<?php

declare(strict_types=1);

namespace Horde\Form\Test\V3;

use Horde\Form\V3\CountryVariable;
use Horde\Nls\Nls;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CountryVariable::class)]
class V3CountryVariableTest extends TestCase
{
    public function testInitPopulatesCountryValues(): void
    {
        $nls = new Nls();
        $var = new CountryVariable('Country', 'country', false, false, null, $nls);
        $var->init();

        $values = $var->getValues();

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
        $this->assertArrayHasKey('DE', $values);
        $this->assertArrayHasKey('US', $values);
        $this->assertArrayHasKey('FR', $values);
    }

    public function testInitWithPrompt(): void
    {
        $nls = new Nls();
        $var = new CountryVariable('Country', 'country', false, false, null, $nls);
        $var->init('Select a country:');

        $this->assertEquals('Select a country:', $var->getPrompt());
    }

    public function testDefaultNlsInstanceUsedWhenNoneInjected(): void
    {
        $var = new CountryVariable('Country', 'country', false);
        $var->init();

        $values = $var->getValues();

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);
        $this->assertArrayHasKey('DE', $values);
    }
}
