<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Traits;

trait GetRawResponse
{
    private function getRawResponse(): array
    {
        return $this->response;
    }
}
