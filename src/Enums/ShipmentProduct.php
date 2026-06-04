<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Enums;

enum ShipmentProduct: string
{
    case DhlPacket = 'V01PAK';
    case DhlPacketInternational = 'V53WPAK';
    case DhlEuropaket = 'V54EPAK';
    // DHL renamed "Warenpost national" to "DHL Kleinpaket". The automatic V62WP -> V62KP
    // conversion on DHL's side was switched off after 2026-05-31; the API now rejects the
    // old V62WP code with "The product entered is unknown."
    case Warenpost = 'V62KP';
    case WarenpostInternational = 'V66WPI';
}
