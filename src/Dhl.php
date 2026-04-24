<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

class Dhl
{
    protected Client $client;

    protected ?ReturnsClient $returnsClient = null;

    public function __construct(
        protected string $username,
        protected string $password,
        protected string $apiKey,
        protected bool $productionMode = false
    ) {
        $this->client = new Client($username, $password, $apiKey, $productionMode);
    }

    public function getShipmentService(): ShipmentService
    {
        return new ShipmentService($this->client);
    }

    public function getReturnsService(): ReturnsService
    {
        $this->returnsClient ??= new ReturnsClient(
            $this->username,
            $this->password,
            $this->apiKey,
            $this->productionMode,
        );

        return new ReturnsService($this->returnsClient);
    }
}
