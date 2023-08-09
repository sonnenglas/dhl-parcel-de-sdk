<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ValueObjects;

use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;

class Address
{
    /**
     * @throws InvalidAddressException
     */
    public function __construct(
        public readonly string $name,
        public readonly string $addressStreet,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $country,
        public readonly string $state = '',
        public readonly string $email = '',
        public readonly string $phone = '',
        public readonly string $additionalInfo = '',
    ) {
        $this->validateData();
    }


    /**
     * @throws InvalidAddressException
     */
    private function validateData(): void
    {
        if (strlen($this->country) !== 3) {
            throw new InvalidAddressException("Country Code must be 3 characters long (according to ISO 3166-1 alpha-3 format). Entered: {$this->country}");
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
