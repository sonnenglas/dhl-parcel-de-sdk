<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Responses;

class ShipmentResponse
{
    public function __construct(
        public readonly string $statusTitle,
        public readonly int $statusCode,
        public readonly string $statusDetail,
        /** @var ShipmentItemResponse[] */
        public readonly array $itemResponses,
    ) {

    }
}
