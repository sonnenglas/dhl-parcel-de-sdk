<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonnenglas\DhlParcelDe\Enums\ReturnLabelType;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidArgumentException;
use Sonnenglas\DhlParcelDe\Exceptions\MissingArgumentException;
use Sonnenglas\DhlParcelDe\ReturnsClient;
use Sonnenglas\DhlParcelDe\ReturnsService;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\ReturnShipment;

class ReturnsServiceTest extends TestCase
{
    private ReturnsService $returnsService;

    /** @var ReturnsClient&MockObject */
    private MockObject $clientMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(ReturnsClient::class);
        $this->returnsService = new ReturnsService($this->clientMock);
    }

    public function testPrepareQueryBuildsPayloadWithRequiredFields(): void
    {
        $shipper = new Address(
            name: 'John Customer',
            addressStreet: 'Musterstraße 42',
            postalCode: '10115',
            city: 'Berlin',
            country: 'DE',
            email: 'john@example.com',
        );

        $shipment = new ReturnShipment(
            receiverId: 'deu',
            shipper: $shipper,
            customerReference: 'ORDER-12345',
            itemWeightKg: 1.5,
        );

        $query = $this->returnsService->prepareQuery($shipment);

        $this->assertSame('deu', $query['receiverId']);
        $this->assertSame('ORDER-12345', $query['customerReference']);
        $this->assertSame(['uom' => 'kg', 'value' => 1.5], $query['itemWeight']);
        $this->assertSame('Musterstraße 42', $query['shipper']['addressStreet']);
        $this->assertSame('DEU', $query['shipper']['country']);
        $this->assertSame('John Customer', $query['shipper']['name1']);
    }

    public function testReturnShipmentRejectsCustomerReferenceOver30Chars(): void
    {
        $shipper = new Address(
            name: 'John',
            addressStreet: 'Street 1',
            postalCode: '10115',
            city: 'Berlin',
            country: 'DE',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customerReference must be at most 30 characters');

        new ReturnShipment(
            receiverId: 'deu',
            shipper: $shipper,
            customerReference: str_repeat('X', 31),
        );
    }

    public function testCreateReturnParsesLabelPdfFromResponse(): void
    {
        $shipment = $this->makeShipment();
        $pdfBinary = '%PDF-1.4 test binary';
        $this->returnsService->setReturnShipment($shipment);

        $this->clientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(static fn (string $url): bool => str_contains($url, 'orders?labelType=SHIPMENT_LABEL')),
                $this->callback(static function (array $payload): bool {
                    return $payload['receiverId'] === 'deu'
                        && isset($payload['shipper']['addressStreet']);
                }),
            )
            ->willReturn([
                'shipmentNo' => '00340434201234567890',
                'internationalShipmentNo' => null,
                'label' => ['b64' => base64_encode($pdfBinary), 'fileFormat' => 'PDF'],
                'sstatus' => ['title' => 'Created', 'statusCode' => 201],
            ]);

        $response = $this->returnsService->createReturn();

        $this->assertSame('00340434201234567890', $response->shipmentNo);
        $this->assertSame($pdfBinary, $response->labelPdf);
        $this->assertNull($response->qrLabelPng);
        $this->assertSame(201, $response->statusCode);
    }

    public function testCreateReturnWithQrLabelTypeIncludesQrFields(): void
    {
        $shipment = $this->makeShipment();
        $pngBinary = "\x89PNG binary";
        $this->returnsService
            ->setReturnShipment($shipment)
            ->setLabelType(ReturnLabelType::QR_LABEL);

        $this->clientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(static fn (string $url): bool => str_contains($url, 'labelType=QR_LABEL')),
                $this->anything(),
            )
            ->willReturn([
                'shipmentNo' => '00340434209999999999',
                'qrLabel' => ['b64' => base64_encode($pngBinary), 'fileFormat' => 'PNG'],
                'qrLink' => 'https://www.dhl.de/qrcode/abc',
                'sstatus' => ['title' => 'Created', 'statusCode' => 201],
            ]);

        $response = $this->returnsService->createReturn();

        $this->assertSame($pngBinary, $response->qrLabelPng);
        $this->assertSame('https://www.dhl.de/qrcode/abc', $response->qrLink);
        $this->assertNull($response->labelPdf);
    }

    public function testCreateReturnThrowsWhenReturnShipmentNotSet(): void
    {
        $this->expectException(MissingArgumentException::class);

        $this->returnsService->createReturn();
    }

    protected function makeShipment(): ReturnShipment
    {
        return new ReturnShipment(
            receiverId: 'deu',
            shipper: new Address(
                name: 'John Customer',
                addressStreet: 'Musterstraße 42',
                postalCode: '10115',
                city: 'Berlin',
                country: 'DE',
            ),
            customerReference: 'REF-001',
            itemWeightKg: 1.0,
        );
    }
}
