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
        public readonly ShipmentProduct $product,
        public readonly string $billingNumber,
        public readonly string $referenceNo,
        public readonly Address $shipper,
        public readonly Address $recipient,
        public readonly Package $package,
    ) {
        $this->validateData();
    }

    /**
     * @throws InvalidAddressException
     */
    private function validateData(): void
    {
        if (strlen($this->billingNumber) !== 14) {
            throw new InvalidArgumentException("Billing number must be 14 characters long. Entered: {$this->billingNumber}.");
        }

        if (strlen($this->referenceNo) < 8) {
            throw new InvalidArgumentException("Reference number (referenceNo) must be at least 8 characters long. Entered: {$this->referenceNo}.");
        }
    }
}
