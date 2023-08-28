<?php

declare(strict_types=1);

require_once('./vendor/autoload.php');

use Sonnenglas\DhlParcelDe\Dhl;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;

$user = 'sandy_sandbox';
$pass = 'pass';
$key = 'fjklashdfljkahsfkljhsafkljh';

$productionMode = false;

$dhl = new Dhl($user, $pass, $key, $productionMode);

$shipmentService = $dhl->getShipmentService();

$shipper = new Address(
    name: 'Sonnenglas GmbH',
    addressStreet: 'Ünnern Diek 62',
    postalCode: '25724',
    city: 'Neufeld',
    country: 'DE',
    state: '',
    email: 'info@test.com',
    phone: '',
    additionalInfo: '',
);

$recipient = new Address(
    name: 'Przemyslaw Peron',
    addressStreet: 'Deulowitzer Str. 31 B',
    postalCode: '03172',
    city: 'Guben',
    country: 'DE',
    state: '',
    email: 'przemek@test.com',
    phone: '792477112',
    additionalInfo: '',
);


$package = new Package(
    height: 200,
    length: 200,
    width: 400,
    weight: 4000,
);

$shipment = new Shipment(
    product: ShipmentProduct::DhlPacket,
    billingNumber: '33333333330102',
    referenceNo: '123456789',
    shipper: $shipper,
    recipient: $recipient,
    package: $package,
);

try {
    $shipment = $shipmentService->setShipments([$shipment])
    ->createShipment();
} catch (Exception $e) {
    echo $shipmentService->getLastErrorResponse();
}

echo "Result:";
var_dump($shipment);