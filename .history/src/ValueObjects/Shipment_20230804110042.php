<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ValueObjects;

use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;

class Shipment
{
    /**
     * @throws InvalidAddressException
     */
    public function __construct(
        protected ShipmentProduct $product,
        protected string $billingNumber,
        protected string $refNo,
    
    ) {
        $this->validateData();
    }

    public function getProduct(): ShipmentProduct
    {
        return $this->product
    }

    public function getA
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
        if (strlen($this->billingNumber) !== 12) {
            throw new InvalidArgumentException("Billing number must be 12 characters long. Entered: {$this->billingNumber}.");
        }

        if (strlen($this->refNo) < 8) {
            throw new InvalidArgumentException("Reference number (refNo) must be at least 8 characters long. Entered: {$this->refNo}.");
        }

        
    }
}
