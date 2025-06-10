<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ValueObjects;

use League\ISO3166\Exception\DomainException;
use League\ISO3166\Exception\OutOfBoundsException;
use League\ISO3166\ISO3166;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;

class Address
{
    private string $isoCountry;

    /**
     * @throws InvalidAddressException
     */
    public function __construct(
        public readonly string $name,
        public readonly string $addressStreet,
        public readonly string $postalCode,
        public readonly string $city,
        private string $country,
        public readonly string $state = '',
        public readonly string $email = '',
        public readonly string $phone = '',
        public readonly string $additionalInfo = '',
        public readonly string $company = '',
        public readonly ?int $packstationId = null,
        public readonly ?string $packstationCustomerNumber = null,
    ) {
        $this->validateData();
        $this->convertCountry();
    }

    /**
     * Convert country code to ISO3166 alpha 3
     *
     * @throws DomainException
     * @throws OutOfBoundsException
     */
    private function convertCountry(): void
    {
        $data = (new ISO3166())->alpha2($this->country);

        $this->isoCountry = $data['alpha3'];
    }

    public function getCountry(): string
    {
        return $this->isoCountry;
    }

    /**
     * Check if this address is for a packstation delivery
     */
    public function isPackstation(): bool
    {
        return $this->packstationId !== null && $this->packstationCustomerNumber !== null;
    }

    /**
     * Convert address to DHL API format
     * Returns either ContactAddress or Locker structure based on address type
     */
    public function toDhlApiFormat(): array
    {
        if ($this->isPackstation()) {
            return $this->toLockerFormat();
        }

        return $this->toContactAddressFormat();
    }

    /**
     * Convert to DHL API Locker format for packstation addresses
     */
    private function toLockerFormat(): array
    {
        return [
            'name' => $this->name,
            'lockerID' => $this->packstationId,
            'postNumber' => $this->packstationCustomerNumber,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
            'country' => $this->getCountry(),
        ];
    }

    /**
     * Convert to DHL API ContactAddress format for regular addresses
     */
    private function toContactAddressFormat(): array
    {
        $address = [
            'addressStreet' => $this->addressStreet,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->getCountry(),
        ];

        if (strlen($this->company)) {
            $combinedName = $this->name . ', ' . $this->company;

            // If combined name exceeds 50 chars, use fallback format
            if (strlen($combinedName) > 50) {
                $address['name1'] = $this->name;
                $address['name2'] = $this->company;

                // Use name3 for additionalInfo in fallback mode
                if (strlen($this->additionalInfo)) {
                    if (strlen($this->additionalInfo) <= 50) {
                        $address['name3'] = $this->additionalInfo;
                    } else {
                        // If additionalInfo is too long for name3, truncate to 50 chars
                        $address['name3'] = substr($this->additionalInfo, 0, 50);
                    }
                }
            } else {
                // Combine contact name and company in name1
                $address['name1'] = $combinedName;

                // Split additionalInfo between name2 and name3 (50 chars each)
                if (strlen($this->additionalInfo)) {
                    $this->splitAdditionalInfoToFields($address);
                }
            }
        } else {
            $address['name1'] = $this->name;

            // Split additionalInfo between name2 and name3 (50 chars each)
            if (strlen($this->additionalInfo)) {
                $this->splitAdditionalInfoToFields($address);
            }
        }

        if (strlen($this->state)) {
            $address['state'] = $this->state;
        }

        if (strlen($this->email)) {
            $address['email'] = $this->email;
        }

        if (strlen($this->phone)) {
            $address['phone'] = $this->phone;
        }

        return $address;
    }

    /**
     * Split additionalInfo between name2 and name3 (50 chars each)
     */
    private function splitAdditionalInfoToFields(array &$address): void
    {
        $info = $this->additionalInfo;

        if (strlen($info) <= 50) {
            // Fits in one field
            $address['name2'] = $info;
        } else {
            // Split into two parts of 50 chars each
            $address['name2'] = substr($info, 0, 50);
            $address['name3'] = substr($info, 50, 50);
        }
    }

    /**
     * @throws InvalidAddressException
     */
    private function validateData(): void
    {
        if (strlen($this->country) !== 2) {
            throw new InvalidAddressException("Country Code must be 2 characters long (according to ISO 3166-1 alpha-2 format). Entered: {$this->country}");
        }

        if ($this->country !== strtoupper($this->country)) {
            throw new InvalidAddressException("Country Code must be in upper-case. Entered: {$this->country}");
        }

        // Validate packstation-specific fields
        $this->validatePackstationData();

        // Skip address street validation for packstation addresses
        if (!$this->isPackstation() && strlen($this->addressStreet) === 0) {
            throw new InvalidAddressException('Address Street is required for regular addresses.');
        }

        if (strlen($this->name) === 0) {
            throw new InvalidAddressException('Name is required.');
        }

        if (strlen($this->city) === 0) {
            throw new InvalidAddressException('City is required.');
        }

        if (strlen($this->postalCode) === 0) {
            throw new InvalidAddressException('Postal code is required.');
        }

        if (strlen($this->additionalInfo) > 100) {
            throw new InvalidAddressException("Additional info must not be longer than 100 characters. Entered: {$this->additionalInfo}");
        }

        if (strlen($this->name) > 50) {
            throw new InvalidAddressException("Name must not be longer than 50 characters. Entered: {$this->name}");
        }

        if (strlen($this->company) > 50) {
            throw new InvalidAddressException("Company name must not be longer than 50 characters. Entered: {$this->company}");
        }
    }

    /**
     * @throws InvalidAddressException
     */
    private function validatePackstationData(): void
    {
        // If either packstation field is provided, both must be provided
        if (($this->packstationId !== null) !== ($this->packstationCustomerNumber !== null)) {
            throw new InvalidAddressException('Both packstationId and packstationCustomerNumber must be provided for packstation delivery.');
        }

        if ($this->packstationId !== null) {
            // Validate packstation ID (3-digit number, 100-999)
            if ($this->packstationId < 100 || $this->packstationId > 999) {
                throw new InvalidAddressException('Packstation ID must be a 3-digit number between 100 and 999.');
            }

            // Validate customer number (6-10 digits)
            if (!preg_match('/^[0-9]{6,10}$/', $this->packstationCustomerNumber)) {
                throw new InvalidAddressException('Packstation customer number must be 6-10 digits.');
            }

            // Packstations are only available in Germany
            if ($this->country !== 'DE') {
                throw new InvalidAddressException('Packstation delivery is only available in Germany (country must be DE).');
            }
        }
    }
}
