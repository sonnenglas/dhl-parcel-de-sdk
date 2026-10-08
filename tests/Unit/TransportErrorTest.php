<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sonnenglas\DhlParcelDe\Client;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\ShipmentService;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;

class TransportErrorTest extends TestCase
{
    public function testConnectionFailureIsExposedViaLastErrorResponse(): void
    {
        $service = $this->serviceThrowing(
            new ConnectException('cURL error 28: Operation timed out', new Request('POST', 'orders'))
        );

        try {
            $service->validateShipment();
            $this->fail('Expected exception was not thrown.');
        } catch (ConnectException) {
            $this->assertStringContainsString('Operation timed out', $service->getLastErrorResponse());
        }
    }

    public function testServerErrorWithEmptyBodyFallsBackToExceptionMessage(): void
    {
        $request = new Request('POST', 'orders');
        $service = $this->serviceThrowing(
            new ServerException('Server error: 502 Bad Gateway', $request, new Response(502))
        );

        try {
            $service->createShipment();
            $this->fail('Expected exception was not thrown.');
        } catch (ServerException) {
            $this->assertStringContainsString('502 Bad Gateway', $service->getLastErrorResponse());
        }
    }

    public function testServerErrorBodyIsPreferredOverExceptionMessage(): void
    {
        $request = new Request('POST', 'orders');
        $service = $this->serviceThrowing(
            new ServerException('Server error', $request, new Response(500, [], '{"detail":"boom"}'))
        );

        try {
            $service->createShipment();
            $this->fail('Expected exception was not thrown.');
        } catch (ServerException) {
            $this->assertSame('{"detail":"boom"}', $service->getLastErrorResponse());
        }
    }

    public function testStaleErrorFromPreviousCallIsNotReturned(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('post')->willReturnOnConsecutiveCalls(
            $this->throwException(new ServerException('first', new Request('POST', 'orders'), new Response(500, [], 'old body'))),
            $this->throwException(new \RuntimeException('not a guzzle error'))
        );
        $service = $this->configuredService($client);

        try {
            $service->validateShipment();
        } catch (ServerException) {
        }

        try {
            $service->validateShipment();
        } catch (\RuntimeException) {
        }

        $this->assertSame('', $service->getLastErrorResponse());
    }

    public function testClientAppliesTimeouts(): void
    {
        $client = new Client('u', 'p', 'k', false, null, 12.5, 3.0);
        $method = (new ReflectionClass($client))->getMethod('getRequestOptions');
        $method->setAccessible(true);
        $options = $method->invoke($client, 'POST', []);

        $this->assertSame(12.5, $options['timeout']);
        $this->assertSame(3.0, $options['connect_timeout']);
    }

    public function testClientDefaultTimeouts(): void
    {
        $client = new Client('u', 'p', 'k', false);
        $method = (new ReflectionClass($client))->getMethod('getRequestOptions');
        $method->setAccessible(true);
        $options = $method->invoke($client, 'POST', []);

        $this->assertSame(30.0, $options['timeout']);
        $this->assertSame(10.0, $options['connect_timeout']);
    }

    private function serviceThrowing(\Throwable $e): ShipmentService
    {
        $client = $this->createMock(Client::class);
        $client->method('post')->willThrowException($e);

        return $this->configuredService($client);
    }

    private function configuredService(Client $client): ShipmentService
    {
        $address = new Address(
            name: 'John Doe',
            addressStreet: 'Musterstraße 123',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
        );
        $shipment = new Shipment(
            product: ShipmentProduct::DhlPacket,
            billingNumber: '33333333330101',
            referenceNo: 'reference-0001',
            shipper: $address,
            recipient: $address,
            package: new Package(height: 100, length: 100, width: 100, weight: 1000),
        );

        return (new ShipmentService($client))->setShipments([$shipment]);
    }
}
