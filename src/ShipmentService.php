<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use GuzzleHttp\Exception\ClientException;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;
use Sonnenglas\DhlParcelDe\Exceptions\MissingArgumentException;
use Sonnenglas\DhlParcelDe\ResponseParsers\ShipmentResponseParser;
use Sonnenglas\DhlParcelDe\Responses\ShipmentResponse;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;

class ShipmentService
{
    /** @var Shipment[] */
    private array $shipments;

    private array $requiredArguments = [
        'shipments',
    ];

    private array $lastResponse;

    private const CREATE_SHIPMENT_URL = 'orders';

    public function __construct(private Client $client)
    {
    }

    public function createShipment(): ?ShipmentResponse
    {
        $this->validateParams();
        $query = $this->prepareQuery();

        try {
            $this->lastResponse = $this->client->post(self::CREATE_SHIPMENT_URL, $query);
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
        return isset($this->lastResponse['client_error']) ? (string) $this->lastResponse['client_error'] : '';
    }

    public function getLastRawResponse(): array
    {
        return $this->lastResponse;
    }

    /**
     * @var Shipment[]
     */
    public function setShipments(array $shipments): self
    {
        foreach ($shipments as $shipment) {
            if (! $shipment instanceof Shipment) {
                throw new InvalidArgumentException('Array should contain values of type Shipment');
            }
        }

        $this->shipments = $shipments;

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
            $data = [
                'product' => $shipment->product->value,
                'billingNumber' => $shipment->billingNumber,
                'refNo' => $shipment->referenceNo,
                'shipper' => $this->prepareAddressQuery($shipment->shipper),
                'consignee' => $this->prepareAddressQuery($shipment->recipient),
                'details' => $this->preparePackageQuery($shipment->package),
                'services' => $this->prepareServicesQuery(),
            ];

            if ($shipment->costCenter) {
                $data['costCenter'] = $shipment->costCenter;
            }

            $query[] = $data;
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
            'addressStreet' => $address->addressStreet,
            'postalCode' => $address->postalCode,
            'city' => $address->city,
            'country' => $address->getCountry(),
        ];

        if (strlen($address->company)) {
            $query['name1'] = $address->company;
            $query['name2'] = $address->name;
        } else {
            $query['name1'] = $address->name;
        }

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
     * @throws MissingArgumentException
     */
    private function validateParams(): void
    {
        foreach ($this->requiredArguments as $param) {
            // @phpstan-ignore-next-line
            if (! isset($this->{$param})) {
                throw new MissingArgumentException("Missing argument: {$param}");
            }
        }

        if (! count($this->shipments)) {
            throw new MissingArgumentException('At least one shipment is required');
        }
    }
}
