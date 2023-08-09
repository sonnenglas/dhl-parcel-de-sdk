<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use Sonnenglas\DhlParcelDe\ShipmentService;

class Dhl
{
    protected Client $client;

    public function __construct(
        string $username,
        string $password,
        string $apiKey,
        bool $productionMode = false
    ) {
        $this->client = new Client($username, $password, $apiKey, $productionMode);
    }

    public function getShipmentService(): ShipmentService
    {
        return new ShipmentService($this->client);
    }
}
