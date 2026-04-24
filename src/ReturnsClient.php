<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

class ReturnsClient extends Client
{
    protected const URI_PRODUCTION = 'https://api-eu.dhl.com/parcel/de/shipping/returns/v1/';

    protected const URI_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/shipping/returns/v1/';
}
