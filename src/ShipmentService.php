<?php

declare(strict_types=1);

namespace Sonnenglas\MyDHL\Services;

use DateTimeImmutable;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\MyDHL\ResponseParsers\ShipmentResponseParser;

class ShipmentService
{
    /** @var Shipment[] */
    private array $shipments;

    private Address $shipper;

    private Address $recipient;

    private array $requiredArguments = [
        'shipments',
        'shipper',
        'recipient',
    ];

    private array $lastResponse;

    private const CREATE_SHIPMENT_URL = 'orders';


    public function __construct(private Client $client)
    {
    }

    public function createShipment(): Shipment
    {
        $this->validateParams();
        $query = $this->prepareQuery();
        $this->lastResponse = $this->client->post(self::CREATE_SHIPMENT_URL, $query);
        return (new ShipmentResponseParser($this->lastResponse))->parse();
    }

    public function getLastRawResponse(): array
    {
        return $this->lastResponse;
    }

    public function addShipper(Address $shipper): self
    {
        $this->shipper = $shipper;

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
            if (!$shipment instanceof Shipment) {
                throw new InvalidArgumentException("Array should contain values of type Shipment");
            }
        }

        $this->shipments = $shipments;

        return $this;
    }

    public function prepareQuery(): array
    {
        $query = [
            'plannedShippingDateAndTime' => $this->plannedShippingDateAndTime->format('Y-m-d\TH:i:s \G\M\TP'),
            'accounts' => $this->prepareAccountsQuery(),
            'customerDetails' => [
                'shipperDetails' => [
                    'postalAddress' => $this->shipperAddress->getAsArray(),
                    'contactInformation' => $this->shipperContact->getAsArray(),
                ],
                'receiverDetails' => [
                    'postalAddress' => $this->receiverAddress->getAsArray(),
                    'contactInformation' => $this->receiverContact->getAsArray(),
                ],
            ],
            'content' => [
                'packages' => $this->preparePackagesQuery(),
                'unitOfMeasurement' => $this->unitOfMeasurement,
                'isCustomsDeclarable' => $this->isCustomsDeclarable,
                'incoterm' => (string) $this->incoterm,
                'description' => $this->description,
            ],
            'getRateEstimates' => $this->getRateEstimates,
            'productCode' => $this->productCode,

        ];

        if (isset($this->shipperTypeCode)) {
            $query['customerDetails']['shipperDetails']['typeCode'] = (string) $this->shipperTypeCode;
        }

        if (isset($this->receiverTypeCode)) {
            $query['customerDetails']['receiverDetails']['typeCode'] = (string) $this->receiverTypeCode;
        }

        if (isset($this->localProductCode) && $this->localProductCode !== '') {
            $query['localProductCode'] = $this->localProductCode;
        }

        if ($this->receiverContact->getEmail() !== '') {
            $query['shipmentNotification'][] = [
                'typeCode' => 'email',
                'languageCountryCode' => $this->receiverAddress->getCountryCode(),
                'receiverId' => $this->receiverContact->getEmail(),
            ];
        }

        if ($this->isPickupRequested) {
            $query['pickup'] = [
                'isRequested' => $this->isPickupRequested,
                'closeTime' => $this->pickupCloseTime,
                'location' => $this->pickupLocation,
            ];

            $query['pickup']['pickupDetails'] = [
                'postalAddress' => $this->pickupAddress->getAsArray(),
                'contactInformation' => $this->pickupContact->getAsArray(),
            ];
        }

        if (count($this->valueAddedServices)) {
            $query['valueAddedServices'] = [];

            foreach ($this->valueAddedServices as $valueAddedService) {
                $query['valueAddedServices'][] = $valueAddedService->getAsArray();
            }
        }

        return $query;
    }

    private function prepareAccountsQuery(): array
    {
        $accounts = [];

        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $accounts[] = $account->getAsArray();
        }

        return $accounts;
    }

    private function preparePackagesQuery(): array
    {
        $packages = [];

        foreach ($this->packages as $package) {
            $packages[] = [
                'weight' => $package->getWeight(),
                'dimensions' => [
                   'length' => $package->getLength(),
                   'width' => $package->getWidth(),
                   'height' => $package->getHeight(),
                ],
            ];
        }

        return $packages;
    }

    /**
     * @return void
     * @throws MissingArgumentException
     */
    private function validateParams(): void
    {
        if (!isset($this->incoterm)) {
            $this->incoterm = new Incoterm('');
        }

        foreach ($this->requiredArguments as $param) {
            if (!isset($this->{$param})) {
                throw new MissingArgumentException("Missing argument: {$param}");
            }
        }

        if ($this->receiverContact->getPhone() == '') {
            throw new MissingArgumentException("Missing phone number for receiver");
        }

        if ($this->receiverContact->getPhone() == '') {
            throw new MissingArgumentException("Missing phone number for shipper");
        }
    }
}
