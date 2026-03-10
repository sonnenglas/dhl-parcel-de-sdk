<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonnenglas\DhlParcelDe\Responses\ValidationMessage;

class ValidationMessageTest extends TestCase
{
    public function testWarningIsDetected(): void
    {
        $message = new ValidationMessage(
            property: 'consignee.city',
            validationMessage: 'City does not match postcode',
            validationState: 'Warning',
        );

        $this->assertTrue($message->isWarning());
        $this->assertFalse($message->isError());
    }

    public function testErrorIsDetected(): void
    {
        $message = new ValidationMessage(
            property: 'consignee.name1',
            validationMessage: 'Name is required',
            validationState: 'Error',
        );

        $this->assertFalse($message->isWarning());
        $this->assertTrue($message->isError());
    }

    public function testWarningIsCaseInsensitive(): void
    {
        $message = new ValidationMessage(
            property: 'consignee.city',
            validationMessage: 'Test',
            validationState: 'warning',
        );

        $this->assertTrue($message->isWarning());
    }

    public function testUnknownStateIsTreatedAsError(): void
    {
        $message = new ValidationMessage(
            property: 'consignee.city',
            validationMessage: 'Test',
            validationState: 'Unknown',
        );

        $this->assertTrue($message->isError());
        $this->assertFalse($message->isWarning());
    }
}
