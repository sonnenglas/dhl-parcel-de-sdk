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
                shipmentNo: $itemResponse['shipmentNo'],
                shipmentStatusTitle: $itemResponse['sstatus']['title'],
                shipmentStatusCode: $itemResponse['sstatus']['status'],
                labelUrl: $itemResponse['label']['url'],
                labelFormat: $itemResponse['label']['format'],
            );
        }

        return new ShipmentResponse(
            statusTitle: $this->response['status']['title'],
            statusCode: $this->response['status']['status'],
            statusDetail: $this->response['status']['detail'],
            itemResponses: $itemResponses,
        );
    }

    public function getLabelPdf(array $response): string
    {
        $labelPdf = '';
        foreach ($response['documents'] as $document) {
            if ($document['typeCode'] === 'label' && $document['imageFormat'] === 'PDF') {
                $labelPdf = base64_decode($document['content'], true);
            }
        }

        return $labelPdf;
    }
}
