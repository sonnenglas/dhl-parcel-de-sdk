<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Enums;


enum ShipmentProduct: string
{
    case DhlPacket = 'V01PAK';
    case DhlPacketInternational = 'V53WPAK';
    case DhlEuropaket = 'V54EPAK';
    case Warenpost = 'V62WP';
    case WarenpostInternational = 'V66WPI';
}