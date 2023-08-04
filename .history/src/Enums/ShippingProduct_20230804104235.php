<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Enums;

use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;

enum Suit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}