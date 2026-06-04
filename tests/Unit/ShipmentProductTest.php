<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;

class ShipmentProductTest extends TestCase
{
    /**
     * DHL retired "Warenpost national" (V62WP) in favour of "DHL Kleinpaket" (V62KP)
     * and stopped auto-converting the old code after 2026-05-31, so the national
     * goods-mail product must serialize to V62KP or the API rejects the shipment
     * with "The product entered is unknown."
     */
    public function testWarenpostNationalSendsKleinpaketCode(): void
    {
        $this->assertEquals('V62KP', ShipmentProduct::Warenpost->value);
    }

    public function testProductCodesAreStable(): void
    {
        $this->assertEquals('V01PAK', ShipmentProduct::DhlPacket->value);
        $this->assertEquals('V53WPAK', ShipmentProduct::DhlPacketInternational->value);
        $this->assertEquals('V54EPAK', ShipmentProduct::DhlEuropaket->value);
        $this->assertEquals('V62KP', ShipmentProduct::Warenpost->value);
        $this->assertEquals('V66WPI', ShipmentProduct::WarenpostInternational->value);
    }
}
