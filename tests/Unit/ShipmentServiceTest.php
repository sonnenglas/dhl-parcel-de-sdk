<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sonnenglas\DhlParcelDe\ShipmentService;
use Sonnenglas\DhlParcelDe\Client;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;
use Sonnenglas\DhlParcelDe\Enums\LabelFormat;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use ReflectionClass;

class ShipmentServiceTest extends TestCase
{
    private ShipmentService $shipmentService;
    private MockObject $clientMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->shipmentService = new ShipmentService($this->clientMock);
    }

    public function testPrepareAddressQueryWithRegularAddress(): void
    {
        $address = new Address(
            name: 'John Doe',
            addressStreet: 'Musterstraße 123',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
            email: 'john@example.com'
        );

        $reflection = new ReflectionClass($this->shipmentService);
        $method = $reflection->getMethod('prepareAddressQuery');
        $method->setAccessible(true);

        $result = $method->invoke($this->shipmentService, $address);

        $expected = [
            'addressStreet' => 'Musterstraße 123',
            'postalCode' => '50667',
            'city' => 'Köln',
            'country' => 'DEU',
            'name1' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testPrepareAddressQueryWithPackstationAddress(): void
    {
        $address = new Address(
            name: 'Max Mustermann',
            addressStreet: '', // Will be ignored for packstation
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '1234567890'
        );

        $reflection = new ReflectionClass($this->shipmentService);
        $method = $reflection->getMethod('prepareAddressQuery');
        $method->setAccessible(true);

        $result = $method->invoke($this->shipmentService, $address);

        $expected = [
            'name' => 'Max Mustermann',
            'lockerID' => 171,
            'postNumber' => '1234567890',
            'city' => 'Köln',
            'postalCode' => '50667',
            'country' => 'DEU',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testPrepareShipmentsQueryWithPackstationRecipient(): void
    {
        $shipper = new Address(
            name: 'Company Inc',
            addressStreet: 'Business St 1',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
        );

        $recipient = new Address(
            name: 'Max Mustermann',
            addressStreet: '',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '1234567890'
        );

        $package = new Package(
            height: 200,
            length: 300,
            width: 150,
            weight: 1000
        );

        $shipment = new Shipment(
            product: ShipmentProduct::DhlPacket,
            billingNumber: '33333333330102',
            referenceNo: 'TEST123456789',
            shipper: $shipper,
            recipient: $recipient,
            package: $package
        );

        $this->shipmentService->setShipments([$shipment]);

        $reflection = new ReflectionClass($this->shipmentService);
        $method = $reflection->getMethod('prepareShipmentsQuery');
        $method->setAccessible(true);

        $result = $method->invoke($this->shipmentService);

        // Check that the result contains one shipment
        $this->assertCount(1, $result);

        $shipmentData = $result[0];

        // Check that consignee uses Locker format
        $expectedConsignee = [
            'name' => 'Max Mustermann',
            'lockerID' => 171,
            'postNumber' => '1234567890',
            'city' => 'Köln',
            'postalCode' => '50667',
            'country' => 'DEU',
        ];

        $this->assertEquals($expectedConsignee, $shipmentData['consignee']);

        // Check that shipper uses ContactAddress format
        $expectedShipper = [
            'addressStreet' => 'Business St 1',
            'postalCode' => '12345',
            'city' => 'Berlin',
            'country' => 'DEU',
            'name1' => 'Company Inc',
        ];

        $this->assertEquals($expectedShipper, $shipmentData['shipper']);
    }

    public function testPrepareShipmentsQueryWithRegularAddresses(): void
    {
        $shipper = new Address(
            name: 'Company Inc',
            addressStreet: 'Business St 1',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
        );

        $recipient = new Address(
            name: 'John Customer',
            addressStreet: 'Customer Ave 42',
            postalCode: '54321',
            city: 'Hamburg',
            country: 'DE',
            email: 'john@customer.com'
        );

        $package = new Package(
            height: 200,
            length: 300,
            width: 150,
            weight: 1000
        );

        $shipment = new Shipment(
            product: ShipmentProduct::DhlPacket,
            billingNumber: '33333333330102',
            referenceNo: 'TEST123456789',
            shipper: $shipper,
            recipient: $recipient,
            package: $package
        );

        $this->shipmentService->setShipments([$shipment]);

        $reflection = new ReflectionClass($this->shipmentService);
        $method = $reflection->getMethod('prepareShipmentsQuery');
        $method->setAccessible(true);

        $result = $method->invoke($this->shipmentService);

        // Check that the result contains one shipment
        $this->assertCount(1, $result);

        $shipmentData = $result[0];

        // Check that both addresses use ContactAddress format
        $expectedConsignee = [
            'addressStreet' => 'Customer Ave 42',
            'postalCode' => '54321',
            'city' => 'Hamburg',
            'country' => 'DEU',
            'name1' => 'John Customer',
            'email' => 'john@customer.com',
        ];

        $this->assertEquals($expectedConsignee, $shipmentData['consignee']);

        $expectedShipper = [
            'addressStreet' => 'Business St 1',
            'postalCode' => '12345',
            'city' => 'Berlin',
            'country' => 'DEU',
            'name1' => 'Company Inc',
        ];

        $this->assertEquals($expectedShipper, $shipmentData['shipper']);
    }

    public function testPrepareQueryStructure(): void
    {
        $shipper = new Address(
            name: 'Test Shipper',
            addressStreet: 'Shipper St 1',
            postalCode: '11111',
            city: 'City1',
            country: 'DE'
        );

        $recipient = new Address(
            name: 'Test Recipient',
            addressStreet: '',
            postalCode: '22222',
            city: 'City2',
            country: 'DE',
            packstationId: 123,
            packstationCustomerNumber: '987654321'
        );

        $package = new Package(100, 200, 300, 500);

        $shipment = new Shipment(
            product: ShipmentProduct::DhlPacket,
            billingNumber: '33333333330102',
            referenceNo: 'REF12345678',
            shipper: $shipper,
            recipient: $recipient,
            package: $package
        );

        $this->shipmentService->setShipments([$shipment]);

        $query = $this->shipmentService->prepareQuery();

        // Check overall structure
        $this->assertArrayHasKey('profile', $query);
        $this->assertArrayHasKey('shipments', $query);
        $this->assertEquals('STANDARD_GRUPPENPROFIL', $query['profile']);

        // Check shipment structure
        $shipmentData = $query['shipments'][0];
        $this->assertEquals('V01PAK', $shipmentData['product']);
        $this->assertEquals('33333333330102', $shipmentData['billingNumber']);
        $this->assertEquals('REF12345678', $shipmentData['refNo']);

        // Verify packstation format is used for recipient
        $this->assertArrayHasKey('lockerID', $shipmentData['consignee']);
        $this->assertArrayHasKey('postNumber', $shipmentData['consignee']);
        $this->assertArrayNotHasKey('addressStreet', $shipmentData['consignee']);

        // Verify contact address format is used for shipper
        $this->assertArrayHasKey('addressStreet', $shipmentData['shipper']);
        $this->assertArrayNotHasKey('lockerID', $shipmentData['shipper']);
        $this->assertArrayNotHasKey('postNumber', $shipmentData['shipper']);
    }

    public function testValidateShipmentSendsValidateQuery(): void
    {
        $shipment = $this->createTestShipment();
        $this->shipmentService->setShipments([$shipment]);

        $this->clientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function (string $url) {
                    return str_contains($url, '?validate=true');
                }),
                $this->anything()
            )
            ->willReturn([
                'status' => ['statusCode' => 200],
            ]);

        $result = $this->shipmentService->validateShipment();

        $this->assertTrue($result);
    }

    public function testValidateShipmentWithMustEncode(): void
    {
        $shipment = $this->createTestShipment();
        $this->shipmentService->setShipments([$shipment]);

        $this->clientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function (string $url) {
                    return str_contains($url, 'validate=true')
                        && str_contains($url, 'mustEncode=true');
                }),
                $this->anything()
            )
            ->willReturn([
                'status' => ['statusCode' => 200],
            ]);

        $result = $this->shipmentService->validateShipment(mustEncode: true);

        $this->assertTrue($result);
    }

    public function testValidateShipmentWithLabelFormat(): void
    {
        $shipment = $this->createTestShipment();
        $this->shipmentService
            ->setShipments([$shipment])
            ->setLabelFormat(LabelFormat::FORMAT_910_300_400);

        $this->clientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function (string $url) {
                    return str_contains($url, 'validate=true')
                        && str_contains($url, 'printFormat=');
                }),
                $this->anything()
            )
            ->willReturn([
                'status' => ['statusCode' => 200],
            ]);

        $result = $this->shipmentService->validateShipment();

        $this->assertTrue($result);
    }

    public function testValidateShipmentReturnsFalseOnNon200Status(): void
    {
        $shipment = $this->createTestShipment();
        $this->shipmentService->setShipments([$shipment]);

        $this->clientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn([
                'status' => ['statusCode' => 400],
            ]);

        $result = $this->shipmentService->validateShipment();

        $this->assertFalse($result);
    }

    protected function createTestShipment(): Shipment
    {
        $shipper = new Address(
            name: 'Company Inc',
            addressStreet: 'Business St 1',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
        );

        $recipient = new Address(
            name: 'John Customer',
            addressStreet: 'Customer Ave 42',
            postalCode: '54321',
            city: 'Hamburg',
            country: 'DE',
        );

        $package = new Package(200, 300, 150, 1000);

        return new Shipment(
            product: ShipmentProduct::DhlPacket,
            billingNumber: '33333333330102',
            referenceNo: 'TEST123456789',
            shipper: $shipper,
            recipient: $recipient,
            package: $package
        );
    }
}
