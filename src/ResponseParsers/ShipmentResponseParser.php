<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ResponseParsers;

use Sonnenglas\DhlParcelDe\Traits\GetRawResponse;
use Sonnenglas\DhlParcelDe\Responses\ShipmentItemResponse;
use Sonnenglas\DhlParcelDe\Responses\ShipmentResponse;

class ShipmentResponseParser
{
    use GetRawResponse;

    public function __construct(private array $response)
    {
    }

    public function parse(): ShipmentResponse
    {
        $itemResponses = [];

        foreach ($this->response['items'] as $itemResponse) {
            $itemResponses[] = new ShipmentItemResponse(
                shipmentNo: (string) $itemResponse['shipmentNo'],
                shipmentStatusTitle: (string) $itemResponse['sstatus']['title'],
                shipmentStatusCode: (int) $itemResponse['sstatus']['statusCode'],
                label: base64_decode($itemResponse['label']['b64'], true),
                labelFormat: (string) $itemResponse['label']['fileFormat'],
            );
        }

        return new ShipmentResponse(
            statusTitle: (string) $this->response['status']['title'],
            statusCode: (int) $this->response['status']['statusCode'],
            statusDetail: (string) $this->response['status']['detail'],
            itemResponses: $itemResponses,
        );
    }
}
