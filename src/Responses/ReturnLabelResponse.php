<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Responses;

class ReturnLabelResponse
{
    public function __construct(
        public readonly string $shipmentNo,
        public readonly ?string $internationalShipmentNo,
        public readonly ?string $labelPdf,
        public readonly ?string $qrLabelPng,
        public readonly ?string $qrLink,
        public readonly string $statusTitle,
        public readonly int $statusCode,
    ) {
    }
}
