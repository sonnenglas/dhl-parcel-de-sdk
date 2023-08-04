<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Enums;


enum ShipmentProduct
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
    case DhlPacket = 'V01PAK';
    case DhlPacketInternational = 'V53WPAK';
    case DhlEuropaket = 'V54EPAK';
    case Warenpost = 'V62WP';
    
    V01PAK: DHL PAKET; * V53WPAK: DHL PAKET International; * V54EPAK: DHL Europaket; * V62WP: Warenpost; * V66WPI: Warenpost International
}