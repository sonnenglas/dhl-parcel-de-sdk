<?php

declare(strict_types=1);

namespace Sonnenglas\MyDHL\ResponseParsers;

use Sonnenglas\DhlParcelDe\Traits\GetRawResponse;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;

class ShipmentResponseParser
{
    use GetRawResponse;

    public function __construct(private array $response)
    {
    }

    public function parse(): Shipment
    {
        return new Shipment(
            shipmentTrackingNumber: $this->response['shipmentTrackingNumber'],
            cancelPickupUrl: $this->response['cancelPickupUrl'],
            trackingUrl: $this->response['trackingUrl'],
            dispatchConfirmationNumber: $this->response['dispatchConfirmationNumber'],
            labelPdf: $this->getLabelPdf($this->response),
            warnings: $this->response['warnings'] ?? [],
            packages: $this->response['packages'] ?? [],
            documents: $this->response['documents'] ?? [],
            shipmentDetails: $this->response['shipmentDetails'] ?? [],
            shipmentCharges: $this->response['shipmentCharges'] ?? [],
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
