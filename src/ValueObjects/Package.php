<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\ValueObjects;

use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;

class Package
{
    /**
     * @param int $height The height of package in mm.
     * @param int $length The length of package in mm.
     * @param int $width The width of package in mm.
     * @param int $weight The weight of package in grams.
     * @throws InvalidAddressException
     */
    public function __construct(
        public readonly int $height,
        public readonly int $length,
        public readonly int $width,
        public readonly int $weight,
    ) {
        $this->validateData();
    }

    private function validateData(): void
    {
        if ($this->height < 1) {
            throw new InvalidArgumentException("Package height must be at least 1 mm. Entered: {$this->height} mm.");
        }

        if ($this->length < 1) {
            throw new InvalidArgumentException("Package length must be at least 1 mm. Entered: {$this->length} mm.");
        }

        if ($this->width < 1) {
            throw new InvalidArgumentException("Package width must be at least 1 mm. Entered: {$this->width} mm.");
        }

        if ($this->weight < 1) {
            throw new InvalidArgumentException("Package weight must be at least 1 g. Entered: {$this->weight} g.");
        }
    }
}
