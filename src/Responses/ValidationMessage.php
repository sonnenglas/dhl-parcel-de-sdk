<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Responses;

class ValidationMessage
{
    public function __construct(
        public readonly string $property,
        public readonly string $validationMessage,
        public readonly string $validationState,
    ) {
    }

    public function isWarning(): bool
    {
        return strtolower($this->validationState) === 'warning';
    }

    public function isError(): bool
    {
        return ! $this->isWarning();
    }
}
