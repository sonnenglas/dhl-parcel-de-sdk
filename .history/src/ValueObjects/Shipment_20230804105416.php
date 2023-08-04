<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ValueObjects;

use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;

class Shipment
{
    /**
     * @throws InvalidAddressException
     */
    public function __construct(
        protected ShipmentProduct $product,
        protected string $billingNumber,
        protected string $postalCode,
        protected string $city,
        protected string $country,
        protected string $state = '',
        protected string $email = '',
        protected string $phone = '',
        protected string $additionalInfo = '',
    
    ) {
        $this->country = strtoupper($this->country);

        $this->validateData();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddressStreet(): string
    {
        return $this->addressStreet;
    }

    public function getAddressLine3(): string
    {
        return $this->addressLine3;
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
            'name1' => $this->name,
            'addressStreet' => $this->addressStreet,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
        ];

        if (strlen($this->email) !== 0) {
            $result['email'] = $this->email;
        }

        if (strlen($this->phone) !== 0) {
            $result['phone'] = $this->phone;
        }

        if (strlen($this->additionalInfo) !== 0) {
            $result['additionalAddressInformation1'] = $this->additionalInfo;
        }

        return $result;
    }

    /**
     * @throws InvalidAddressException
     */
    protected function validateData(): void
    {
        if (strlen($this->country) !== 3) {
            throw new InvalidAddressException("Country Code must be 3 characters long (according to ISO 3166-1 alpha-3 format). Entered: {$this->country}");
        }

        if (strlen($this->addressStreet) === 0) {
            throw new InvalidAddressException("Address Street must not be empty.");
        }

        if (strlen($this->city) === 0) {
            throw new InvalidAddressException("City name must not be empty.");
        }
    }
}
