<?php

declare(strict_types=1);

namespace Sonnenglas\MyDHL;

use Sonnenglas\D\Services\ShipmentService;

class MyDHL
{
    protected Client $client;

    public function __construct(
        string $username,
        string $password,
        bool $testMode = false
    ) {
        $this->client = new Client($username, $password, $testMode);
    }

    public function getShipmentService(): ShipmentService
    {
        return new ShipmentService($this->client);
    }
}
