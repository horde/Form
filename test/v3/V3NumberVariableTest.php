<?php

declare(strict_types=1);

namespace Horde\Form\Test\V3;

use Horde\Form\V3\NumberVariable;
use Horde\Nls\Nls;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NumberVariable::class)]
class V3NumberVariableTest extends TestCase
{
    public function testValidNumberPassesValidation(): void
    {
        $nls = new Nls();
        $var = new NumberVariable('Amount', 'amount', false, false, null, $nls);
        $var->init();

        $vars = new Horde_Variables(['amount' => '123.45']);
        $this->assertTrue($var->isValid($vars, '123.45'));
    }

    public function testEmptyOptionalFieldIsValid(): void
    {
        $nls = new Nls();
        $var = new NumberVariable('Amount', 'amount', false, false, null, $nls);
        $var->init();

        $vars = new Horde_Variables(['amount' => '']);
        $this->assertTrue($var->isValid($vars, ''));
    }

    public function testInvalidNumberFailsValidation(): void
    {
        $nls = new Nls();
        $var = new NumberVariable('Amount', 'amount', false, false, null, $nls);
        $var->init();

        $vars = new Horde_Variables(['amount' => 'abc']);
        $this->assertFalse($var->isValid($vars, 'abc'));
    }

    public function testGetInfoV3NormalizesDecimalSeparator(): void
    {
        $nls = new Nls();
        $var = new NumberVariable('Amount', 'amount', false, false, null, $nls);
        $var->init();

        $vars = new Horde_Variables(['amount' => '1234.56']);

        $method = new \ReflectionMethod($var, 'getInfoV3');
        $result = $method->invoke($var, $vars);

        $this->assertIsString($result);
        $this->assertStringContainsString('1234', $result);
    }

    public function testFractionParameterIsStored(): void
    {
        $nls = new Nls();
        $var = new NumberVariable('Amount', 'amount', false, false, null, $nls);
        $var->init(2);

        $this->assertEquals(2, $var->_fraction);
    }
}
