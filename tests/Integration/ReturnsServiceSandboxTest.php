<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sonnenglas\DhlParcelDe\Dhl;
use Sonnenglas\DhlParcelDe\Enums\ReturnLabelType;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\ReturnShipment;

/**
 * Smoke test hitting the DHL Parcel DE sandbox Returns API.
 *
 * Requires environment variables (falls back to documented public sandbox credentials):
 *   DHL_SANDBOX_USERNAME   (default: user-valid)
 *   DHL_SANDBOX_PASSWORD   (default: SandboxPasswort2023!)
 *   DHL_SANDBOX_API_KEY    (no default — skip test if missing)
 *   DHL_SANDBOX_RECEIVER_ID (default: deu)
 *
 * Run with: vendor/bin/phpunit --testsuite Integration
 */
class ReturnsServiceSandboxTest extends TestCase
{
    public function testCreatesReturnLabelViaSandbox(): void
    {
        $apiKey = getenv('DHL_SANDBOX_API_KEY') ?: '';

        if ($apiKey === '') {
            $this->markTestSkipped('DHL_SANDBOX_API_KEY not set — integration test skipped.');
        }

        $username = getenv('DHL_SANDBOX_USERNAME') ?: 'user-valid';
        $password = getenv('DHL_SANDBOX_PASSWORD') ?: 'SandboxPasswort2023!';
        $receiverId = getenv('DHL_SANDBOX_RECEIVER_ID') ?: 'deu';

        $dhl = new Dhl($username, $password, $apiKey, productionMode: false);
        $returnsService = $dhl->getReturnsService();

        $shipment = new ReturnShipment(
            receiverId: $receiverId,
            shipper: new Address(
                name: 'Max Mustermann',
                addressStreet: 'Sträßchensweg 10',
                postalCode: '53113',
                city: 'Bonn',
                country: 'DE',
                email: 'max@example.com',
            ),
            customerReference: 'SANDBOX-TEST-'.time(),
            itemWeightKg: 2.0,
        );

        $response = $returnsService
            ->setReturnShipment($shipment)
            ->setLabelType(ReturnLabelType::SHIPMENT_LABEL)
            ->createReturn();

        $this->assertNotEmpty($response->shipmentNo, 'Sandbox must return a shipment number.');
        $this->assertNotNull($response->labelPdf, 'Sandbox must return a label PDF.');
        $this->assertStringStartsWith('%PDF', (string) $response->labelPdf, 'Label must be a PDF binary.');
    }
}
