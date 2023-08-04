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
        protected string $name,
        protected string $addressStreet,
        protected string $postalCode,
        protected string $city,
        protected string $country,
    
    ) {
        $this->country = strtoupper($this->country);

        $this->validateData();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAddressStreet(): string
    {
        return $this->addressStreet;
    }

    /**
     * @return string
     */
    public function getAddressLine3(): string
    {
        return $this->addressLine3;
    }

    /**
     * @return string
     */
    public function getCountyName(): string
    {
        return $this->countyName;
    }

    /**
     * @return string
     */
    public function getProvinceCode(): string
    {
        return $this->provinceCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getAsArray(): array
    {
        $result = [
            'addressLine1' => $this->name,
            'countryCode' => $this->countryCode,
            'postalCode' => $this->postalCode,
            'cityName' => $this->city,
        ];

        if ($this->addressLine2 !== '') {
            $result['addressLine2'] = $this->addressLine2;
        }

        if ($this->addressLine3 !== '') {
            $result['addressLine3'] = $this->addressLine3;
        }

        if ($this->countyName !== '') {
            $result['countyName'] = $this->countyName;
        }

        if ($this->provinceCode !== '') {
            $result['provinceCode'] = $this->provinceCode;
        }

        return $result;
    }

    /**
     * @throws InvalidAddressException
     */
    protected function validateData(): void
    {
        if (strlen($this->countryCode) !== 3) {
            throw new InvalidAddressException("Country Code must be 3 characters long (according to ISO 3166-1 alpha-3 format). Entered: {$this->countryCode}");
        }

        if (strlen($this->addressStreet) === 0) {
            throw new InvalidAddressException("Address Street must not be empty.");
        }

        if (strlen($this->city) === 0) {
            throw new InvalidAddressException("City name must not be empty.");
        }
    }
}
