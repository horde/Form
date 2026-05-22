<?php

declare(strict_types=1);

namespace Horde\Form\Test\V3;

use Horde\Form\V3\AddressVariable;
use Horde\Nls\Nls;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddressVariable::class)]
class V3AddressVariableTest extends TestCase
{
    private AddressVariable $var;

    protected function setUp(): void
    {
        $nls = new Nls();
        $this->var = new AddressVariable('Address', 'address', false, false, null, $nls);
    }

    public function testParseUsAddress(): void
    {
        $address = "123 Main Street\nSpringfield, IL 62701";
        $result = $this->var->parse($address);

        $this->assertArrayHasKey('country', $result);
        $this->assertEquals('us', $result['country']);
        $this->assertEquals('123 Main Street', $result['street']);
        $this->assertEquals('Springfield', $result['city']);
        $this->assertEquals('IL', $result['state']);
        $this->assertEquals('62701', $result['zip']);
    }

    public function testParseCanadianAddress(): void
    {
        $address = "456 Maple Ave\nToronto, ON K1A 0B1";
        $result = $this->var->parse($address);

        $this->assertArrayHasKey('country', $result);
        $this->assertEquals('ca', $result['country']);
    }

    public function testParseUkPostcode(): void
    {
        $address = "10 Downing Street\nLondon SW1A 2AA";
        $result = $this->var->parse($address);

        $this->assertArrayHasKey('country', $result);
        $this->assertEquals('uk', $result['country']);
        $this->assertEquals('SW1A 2AA', $result['zip']);
    }

    public function testParseEuropeanAddressWithCarsign(): void
    {
        $address = "Hauptstraße 1\nD-10115 Berlin";
        $result = $this->var->parse($address);

        $this->assertArrayHasKey('zip', $result);
        $this->assertEquals('10115', $result['zip']);
        $this->assertEquals('Berlin', $result['city']);
        $this->assertArrayHasKey('country', $result);
    }

    public function testParseAustralianAddress(): void
    {
        $address = "42 Wallaby Way\nSydney, NSW 2000";
        $result = $this->var->parse($address);

        $this->assertArrayHasKey('country', $result);
        $this->assertEquals('au', $result['country']);
        $this->assertEquals('NSW', $result['state']);
        $this->assertEquals('2000', $result['zip']);
    }

    public function testParseEmptyAddressReturnsEmptyArray(): void
    {
        $result = $this->var->parse('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
