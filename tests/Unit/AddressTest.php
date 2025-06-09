<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sonnenglas\DhlParcelDe\ValueObjects\Address;
use Sonnenglas\DhlParcelDe\Exceptions\InvalidAddressException;

class AddressTest extends TestCase
{
    public function testRegularAddressCreation(): void
    {
        $address = new Address(
            name: 'John Doe',
            addressStreet: 'Musterstraße 123',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE'
        );

        $this->assertFalse($address->isPackstation());
        $this->assertEquals('DEU', $address->getCountry());
    }

    public function testPackstationAddressCreation(): void
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

        $this->assertTrue($address->isPackstation());
        $this->assertEquals('DEU', $address->getCountry());
    }

    public function testPackstationValidPackstationId(): void
    {
        // Test minimum valid ID (100)
        $address = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 100,
            packstationCustomerNumber: '123456'
        );
        $this->assertTrue($address->isPackstation());

        // Test maximum valid ID (999)
        $address = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 999,
            packstationCustomerNumber: '123456'
        );
        $this->assertTrue($address->isPackstation());
    }

    public function testPackstationInvalidPackstationIdTooLow(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation ID must be a 3-digit number between 100 and 999.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 99, // Too low
            packstationCustomerNumber: '123456'
        );
    }

    public function testPackstationInvalidPackstationIdTooHigh(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation ID must be a 3-digit number between 100 and 999.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 1000, // Too high
            packstationCustomerNumber: '123456'
        );
    }

    public function testPackstationValidCustomerNumbers(): void
    {
        // Test minimum length (6 digits)
        $address = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '123456'
        );
        $this->assertTrue($address->isPackstation());

        // Test maximum length (10 digits)
        $address = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '1234567890'
        );
        $this->assertTrue($address->isPackstation());

        // Test with leading zeros
        $address = new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '0001234567'
        );
        $this->assertTrue($address->isPackstation());
    }

    public function testPackstationInvalidCustomerNumberTooShort(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation customer number must be 6-10 digits.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '12345' // Too short
        );
    }

    public function testPackstationInvalidCustomerNumberTooLong(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation customer number must be 6-10 digits.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '12345678901' // Too long
        );
    }

    public function testPackstationInvalidCustomerNumberNonNumeric(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation customer number must be 6-10 digits.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '12345a' // Contains letter
        );
    }

    public function testPackstationRequiresBothFields(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Both packstationId and packstationCustomerNumber must be provided for packstation delivery.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171 // Missing customer number
        );
    }

    public function testPackstationRequiresBothFieldsReverse(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Both packstationId and packstationCustomerNumber must be provided for packstation delivery.');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationCustomerNumber: '123456' // Missing packstation ID
        );
    }

    public function testPackstationOnlyInGermany(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Packstation delivery is only available in Germany (country must be DE).');

        new Address(
            name: 'Test User',
            addressStreet: '',
            postalCode: '75001',
            city: 'Paris',
            country: 'FR', // Not Germany
            packstationId: 171,
            packstationCustomerNumber: '123456'
        );
    }

    public function testRegularAddressRequiresStreet(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Address Street is required for regular addresses.');

        new Address(
            name: 'Test User',
            addressStreet: '', // Empty street for regular address
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
            // No packstation fields, so it's a regular address
        );
    }

    public function testPackstationDoesNotRequireStreet(): void
    {
        // This should NOT throw an exception
        $address = new Address(
            name: 'Test User',
            addressStreet: '', // Empty street is OK for packstation
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '123456'
        );

        $this->assertTrue($address->isPackstation());
    }

    public function testRegularAddressApiFormat(): void
    {
        $address = new Address(
            name: 'John Doe',
            addressStreet: 'Musterstraße 123',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
            email: 'john@example.com',
            phone: '+49123456789',
            additionalInfo: '2nd floor'
        );

        $apiFormat = $address->toDhlApiFormat();

        $this->assertEquals([
            'addressStreet' => 'Musterstraße 123',
            'postalCode' => '50667',
            'city' => 'Köln',
            'country' => 'DEU',
            'name1' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+49123456789',
            'additionalAddressInformation1' => '2nd floor'
        ], $apiFormat);
    }

    public function testRegularAddressWithCompanyApiFormat(): void
    {
        $address = new Address(
            name: 'John Doe',
            addressStreet: 'Musterstraße 123',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
            company: 'ACME Corp'
        );

        $apiFormat = $address->toDhlApiFormat();

        $this->assertEquals([
            'addressStreet' => 'Musterstraße 123',
            'postalCode' => '50667',
            'city' => 'Köln',
            'country' => 'DEU',
            'name1' => 'ACME Corp',
            'name2' => 'John Doe'
        ], $apiFormat);
    }

    public function testPackstationApiFormat(): void
    {
        $address = new Address(
            name: 'Max Mustermann',
            addressStreet: 'This will be ignored',
            postalCode: '50667',
            city: 'Köln',
            country: 'DE',
            packstationId: 171,
            packstationCustomerNumber: '1234567890'
        );

        $apiFormat = $address->toDhlApiFormat();

        $this->assertEquals([
            'name' => 'Max Mustermann',
            'lockerID' => 171,
            'postNumber' => '1234567890',
            'city' => 'Köln',
            'postalCode' => '50667',
            'country' => 'DEU'
        ], $apiFormat);
    }

    public function testRequiredFieldValidation(): void
    {
        // Test empty name
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Name is required.');

        new Address(
            name: '', // Empty name
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
        );
    }

    public function testCityRequiredValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('City is required.');

        new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: '', // Empty city
            country: 'DE'
        );
    }

    public function testPostalCodeRequiredValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Postal code is required.');

        new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '', // Empty postal code
            city: 'Berlin',
            country: 'DE'
        );
    }

    public function testCountryValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Country Code must be 2 characters long (according to ISO 3166-1 alpha-2 format). Entered: DEU');

        new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DEU' // Should be 2 characters
        );
    }

    public function testCountryUpperCaseValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Country Code must be in upper-case. Entered: de');

        new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'de' // Should be uppercase
        );
    }

    public function testFieldLengthValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Name must not be longer than 50 characters.');

        new Address(
            name: str_repeat('A', 51), // Too long
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE'
        );
    }

    public function testAdditionalInfoLengthValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Additional info must not be longer than 60 characters.');

        new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            additionalInfo: str_repeat('A', 61) // Too long
        );
    }

    public function testCompanyLengthValidation(): void
    {
        $this->expectException(InvalidAddressException::class);
        $this->expectExceptionMessage('Company name must not be longer than 50 characters.');

        new Address(
            name: 'Test User',
            addressStreet: 'Test Street',
            postalCode: '12345',
            city: 'Berlin',
            country: 'DE',
            company: str_repeat('A', 51) // Too long
        );
    }
}
