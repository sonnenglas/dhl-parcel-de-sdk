<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use DateTimeImmutable;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;

class ShipmentService
{
    private Address $shipper;
    private Address $recipient;
    /** $var  */
    private array $shipments;
    
    private DateTimeImmutable $plannedShippingDateAndTime;

    private bool $isPickupRequested;
    private string $pickupCloseTime;
    private string $pickupLocation;
    private Address $pickupAddress;
    private Contact $pickupContact;
    private string $productCode;
    private string $localProductCode;
    private Address $shipperAddress;
    private Contact $shipperContact;
    private Address $receiverAddress;
    private Contact $receiverContact;
    private bool $getRateEstimates = false;
    private bool $isCustomsDeclarable = false;
    private string $description;
    private Incoterm $incoterm;
    private CustomerTypeCode $shipperTypeCode;
    private CustomerTypeCode $receiverTypeCode;
    /** @var ValueAddedService[] */
    private array $valueAddedServices = [];

    protected string $unitOfMeasurement = 'metric';

    /**
     * @var Account[]
     */
    private array $accounts;

    /**
     * @var Package[]
     */
    private array $packages;

    private array $requiredArguments = [
        'plannedShippingDateAndTime',
        'isPickupRequested',
        'productCode',
        'shipperAddress',
        'shipperContact',
        'receiverAddress',
        'receiverContact',
        'accounts',
        'packages',
    ];

    private array $lastResponse;

    private const CREATE_SHIPMENT_URL = 'shipments';


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

    public function setPlannedShippingDateAndTime(DateTimeImmutable $date): self
    {
        $this->plannedShippingDateAndTime = $date;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param bool $isPickupRequested Please advise if a pickup is needed for this shipment
     * @param string $pickupCloseTime The latest time the location premises is available to dispatch the DHL Express shipment. (HH:MM)
     * @param string $pickupLocation Provides information on where the package should be picked up by DHL courier
     * @return $this
     */
    public function setPickup(bool $isPickupRequested, string $pickupCloseTime = '', string $pickupLocation = ''): self
    {
        $this->isPickupRequested = $isPickupRequested;
        $this->pickupCloseTime = $pickupCloseTime;
        $this->pickupLocation = $pickupLocation;

        return $this;
    }

    public function isCustomsDeclarable(bool $isCustomsDeclarable): self
    {
        $this->isCustomsDeclarable = $isCustomsDeclarable;

        return $this;
    }

    public function setShipperTypeCode(CustomerTypeCode $typeCode): self
    {
        $this->shipperTypeCode = $typeCode;

        return $this;
    }

    public function setReceiverTypeCode(CustomerTypeCode $typeCode): self
    {
        $this->receiverTypeCode = $typeCode;

        return $this;
    }


    public function setPickupDetails(Address $pickupAddress, Contact $pickupContact): self
    {
        $this->pickupAddress = $pickupAddress;
        $this->pickupContact = $pickupContact;

        return $this;
    }

    public function setProductCode(string $productCode): self
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function setLocalProductCode(string $localProductCode): self
    {
        $this->localProductCode = $localProductCode;

        return $this;
    }

    /**
     * @param Account[] $accounts
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setAccounts(array $accounts): self
    {
        foreach ($accounts as $account) {
            if (!$account instanceof Account) {
                throw new InvalidArgumentException("Array should contain values of type Account");
            }
        }

        $this->accounts = $accounts;

        return $this;
    }

    public function setShipperDetails(Address $shipperAddress, Contact $shipperContact): self
    {
        $this->shipperAddress = $shipperAddress;
        $this->shipperContact = $shipperContact;

        return $this;
    }

    public function setReceiverDetails(Address $receiverAddress, Contact $receiverContact): self
    {
        $this->receiverAddress = $receiverAddress;
        $this->receiverContact = $receiverContact;

        return $this;
    }

    public function setGetRateEstimates(bool $getRateEstimates): self
    {
        $this->getRateEstimates = $getRateEstimates;

        return $this;
    }

    /**
     * @param array<Package> $packages
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setPackages(array $packages): self
    {
        foreach ($packages as $package) {
            if (!$package instanceof Package) {
                throw new InvalidArgumentException("Array should contain values of type Package");
            }
        }

        $this->packages = $packages;

        return $this;
    }

    public function setIncoterm(Incoterm $incoterm): self
    {
        $this->incoterm = $incoterm;

        return $this;
    }

    public function setValueAddedServices(array $valueAddedServices): self
    {
        foreach ($valueAddedServices as $valueAddedService) {
            if (!$valueAddedService instanceof ValueAddedService) {
                throw new InvalidArgumentException("Array should contain values of type ValueAddedService");
            }
        }

        $this->valueAddedServices = $valueAddedServices;

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
