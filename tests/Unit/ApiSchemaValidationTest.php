<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;

/**
 * Test validation against DHL API schema constraints from parcel-de-shipping-v2.yaml
 */
class ApiSchemaValidationTest extends TestCase
{
    public function testLockerSchemaConstraintsFromApiYaml(): void
    {
        // Test schema constraints for Locker (Packstation) from the API spec:
        // lockerID: maximum: 999, minimum: 100, type: integer
        // postNumber: pattern: ^[0-9]{6,10}$
        // name: maxLength: 50, minLength: 1
        // city: maxLength: 40, minLength: 0 
        // postalCode: maxLength: 10, minLength: 3, pattern: ^[0-9A-Za-z]+([ -]?[0-9A-Za-z]+)*$

        $address = new Address(
            name: 'Paula Packstation', // Matches API example
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 118, // Matches API example range
            packstationCustomerNumber: '1234567890' // 10 digits, max allowed
        );

        $apiFormat = $address->toDhlApiFormat();

        // Verify format matches API Locker schema
        $this->assertArrayHasKey('name', $apiFormat);
        $this->assertArrayHasKey('lockerID', $apiFormat);
        $this->assertArrayHasKey('postNumber', $apiFormat);
        $this->assertArrayHasKey('city', $apiFormat);
        $this->assertArrayHasKey('postalCode', $apiFormat);
        $this->assertArrayHasKey('country', $apiFormat);

        // Verify data types and constraints
        $this->assertIsInt($apiFormat['lockerID']);
        $this->assertIsString($apiFormat['postNumber']);
        $this->assertGreaterThanOrEqual(100, $apiFormat['lockerID']);
        $this->assertLessThanOrEqual(999, $apiFormat['lockerID']);
        $this->assertMatchesRegularExpression('/^[0-9]{6,10}$/', $apiFormat['postNumber']);
        $this->assertLessThanOrEqual(50, strlen($apiFormat['name']));
        $this->assertLessThanOrEqual(40, strlen($apiFormat['city']));
        $this->assertLessThanOrEqual(10, strlen($apiFormat['postalCode']));
    }

    public function testContactAddressSchemaConstraintsFromApiYaml(): void
    {
        // Test schema constraints for ContactAddress from the API spec:
        // name1: maxLength: 50, minLength: 1
        // addressStreet: maxLength: 50, minLength: 1
        // city: maxLength: 40, minLength: 1
        // postalCode: maxLength: 10, minLength: 3, pattern: ^[0-9A-Za-z]+([ -]?[0-9A-Za-z]+)*$

        $address = new Address(
            name: 'Blumen Krause', // Matches API example
            addressStreet: 'Hauptstrasse', // Matches API example
            postalCode: '53113', // Matches API example
            city: 'Bonn', // Matches API example
            country: 'DE',
            email: 'max@mustermann.de'
        );

        $apiFormat = $address->toDhlApiFormat();

        // Verify format matches API ContactAddress schema
        $this->assertArrayHasKey('name1', $apiFormat);
        $this->assertArrayHasKey('addressStreet', $apiFormat);
        $this->assertArrayHasKey('city', $apiFormat);
        $this->assertArrayHasKey('postalCode', $apiFormat);
        $this->assertArrayHasKey('country', $apiFormat);
        $this->assertArrayHasKey('email', $apiFormat);

        // Verify data constraints
        $this->assertLessThanOrEqual(50, strlen($apiFormat['name1']));
        $this->assertLessThanOrEqual(50, strlen($apiFormat['addressStreet']));
        $this->assertLessThanOrEqual(40, strlen($apiFormat['city']));
        $this->assertLessThanOrEqual(10, strlen($apiFormat['postalCode']));
        $this->assertGreaterThanOrEqual(1, strlen($apiFormat['name1']));
        $this->assertGreaterThanOrEqual(1, strlen($apiFormat['addressStreet']));
        $this->assertGreaterThanOrEqual(1, strlen($apiFormat['city']));
    }

    public function testLockerCountryConstraintFromApiSchema(): void
    {
        // API schema states: "Only usable for German Packstation"
        // Our implementation should enforce this

        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation delivery is only available in Germany');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '75001',
            city: 'Paris',
            country: 'FR', // Not Germany - should fail
            packstationId: 118,
            packstationCustomerNumber: '123456'
        );
    }

    public function testLockerIdBoundariesFromApiSchema(): void
    {
        // API schema: lockerID minimum: 100, maximum: 999

        // Test minimum boundary
        $minAddress = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 100, // Minimum allowed
            packstationCustomerNumber: '123456'
        );
        $this->assertEquals(100, $minAddress->toDhlApiFormat()['lockerID']);

        // Test maximum boundary
        $maxAddress = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 999, // Maximum allowed
            packstationCustomerNumber: '123456'
        );
        $this->assertEquals(999, $maxAddress->toDhlApiFormat()['lockerID']);
    }

    public function testPostNumberPatternFromApiSchema(): void
    {
        // API schema: postNumber pattern: ^[0-9]{6,10}$

        // Test all valid lengths (6-10 digits)
        $validLengths = ['123456', '1234567', '12345678', '123456789', '1234567890'];

        foreach ($validLengths as $postNumber) {
            $address = new Address(
                name: 'Test User',
                addressStreet: '',
                postalCode: '12345',
                city: 'Berlin',
                country: 'DE',
                packstationId: 171,
                packstationCustomerNumber: $postNumber
            );

            $apiFormat = $address->toDhlApiFormat();
            $this->assertEquals($postNumber, $apiFormat['postNumber']);
            $this->assertMatchesRegularExpression('/^[0-9]{6,10}$/', $apiFormat['postNumber']);
        }
    }

    public function testNameFieldMappingFromApiSchema(): void
    {
        // API schema shows Locker uses 'name' field, ContactAddress uses 'name1' field

        // Test Packstation (Locker) format
        $packstationAddress = new Address(
            name: 'Paula Packstation',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 118,
            packstationCustomerNumber: '123456'
        );

        $lockerFormat = $packstationAddress->toDhlApiFormat();
        $this->assertArrayHasKey('name', $lockerFormat);
        $this->assertArrayNotHasKey('name1', $lockerFormat);
        $this->assertEquals('Paula Packstation', $lockerFormat['name']);

        // Test regular address (ContactAddress) format
        $regularAddress = new Address(
            name: 'Blumen Krause',
            addressStreet: 'Hauptstrasse 1',
            postalCode: '53113',
            city: 'Bonn',
            country: 'DE'
        );

        $contactFormat = $regularAddress->toDhlApiFormat();
        $this->assertArrayHasKey('name1', $contactFormat);
        $this->assertArrayNotHasKey('name', $contactFormat);
        $this->assertEquals('Blumen Krause', $contactFormat['name1']);
    }

    public function testRequiredFieldsFromApiSchema(): void
    {
        // API schema for Locker requires: city, lockerID, name, postNumber, postalCode
        $packstationAddress = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '123456'
        );

        $lockerFormat = $packstationAddress->toDhlApiFormat();

        // All required fields should be present
        $requiredFields = ['city', 'lockerID', 'name', 'postNumber', 'postalCode'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $lockerFormat);
            $this->assertNotEmpty($lockerFormat[$field]);
        }

        // API schema for ContactAddress requires: addressStreet, city, country, name1
        $regularAddress = new Address(
            name: 'Test User',
            addressStreet: 'Test Street 1',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
        );

        $contactFormat = $regularAddress->toDhlApiFormat();

        // All required fields should be present
        $requiredFields = ['addressStreet', 'city', 'country', 'name1'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $contactFormat);
            $this->assertNotEmpty($contactFormat[$field]);
        }
    }

    public function testCountryCodeFormatFromApiSchema(): void
    {
        // API uses ISO 3166-1 alpha-3 country codes (e.g., DEU, not DE)

        $address = new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE' // Input as alpha-2
        );

        $apiFormat = $address->toDhlApiFormat();
        $this->assertEquals('DEU', $apiFormat['country']); // Should be converted to alpha-3
    }
}
