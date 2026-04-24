<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ResponseParsers;

use Sonnenglas\DhlParcelDe\Responses\ReturnLabelResponse;

class ReturnResponseParser
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(private array $response)
    {
    }

    public function parse(): ReturnLabelResponse
    {
        return new ReturnLabelResponse(
            shipmentNo: (string) ($this->response['shipmentNo'] ?? ''),
            internationalShipmentNo: isset($this->response['internationalShipmentNo'])
                ? (string) $this->response['internationalShipmentNo']
                : null,
            labelPdf: $this->decodeBase64($this->response['label']['b64'] ?? null),
            qrLabelPng: $this->decodeBase64($this->response['qrLabel']['b64'] ?? null),
            qrLink: isset($this->response['qrLink']) ? (string) $this->response['qrLink'] : null,
            statusTitle: (string) ($this->response['sstatus']['title'] ?? ''),
            statusCode: (int) ($this->response['sstatus']['statusCode'] ?? $this->response['sstatus']['status'] ?? 0),
        );
    }

    private function decodeBase64(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = base64_decode($value, true);

        return $decoded === false ? null : $decoded;
    }
}
