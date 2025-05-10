<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;
use Sonnenglas\DhlParcelDe\Exceptions\MissingArgumentException;
use Sonnenglas\DhlParcelDe\ResponseParsers\ShipmentResponseParser;
use Sonnenglas\DhlParcelDe\Responses\ShipmentResponse;
use GuzzleHttp\Exception\ClientException;
use Sonnenglas\DhlParcelDe\Enums\LabelFormat;
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

    private string $profile = 'STANDARD_GRUPPENPROFIL';

    private ?LabelFormat $labelFormat = null;

    public function __construct(private Client $client) {}

    public function createShipment(): ?ShipmentResponse
    {
        $this->validateParams();
        $query = $this->prepareQuery();

        $url = self::CREATE_SHIPMENT_URL;

        if ($this->labelFormat) {
            $url .= '?printFormat=' . $this->labelFormat->value;
        }

        try {
            $this->lastResponse = $this->client->post($url, $query);
            $this->lastResponse['client_error'] = '';

            return (new ShipmentResponseParser($this->lastResponse))->parse();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->lastResponse['client_error'] = (string) $response->getBody();

            throw $e;
        }

        return null;
    }

    /**
     * Delete a shipment by its shipment number.
     * 
     * @param string $shipmentNumber The shipment number to delete
     * @return bool True if deletion was successful, false otherwise
     * @throws InvalidArgumentException When shipment number is empty
     */
    public function deleteShipment(string $shipmentNumber): bool
    {
        if (empty($shipmentNumber)) {
            throw new InvalidArgumentException("Shipment number must not be empty");
        }

        $query = [
            'profile' => $this->profile,
            'shipment' => $shipmentNumber,
        ];

        try {
            $this->lastResponse = $this->client->delete(self::CREATE_SHIPMENT_URL, $query);
            $this->lastResponse['client_error'] = '';

            // Check for success in the response status (single shipment response)
            if (isset($this->lastResponse['status'])) {
                $statusCode = $this->lastResponse['status']['statusCode'] ?? $this->lastResponse['status']['status'] ?? null;
                if ($statusCode === 200) {
                    return true;
                }
            }

            // Check items array for multi-shipment response (207)
            if (isset($this->lastResponse['items']) && is_array($this->lastResponse['items'])) {
                foreach ($this->lastResponse['items'] as $item) {
                    // Find the item matching our shipment number
                    if (isset($item['shipmentNo']) && $item['shipmentNo'] === $shipmentNumber && isset($item['sstatus'])) {
                        $statusCode = $item['sstatus']['statusCode'] ?? $item['sstatus']['status'] ?? null;
                        return $statusCode === 200;
                    }
                }
            }

            return false;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->lastResponse['client_error'] = (string) $response->getBody();

            return false;
        }
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
     * Set the profile to be used for DHL API requests.
     * 
     * @param string $profileName The profile name to use
     * @return self
     */
    public function setProfile(string $profileName = 'STANDARD_GRUPPENPROFIL'): self
    {
        $this->profile = $profileName;

        return $this;
    }

    /**
     * @var Shipment[] $shipments
     * @param array $shipments
     * @return ShipmentService
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

    public function setLabelFormat(LabelFormat $labelFormat): self
    {
        $this->labelFormat = $labelFormat;

        return $this;
    }

    public function prepareQuery(): array
    {
        $query = [];

        $query['profile'] = $this->profile;

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
