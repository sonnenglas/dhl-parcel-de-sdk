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
     *
     * @param string $addressHouse Optional house number sent as a separate field. Required by the
     *                             Parcel DE Returns API; for the Shipping API DHL also accepts
     *                             the house number embedded in $addressStreet, but using a
     *                             dedicated field is recommended.
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
        public readonly string $addressHouse = '',
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

        if (mb_strlen($this->addressHouse)) {
            $address['addressHouse'] = $this->addressHouse;
        }

        $this->assignNameFields($address);

        if (mb_strlen($this->state)) {
            $address['state'] = $this->state;
        }

        if (mb_strlen($this->email)) {
            $address['email'] = $this->email;
        }

        if (mb_strlen($this->phone)) {
            $address['phone'] = $this->phone;
        }

        return $address;
    }

    /**
     * Distribute the recipient name, company and additional info across DHL's
     * three name fields (name1/name2/name3), each capped at 50 characters.
     *
     * DHL only accepts 50 characters per name line but provides three of them.
     * Rather than rejecting an over-long name — which previously threw and left
     * orders stranded in fulfillment when a label could not be generated — the
     * parts are combined where they fit and any part that does not is split
     * across the remaining lines. Only genuinely unfittable input (more than
     * three 50-character lines) is rejected.
     *
     * @param  array<string, mixed>  $address
     *
     * @throws InvalidAddressException
     */
    private function assignNameFields(array &$address): void
    {
        $chunks = $this->splitSegmentsIntoFields($this->buildNameSegments());

        if (count($chunks) > 3) {
            throw new InvalidAddressException(
                "Name, company and additional info are too long to fit DHL's three "
                . '50-character name fields (150 characters total).'
            );
        }

        $address['name1'] = $chunks[0] ?? $this->name;

        if (isset($chunks[1])) {
            $address['name2'] = $chunks[1];
        }

        if (isset($chunks[2])) {
            $address['name3'] = $chunks[2];
        }
    }

    /**
     * Build the ordered list of logical name lines from name, company and
     * additionalInfo, combining name and company into a single line when they
     * fit within one 50-character field.
     *
     * @return array<int, string>
     */
    private function buildNameSegments(): array
    {
        $segments = [];

        if (mb_strlen($this->company)) {
            $combinedName = (mb_strlen($this->name) > 0 ? $this->name . ', ' : '') . $this->company;

            if (mb_strlen($combinedName) <= 50) {
                $segments[] = $combinedName;
            } else {
                if (mb_strlen($this->name) > 0) {
                    $segments[] = $this->name;
                }

                $segments[] = $this->company;
            }
        } elseif (mb_strlen($this->name) > 0) {
            $segments[] = $this->name;
        }

        if (mb_strlen($this->additionalInfo)) {
            $segments[] = $this->additionalInfo;
        }

        return $segments;
    }

    /**
     * Flatten logical name lines into physical DHL fields, splitting any line
     * longer than 50 characters across consecutive fields.
     *
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    private function splitSegmentsIntoFields(array $segments): array
    {
        $chunks = [];

        foreach ($segments as $segment) {
            $length = mb_strlen($segment);

            if ($length <= 50) {
                $chunks[] = $segment;

                continue;
            }

            for ($offset = 0; $offset < $length; $offset += 50) {
                $chunks[] = mb_substr($segment, $offset, 50);
            }
        }

        return $chunks;
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
        if (!$this->isPackstation() && mb_strlen($this->addressStreet) === 0) {
            throw new InvalidAddressException('Address Street is required for regular addresses.');
        }

        if (mb_strlen($this->name) === 0) {
            throw new InvalidAddressException('Name is required.');
        }

        if (mb_strlen($this->city) === 0) {
            throw new InvalidAddressException('City is required.');
        }

        if (mb_strlen($this->postalCode) === 0) {
            throw new InvalidAddressException('Postal code is required.');
        }

        if (mb_strlen($this->additionalInfo) > 100) {
            throw new InvalidAddressException("Additional info must not be longer than 100 characters. Entered: {$this->additionalInfo}");
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
