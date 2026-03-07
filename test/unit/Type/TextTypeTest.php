<?php

/**
 * Comprehensive tests for common Horde_Form_Type classes.
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 * @author     Ralf Lang <lang@b1-systems.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */

namespace Horde\Form\Test\Unit\Type;

use Horde_Form;
use Horde_Form_Type_text;
use Horde_Form_Variable;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Form_Type_text class.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(Horde_Form_Type_text::class)]
class TextTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $type = new Horde_Form_Type_text();

        $this->assertInstanceOf(Horde_Form_Type_text::class, $type);
    }

    public function testGetTypeName(): void
    {
        $type = new Horde_Form_Type_text();

        $this->assertEquals('text', $type->getTypeName());
    }

    public function testInitWithNoParameters(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init();

        // Should not throw exception
        $this->assertInstanceOf(Horde_Form_Type_text::class, $type);
    }

    public function testInitWithSizeParameter(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init(40);

        // Size is set internally, tested via validation
        $this->assertInstanceOf(Horde_Form_Type_text::class, $type);
    }

    public function testInitWithSizeAndMaxLength(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init(40, 255);

        $this->assertInstanceOf(Horde_Form_Type_text::class, $type);
    }

    public function testIsValidReturnsTrueForValidValue(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init();

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, 'valid text', $message = '');

        $this->assertTrue($result);
    }

    public function testIsValidReturnsFalseForRequiredFieldEmpty(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init();

        $var = $this->createMockVariable(true);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, '', $message = '');

        $this->assertFalse($result);
    }

    public function testIsValidAllowsZeroForRequiredField(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init();

        $var = $this->createMockVariable(true);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, '0', $message = '');

        $this->assertTrue($result);
    }

    public function testIsValidReturnsFalseWhenExceedsMaxLength(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init(40, 10);  // Max 10 characters

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, 'this is too long', $message = '');

        $this->assertFalse($result);
    }

    public function testIsValidReturnsTrueWhenWithinMaxLength(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init(40, 20);  // Max 20 characters

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, 'short', $message = '');

        $this->assertTrue($result);
    }

    public function testIsValidWithRegex(): void
    {
        $type = new Horde_Form_Type_text();
        $type->init(40, null);
        // Access protected property via reflection for testing
        $reflection = new \ReflectionClass($type);
        $regexProp = $reflection->getProperty('_regex');
        $regexProp->setAccessible(true);
        $regexProp->setValue($type, '/^[0-9]+$/');

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $this->assertTrue($type->isValid($var, $vars, '12345', $message = ''));
        $this->assertFalse($type->isValid($var, $vars, 'abc', $message = ''));
    }

    private function createMockVariable(bool $required): Horde_Form_Variable
    {
        $type = new Horde_Form_Type_text();
        return new Horde_Form_Variable('Test', 'test', $type, $required);
    }
}
