<?php

/**
 * Tests for Horde_Form_Type_enum class.
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

use Horde_Form_Type_enum;
use Horde_Form_Variable;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Form_Type_enum class.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(Horde_Form_Type_enum::class)]
class EnumTypeTest extends TestCase
{
    public function testGetTypeName(): void
    {
        $type = new Horde_Form_Type_enum();

        $this->assertEquals('enum', $type->getTypeName());
    }

    public function testInitWithArrayOfValues(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2', 'opt3' => 'Option 3'];

        $type->init($values);

        $this->assertEquals($values, $type->getValues());
    }

    public function testInitWithPromptTrue(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1'];

        $type->init($values, true);

        $prompt = $type->getPrompt();
        $this->assertNotEmpty($prompt);
        $this->assertIsString($prompt);
    }

    public function testInitWithCustomPrompt(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1'];

        $type->init($values, 'Please select an option');

        $this->assertEquals('Please select an option', $type->getPrompt());
    }

    public function testInitWithNoPrompt(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1'];

        $type->init($values, false);

        $this->assertFalse($type->getPrompt());
    }

    public function testIsValidReturnsTrueForValidValue(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2'];
        $type->init($values);

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, 'opt1', $message = '');

        $this->assertTrue($result);
    }

    public function testIsValidReturnsFalseForInvalidValue(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2'];
        $type->init($values);

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, 'invalid_option', $message = '');

        $this->assertFalse($result);
    }

    public function testIsValidReturnsFalseForRequiredEmpty(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2'];
        $type->init($values);

        $var = $this->createMockVariable(true);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, '', $message = '');

        $this->assertFalse($result);
    }

    public function testIsValidReturnsTrueForEmptyWithPrompt(): void
    {
        $type = new Horde_Form_Type_enum();
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2'];
        $type->init($values, true);  // Has prompt

        $var = $this->createMockVariable(false);  // Not required
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, '', $message = '');

        $this->assertTrue($result, 'Empty value should be valid when field has prompt and is not required');
    }

    public function testIsValidReturnsTrueForEmptyValues(): void
    {
        $type = new Horde_Form_Type_enum();
        $type->init([]);  // Empty values array

        $var = $this->createMockVariable(false);
        $vars = new Horde_Variables();

        $result = $type->isValid($var, $vars, 'anything', $message = '');

        $this->assertTrue($result, 'Should be valid when values array is empty');
    }

    public function testSetValues(): void
    {
        $type = new Horde_Form_Type_enum();
        $values1 = ['opt1' => 'Option 1'];
        $type->init($values1);

        $values2 = ['opt2' => 'Option 2', 'opt3' => 'Option 3'];
        $type->setValues($values2);

        $this->assertEquals($values2, $type->getValues());
    }

    private function createMockVariable(bool $required): Horde_Form_Variable
    {
        $type = new Horde_Form_Type_enum();
        return new Horde_Form_Variable('Test', 'test', $type, $required);
    }
}
