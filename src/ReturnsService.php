<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use GuzzleHttp\Exception\ClientException;
use Sonnenglas\DhlParcelDe\Enums\ReturnLabelType;
use Sonnenglas\DhlParcelDe\Exceptions\MissingArgumentException;
use Sonnenglas\DhlParcelDe\ResponseParsers\ReturnResponseParser;
use Sonnenglas\DhlParcelDe\Responses\ReturnLabelResponse;
use Sonnenglas\DhlParcelDe\ValueObjects\ReturnShipment;

class ReturnsService
{
    private const CREATE_RETURN_URL = 'orders';

    private ?ReturnShipment $returnShipment = null;

    private ReturnLabelType $labelType = ReturnLabelType::SHIPMENT_LABEL;

    /**
     * @var array<string, mixed>
     */
    private array $lastResponse = [];

    public function __construct(private ReturnsClient $client)
    {
    }

    public function setReturnShipment(ReturnShipment $returnShipment): self
    {
        $this->returnShipment = $returnShipment;

        return $this;
    }

    public function setLabelType(ReturnLabelType $labelType): self
    {
        $this->labelType = $labelType;

        return $this;
    }

    /**
     * @throws MissingArgumentException
     * @throws ClientException
     */
    public function createReturn(): ReturnLabelResponse
    {
        if ($this->returnShipment === null) {
            throw new MissingArgumentException('Return shipment must be set before creating a return label.');
        }

        $payload = $this->prepareQuery($this->returnShipment);
        $url = self::CREATE_RETURN_URL.'?labelType='.$this->labelType->value;

        try {
            $this->lastResponse = $this->client->post($url, $payload);
            $this->lastResponse['client_error'] = '';

            return (new ReturnResponseParser($this->lastResponse))->parse();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->lastResponse['client_error'] = (string) $response->getBody();

            throw $e;
        }
    }

    public function getLastErrorResponse(): string
    {
        return isset($this->lastResponse['client_error']) ? (string) $this->lastResponse['client_error'] : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastRawResponse(): array
    {
        return $this->lastResponse;
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareQuery(ReturnShipment $shipment): array
    {
        $query = [
            'receiverId' => $shipment->receiverId,
            'shipper' => $shipment->shipper->toDhlApiFormat(),
        ];

        if ($shipment->customerReference !== null) {
            $query['customerReference'] = $shipment->customerReference;
        }

        if ($shipment->shipmentReference !== null) {
            $query['shipmentReference'] = $shipment->shipmentReference;
        }

        if ($shipment->itemWeightKg !== null) {
            $query['itemWeight'] = [
                'uom' => 'kg',
                'value' => $shipment->itemWeightKg,
            ];
        }

        if ($shipment->customsDetails !== null) {
            $query['customsDetails'] = $shipment->customsDetails;
        }

        return $query;
    }
}
