<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ValueObjects;

use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;

class ReturnShipment
{
    /**
     * @param  array<string, mixed>|null  $customsDetails  required for non-EU destinations (CHE, GBR, NOR)
     */
    public function __construct(
        public readonly string $receiverId,
        public readonly Address $shipper,
        public readonly ?string $customerReference = null,
        public readonly ?string $shipmentReference = null,
        public readonly ?float $itemWeightKg = null,
        public readonly ?array $customsDetails = null,
    ) {
        $this->validateData();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateData(): void
    {
        if ($this->receiverId === '') {
            throw new InvalidArgumentException('receiverId must not be empty.');
        }

        if ($this->customerReference !== null && strlen($this->customerReference) > 30) {
            throw new InvalidArgumentException("customerReference must be at most 30 characters. Entered: {$this->customerReference}.");
        }

        if ($this->itemWeightKg !== null && $this->itemWeightKg <= 0) {
            throw new InvalidArgumentException('itemWeightKg must be greater than zero.');
        }
    }
}
