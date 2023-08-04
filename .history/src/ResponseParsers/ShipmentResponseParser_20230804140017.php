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

    public function parse():void
    {
        
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
