<?php

/**
 * Tests for Horde\Form\V3\EnumVariable type.
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

namespace Horde\Form\Test\V3;

use Horde\Form\V3\EnumVariable;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Horde\Form\V3\EnumVariable (dropdown/select field).
 *
 * In V3, EnumVariable combines both Type and Variable functionality.
 * Compare with lib/Horde/Form/Type.php Horde_Form_Type_enum.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(EnumVariable::class)]
class V3EnumVariableTest extends TestCase
{
    // ========================================================================
    // Type Name Test
    // ========================================================================

    public function testGetTypeName(): void
    {
        $var = new EnumVariable('Category', 'category', false);

        $this->assertEquals('enum', $var->getTypeName());
    }

    // ========================================================================
    // init() Tests
    // ========================================================================

    public function testInitWithArrayOfValues(): void
    {
        $var = new EnumVariable('Category', 'category', false);
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2'];

        $var->init($values);

        $this->assertEquals($values, $var->_values);
    }

    public function testInitWithPromptTrue(): void
    {
        $var = new EnumVariable('Category', 'category', false);

        $var->init(['opt1' => 'Option 1'], true);

        $this->assertIsString($var->_prompt);
        $this->assertStringContainsString('select', $var->_prompt);
    }

    public function testInitWithCustomPrompt(): void
    {
        $var = new EnumVariable('Category', 'category', false);

        $var->init(['opt1' => 'Option 1'], 'Choose one:');

        $this->assertEquals('Choose one:', $var->_prompt);
    }

    public function testInitWithPromptFalse(): void
    {
        $var = new EnumVariable('Category', 'category', false);

        $var->init(['opt1' => 'Option 1'], false);

        $this->assertFalse($var->_prompt);
    }

    public function testInitWithNoPromptParameter(): void
    {
        $var = new EnumVariable('Category', 'category', false);

        $var->init(['opt1' => 'Option 1']);

        // Default prompt should be false
        $this->assertFalse($var->_prompt);
    }

    // ========================================================================
    // Validation Tests - V3 signature: isValid($vars, $value): bool
    // ========================================================================

    public function testIsValidWithValidValue(): void
    {
        $vars = new Horde_Variables(['category' => 'opt1']);
        $var = new EnumVariable('Category', 'category', false);
        $var->init(['opt1' => 'Option 1', 'opt2' => 'Option 2']);

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    public function testIsValidWithInvalidValue(): void
    {
        $vars = new Horde_Variables(['category' => 'invalid']);
        $var = new EnumVariable('Category', 'category', false);
        $var->init(['opt1' => 'Option 1', 'opt2' => 'Option 2']);

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    public function testIsValidWithRequiredFieldEmpty(): void
    {
        $vars = new Horde_Variables(['category' => '']);
        $var = new EnumVariable('Category', 'category', true);
        $var->init(['opt1' => 'Option 1', 'opt2' => 'Option 2']);

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    public function testIsValidWithEmptyValueAndPrompt(): void
    {
        $vars = new Horde_Variables(['category' => '']);
        $var = new EnumVariable('Category', 'category', false);
        $var->init(['opt1' => 'Option 1'], true);

        $result = $var->validate($vars);

        // Empty is valid when prompt is set and field is not required
        $this->assertTrue($result);
    }

    public function testIsValidWithEmptyValuesArray(): void
    {
        $vars = new Horde_Variables(['category' => 'anything']);
        $var = new EnumVariable('Category', 'category', false);
        $var->init([]);

        $result = $var->validate($vars);

        // Empty values array allows anything
        $this->assertTrue($result);
    }

    // ========================================================================
    // getValues() and setValues() Tests
    // ========================================================================

    public function testGetValues(): void
    {
        $var = new EnumVariable('Category', 'category', false);
        $values = ['opt1' => 'Option 1', 'opt2' => 'Option 2'];
        $var->init($values);

        $this->assertEquals($values, $var->getValues());
    }

    public function testSetValues(): void
    {
        $var = new EnumVariable('Category', 'category', false);
        $values = ['new1' => 'New 1', 'new2' => 'New 2'];

        $var->setValues($values);

        $this->assertEquals($values, $var->_values);
        $this->assertEquals($values, $var->getValues());
    }

    // ========================================================================
    // getPrompt() Test
    // ========================================================================

    public function testGetPrompt(): void
    {
        $var = new EnumVariable('Category', 'category', false);
        $var->init(['opt1' => 'Option 1'], 'Select category:');

        $this->assertEquals('Select category:', $var->getPrompt());
    }

    // ========================================================================
    // about() Test - V3 uses return type hint: about(): array
    // ========================================================================

    public function testAboutReturnsArrayWithReturnType(): void
    {
        $var = new EnumVariable('Category', 'category', false);

        $about = $var->about();

        $this->assertIsArray($about);
        $this->assertArrayHasKey('name', $about);
        $this->assertArrayHasKey('params', $about);
        $this->assertArrayHasKey('values', $about['params']);
        $this->assertArrayHasKey('prompt', $about['params']);
    }
}
