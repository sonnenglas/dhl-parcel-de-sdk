<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Responses;

class ShipmentItemResponse
{
    public function __construct(
        public readonly string $shipmentNo,
        public readonly string $shipmentStatusTitle,
        public readonly int $shipmentStatusCode,
        public readonly string $label,
        public readonly string $labelFormat,
    ) {

    }
}
