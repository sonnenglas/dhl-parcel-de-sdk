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
        protected string $referenceNo,
    
    ) {
        $this->validateData();
    }

    public function getProduct(): ShipmentProduct
    {
        return $this->product
    }

    public function getBillingNumber(): string
    {
        return $this->billingNumber;
    }

    public function getReferenceNo(): string
    {
        return $this->referenceNo;
    }

    public function getAsArray(): array
    {
        $result = [
            'product' => $this->product->value,
            'billingNumber' => $this->billingNumber,
            'refNo' => $this->referenceNo,
            

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

        if (strlen($this->referenceNo) < 8) {
            throw new InvalidArgumentException("Reference number (refNo) must be at least 8 characters long. Entered: {$this->referenceNo}.");
        }

        
    }
}
