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
        protected string $cityName,
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

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    public function getAsArray(): array
    {
        $result = [
            'addressLine1' => $this->name,
            'countryCode' => $this->countryCode,
            'postalCode' => $this->postalCode,
            'cityName' => $this->cityName,
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
        if (strlen($this->countryCode) !== 2) {
            throw new InvalidAddressException("Country Code must be 2 characters long. Entered: {$this->countryCode}");
        }

        if (strlen($this->name) === 0) {
            throw new InvalidAddressException("Address Line1 must not be empty.");
        }

        if (strlen($this->cityName) === 0) {
            throw new InvalidAddressException("City name must not be empty.");
        }
    }
}
