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
$key = 'Wazx8wwmYRdC26f7OLqYni5GGGRjF6g4';

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
    email: 'info@sonnenglas.net',
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
    email: 'przemek@redkorn.pl',
    phone: '792477888',
    additionalInfo: '',
);


$package = new Package(
    height: 200,
    length: 200,
    width: 400,
    weight: 4000,
);

$shipment = new Shipment(
    product: ShipmentProduct::DhlEuropaket,
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
    var_dump('END');
}

echo "Result:";
var_dump($shipment);