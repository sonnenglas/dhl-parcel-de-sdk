<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use Exception;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;
use Sonnenglas\DhlParcelDe\Exceptions\MissingArgumentException;
use Sonnenglas\DhlParcelDe\ResponseParsers\ShipmentResponseParser;
use Sonnenglas\DhlParcelDe\Responses\ShipmentResponse;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;
use GuzzleHttp\Exception\ClientException;
use Sonnenglas\DhlParcelDe\Enums\LabelFormat;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;

class ShipmentService
{
    /** @var Shipment[] */
    private array $shipments;

    private array $requiredArguments = [
        'shipments',
    ];

    private array $lastResponse;

    private const CREATE_SHIPMENT_URL = 'orders';

    private ?LabelFormat $labelFormat;


    public function __construct(private Client $client) {}

    public function createShipment(): ?ShipmentResponse
    {
        $this->validateParams();
        $query = $this->prepareQuery();

        $headers = [];

        if ($this->labelFormat) {
            $headers['printFormat'] = $this->labelFormat->value;
        }

        try {
            $this->lastResponse = $this->client->post(self::CREATE_SHIPMENT_URL, $query, $headers);
            $this->lastResponse['client_error'] = '';

            return (new ShipmentResponseParser($this->lastResponse))->parse();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->lastResponse['client_error'] = (string) $response->getBody();

            throw $e;
        }

        return null;
    }

    public function getLastErrorResponse(): string
    {
        return $this->lastResponse['client_error'] ?: '';
    }

    public function getLastRawResponse(): array
    {
        return $this->lastResponse;
    }

    /**
     * @var Shipment[] $shipments
     * @param array $shipments
     * @return ShipmentService
     */
    public function setShipments(array $shipments): self
    {
        foreach ($shipments as $shipment) {
            if (!$shipment instanceof Shipment) {
                throw new InvalidArgumentException("Array should contain values of type Shipment");
            }
        }

        $this->shipments = $shipments;

        return $this;
    }

    public function setLabelFormat(LabelFormat $labelFormat): self
    {
        $this->labelFormat = $labelFormat;

        return $this;
    }

    public function prepareQuery(): array
    {
        $query = [];

        $query['profile'] = 'STANDARD_GRUPPENPROFIL';

        $query['shipments'] = $this->prepareShipmentsQuery();

        return $query;
    }

    private function prepareShipmentsQuery(): array
    {
        $query = [];

        foreach ($this->shipments as $shipment) {
            $query[] = [
                'product' => $shipment->product->value,
                'billingNumber' => $shipment->billingNumber,
                'refNo' => $shipment->referenceNo,
                'shipper' => $this->prepareAddressQuery($shipment->shipper),
                'consignee' => $this->prepareAddressQuery($shipment->recipient),
                'details' => $this->preparePackageQuery($shipment->package),
                'services' => $this->prepareServicesQuery(),
            ];
        }

        return $query;
    }

    private function preparePackageQuery(Package $package): array
    {
        return [
            'dim' => [
                'uom' => 'mm',
                'height' => $package->height,
                'length' => $package->length,
                'width' => $package->width,
            ],
            'weight' => [
                'uom' => 'g',
                'value' => $package->weight,
            ],
        ];
    }

    private function prepareAddressQuery(Address $address): array
    {
        $query = [
            'name1' => $address->name,
            'addressStreet' => $address->addressStreet,
            'postalCode' => $address->postalCode,
            'city' => $address->city,
            'country' => $address->getCountry(),
        ];

        if (strlen($address->email)) {
            $query['email'] = $address->email;
        }

        if (strlen($address->phone)) {
            $query['phone'] = $address->phone;
        }

        if (strlen($address->additionalInfo)) {
            $query['additionalAddressInformation1'] = $address->additionalInfo;
        }

        return $query;
    }

    private function prepareServicesQuery(): array
    {
        // This part is required when shipping internationally
        return [
            'endorsement' => 'RETURN',
        ];
    }

    /**
     * @return void
     * @throws MissingArgumentException
     */
    private function validateParams(): void
    {
        foreach ($this->requiredArguments as $param) {
            // @phpstan-ignore-next-line
            if (!isset($this->{$param})) {
                throw new MissingArgumentException("Missing argument: {$param}");
            }
        }

        if (! count($this->shipments)) {
            throw new MissingArgumentException("At least one shipment is required");
        }
    }
}
