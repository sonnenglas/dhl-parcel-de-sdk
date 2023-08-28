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
    ) {
        $this->validateData();
        $this->convertCountry();
    }

    /**
     * Convert country code to ISO3166 alpha 3
     * 
     * @return void 
     * @throws DomainException 
     * @throws OutOfBoundsException 
     */
    private function convertCountry(): void
    {
        $data = (new ISO3166)->alpha2($this->country);
     
        $this->isoCountry = $data['alpha3'];
    }

    public function getCountry(): string
    {
        return $this->isoCountry;
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

        if (strlen($this->addressStreet) === 0) {
            throw new InvalidAddressException("Address street must not be empty.");
        }

        if (strlen($this->city) === 0) {
            throw new InvalidAddressException("City name must not be empty.");
        }

        if (strlen($this->postalCode) === 0) {
            throw new InvalidAddressException("Postal code must not be empty.");
        }

        if (strlen($this->name) === 0) {
            throw new InvalidAddressException("Name must not be empty.");
        }
    }
}
