# DHL Parcel DE SDK

A PHP SDK for interacting with the DHL Parcel DE Shipping API (Post & Parcel Germany). This package allows you to easily create shipments, print labels, and manage shipments through the DHL API.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

## Installation

You can install the package via composer:

```bash
composer require sonnenglas/dhl-parcel-de-sdk
```

## Requirements

- PHP 8.0 or higher
- Composer
- DHL API credentials (username, password, and API key)

## Features

- Create shipments and generate labels
- Delete shipments
- Support for various DHL products (DHL Paket, DHL Paket International, etc.)
- Address validation
- Custom package dimensions and weight
- Support for production and sandbox environments

## Usage

### Initialization

```php
use Sonnenglas\DhlParcelDe\Dhl;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;

// Initialize with your DHL API credentials
$dhl = new Dhl(
    username: 'your_username',
    password: 'your_password',
    apiKey: 'your_api_key',
    productionMode: false // Set to true for production
);

$shipmentService = $dhl->getShipmentService();
```

### Creating a Shipment

```php
// 1. Create the shipper address
$shipper = new Address(
    name: 'Your Company Name',
    addressStreet: 'Your Street 123',
    postalCode: '12345',
    city: 'Your City',
    country: 'DE',
    email: 'info@example.com'
);

// 2. Create the recipient address
$recipient = new Address(
    name: 'John Doe',
    addressStreet: 'Customer Street 456',
    postalCode: '54321',
    city: 'Customer City',
    country: 'DE',
    email: 'customer@example.com',
    phone: '123456789'
);

// 3. Define the package details
$package = new Package(
    height: 200, // in mm
    length: 300, // in mm
    width: 400,  // in mm
    weight: 2000 // in g
);

// 4. Create the shipment object
$shipment = new Shipment(
    product: ShipmentProduct::DhlPacket,
    billingNumber: '33333333330102', // Your DHL billing number
    referenceNo: '123456789',        // Your reference number (min 8 characters)
    shipper: $shipper,
    recipient: $recipient,
    package: $package
);

// 5. Send the shipment to DHL
try {
    $response = $shipmentService
        ->setShipments([$shipment])
        ->createShipment();
    
    // The shipment was created successfully
    $shipmentNumber = $response->shipmentNumber;
    $labelUrl = $response->labelUrl;
    
    echo "Shipment created with number: " . $shipmentNumber;
    echo "Label URL: " . $labelUrl;
} catch (Exception $e) {
    // Handle any errors
    echo "Error: " . $e->getMessage();
    echo "API Error Response: " . $shipmentService->getLastErrorResponse();
}
```

### DHL Packstation Support

The SDK supports DHL Packstation deliveries, allowing customers to receive packages at automated parcel stations throughout Germany.

#### What is a Packstation?

DHL Packstations are automated parcel stations where customers can receive and send packages 24/7. They are located throughout Germany and provide a convenient alternative to home delivery.

#### Requirements for Packstation Delivery

To send a package to a DHL Packstation, you need:

1. **Packstation ID** - A 3-digit number (100-999) identifying the specific packstation
2. **Customer's DHL Account Number (Postnummer)** - A 6-10 digit number that customers receive when they register for DHL services
3. **Packstation Location** - The postal code and city where the packstation is located
4. **Customer Name** - The name of the person who will collect the package

#### Creating a Packstation Address

```php
$packstationAddress = new Address(
    name: 'Max Mustermann',                    // Customer name
    addressStreet: '',                         // Ignored for packstation
    postalCode: '50667',                       // Packstation postal code
    city: 'Köln',                            // Packstation city
    country: 'DE',                            // Must be 'DE' for packstations
    state: '',
    email: 'max.mustermann@example.com',      // Optional
    phone: '',                                // Optional
    additionalInfo: '',                       // Ignored for packstation
    company: '',                              // Must be empty for packstation
    packstationId: 171,                       // 3-digit packstation number
    packstationCustomerNumber: '1234567890'   // Customer's DHL account number
);
```

#### Complete Packstation Shipment Example

```php
use Sonnenglas\DhlParcelDe\Dhl;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;

// Initialize DHL client
$dhl = new Dhl($username, $password, $apiKey, $productionMode);
$shipmentService = $dhl->getShipmentService();

// Sender address
$shipper = new Address(
    name: 'Your Company',
    addressStreet: 'Your Street 123',
    postalCode: '12345',
    city: 'Your City',
    country: 'DE'
);

// Packstation recipient
$recipient = new Address(
    name: 'Max Mustermann',
    addressStreet: '',                         // Will be ignored
    postalCode: '50667',                       // Packstation location
    city: 'Köln',                            // Packstation city
    country: 'DE',                           // Must be DE
    packstationId: 171,                       // 3-digit packstation number
    packstationCustomerNumber: '1234567890'   // Customer's DHL account number
);

// Package details
$package = new Package(
    height: 200,    // mm
    length: 300,    // mm
    width: 150,     // mm
    weight: 1000    // grams
);

// Create shipment
$shipment = new Shipment(
    product: ShipmentProduct::DhlPacket,
    billingNumber: '33333333330102',
    referenceNo: 'REF123456789',
    shipper: $shipper,
    recipient: $recipient,
    package: $package
);

// Send shipment
try {
    $response = $shipmentService->setShipments([$shipment])->createShipment();
    echo "Packstation shipment created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### Packstation Validation Rules

The SDK validates packstation addresses according to DHL requirements:

- **Packstation ID**: Must be a 3-digit integer between 100 and 999
- **Customer Number**: Must be 6-10 digits (string)
- **Country**: Must be 'DE' (packstations are only available in Germany)
- **Both Fields Required**: If you provide one packstation field, you must provide both
- **Company Field**: Must be empty for packstation deliveries (private customers only)

#### API Format

The SDK automatically converts packstation addresses to the correct DHL API format:

**Packstation Address (Locker format):**
```json
{
    "name": "Max Mustermann",
    "lockerID": 171,
    "postNumber": "1234567890",
    "city": "Köln",
    "postalCode": "50667",
    "country": "DEU"
}
```

**Regular Address (ContactAddress format):**
```json
{
    "name1": "John Doe",
    "addressStreet": "Musterstraße 123",
    "postalCode": "50667",
    "city": "Köln",
    "country": "DEU"
}
```

#### Finding Packstations

Customers can find nearby packstations using:
- DHL website: https://www.dhl.de/de/privatkunden/pakete-empfangen/an-einem-abholort-empfangen/packstation-finden.html
- DHL mobile app
- DHL customer portal

#### Customer Registration

Customers need to register for DHL services to get their Postnummer (customer number):
- Online at: https://www.dhl.de/de/privatkunden/kundenservice/dhl-kundenkonto.html
- At DHL retail locations
- Through the DHL mobile app

The Postnummer is required for all packstation deliveries and is unique to each customer.

### Deleting a Shipment

```php
try {
    $success = $shipmentService->deleteShipment('1234567890');
    
    if ($success) {
        echo "Shipment deleted successfully";
    } else {
        echo "Failed to delete shipment";
        echo "Error: " . $shipmentService->getLastErrorResponse();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Using a Custom Profile

```php
$shipmentService->setProfile('CUSTOM_PROFILE');
```

### Setting Label Format

```php
use Sonnenglas\DhlParcelDe\Enums\LabelFormat;

$shipmentService->setLabelFormat(LabelFormat::A4);
```

## Complete Example

Here's a complete example showing how to use the SDK to create a shipment:

```php
<?php

declare(strict_types=1);

require_once('./vendor/autoload.php');

use Sonnenglas\DhlParcelDe\Dhl;
use Sonnenglas\DhlParcelDe\Enums\ShipmentProduct;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\ValueObjects\Shipment;
use Sonnenglas\DhlParcelDe\ValueObjects\Package;

$user = 'your_api_username';
$pass = 'your_api_password';
$key = 'your_api_key_here';

$productionMode = false;

$dhl = new Dhl($user, $pass, $key, $productionMode);

$shipmentService = $dhl->getShipmentService();

$shipper = new Address(
    name: 'Stellar Widgets GmbH',
    addressStreet: 'Industrieweg 42',
    postalCode: '28195',
    city: 'Bremen',
    country: 'DE',
    state: '',
    email: 'info@stellarwidgets.example',
    phone: '',
    additionalInfo: '',
);

$recipient = new Address(
    name: 'Anna Schmidt',
    addressStreet: 'Rosenstraße 67',
    postalCode: '10115',
    city: 'Berlin',
    country: 'DE',
    state: '',
    email: 'anna.schmidt@example.com',
    phone: '01761234567',
    additionalInfo: 'Second Floor',
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
    referenceNo: 'ORD987654321',
    shipper: $shipper,
    recipient: $recipient,
    package: $package,
);

try {
    $response = $shipmentService->setShipments([$shipment])
        ->createShipment();
    
    echo "Result:";
    var_dump($response);
} catch (Exception $e) {
    echo $shipmentService->getLastErrorResponse();
}
```

## Available Products

The SDK supports the following DHL products:

- `ShipmentProduct::DhlPacket` - DHL Paket (V01PAK)
- `ShipmentProduct::DhlPacketInternational` - DHL Paket International (V53WPAK)
- `ShipmentProduct::DhlEuropaket` - DHL Europaket (V54EPAK)
- `ShipmentProduct::WarenPost` - Warenpost (V62WP)
- `ShipmentProduct::DhlKleinpaket` - DHL Kleinpaket (V62KP)
- `ShipmentProduct::WarenPostInternational` - Warenpost International (V66WPI)

## Error Handling

The SDK provides several ways to handle errors:

1. Exceptions are thrown for critical errors
2. The `getLastErrorResponse()` method provides detailed error messages from the DHL API
3. The `getLastRawResponse()` method returns the complete API response for debugging

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information. 