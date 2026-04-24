<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Enums;

enum ReturnLabelType: string
{
    case SHIPMENT_LABEL = 'SHIPMENT_LABEL';
    case QR_LABEL = 'QR_LABEL';
    case BOTH = 'BOTH';
}
