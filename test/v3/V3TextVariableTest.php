<?php

/**
 * Tests for Horde\Form\V3\TextVariable type.
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

use Horde\Form\V3\TextVariable;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Horde\Form\V3\TextVariable.
 *
 * In V3, TextVariable combines both Type and Variable functionality.
 * Compare with lib/Horde/Form/Type.php Horde_Form_Type_text.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(TextVariable::class)]
class V3TextVariableTest extends TestCase
{
    // ========================================================================
    // Constructor and Type Name Tests
    // ========================================================================

    public function testConstructorSetsBasicProperties(): void
    {
        $var = new TextVariable('Full Name', 'name', true, false, 'Your name');

        $this->assertEquals('Full Name', $var->humanName);
        $this->assertEquals('name', $var->varName);
        $this->assertTrue($var->required);
        $this->assertFalse($var->readonly);
        $this->assertEquals('Your name', $var->description);
    }

    public function testGetTypeName(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertEquals('text', $var->getTypeName());
    }

    // ========================================================================
    // init() Tests - V3 uses same pattern as lib/
    // ========================================================================

    public function testInitWithNoParameters(): void
    {
        $var = new TextVariable('Name', 'name', false);
        $var->init();

        $this->assertEquals('', $var->_regex);
        $this->assertEquals(40, $var->_size);
        $this->assertNull($var->_maxlength);
    }

    public function testInitWithSize(): void
    {
        $var = new TextVariable('Name', 'name', false);
        $var->init('', 60);

        $this->assertEquals(60, $var->_size);
    }

    public function testInitWithSizeAndMaxLength(): void
    {
        $var = new TextVariable('Name', 'name', false);
        $var->init('', 50, 100);

        $this->assertEquals(50, $var->_size);
        $this->assertEquals(100, $var->_maxlength);
    }

    public function testInitWithRegex(): void
    {
        $var = new TextVariable('Email', 'email', false);
        $var->init('/^[a-z]+@[a-z]+\.[a-z]+$/');

        $this->assertEquals('/^[a-z]+@[a-z]+\.[a-z]+$/', $var->_regex);
    }

    // ========================================================================
    // Validation Tests - V3 signature: isValid($vars, $value): bool
    // ========================================================================

    public function testIsValidWithValidText(): void
    {
        $vars = new Horde_Variables(['name' => 'John Doe']);
        $var = new TextVariable('Name', 'name', false);
        $var->init();

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    public function testIsValidWithRequiredFieldEmpty(): void
    {
        $vars = new Horde_Variables(['name' => '']);
        $var = new TextVariable('Name', 'name', true);
        $var->init();

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    public function testIsValidAllowsZeroForRequiredField(): void
    {
        $vars = new Horde_Variables(['count' => '0']);
        $var = new TextVariable('Count', 'count', true);
        $var->init();

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    public function testIsValidFailsWhenExceedsMaxLength(): void
    {
        $vars = new Horde_Variables(['name' => 'This is a very long string that exceeds maximum']);
        $var = new TextVariable('Name', 'name', false);
        $var->init('', 40, 20);

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    public function testIsValidPassesWhenWithinMaxLength(): void
    {
        $vars = new Horde_Variables(['name' => 'Short']);
        $var = new TextVariable('Name', 'name', false);
        $var->init('', 40, 20);

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    public function testIsValidFailsWhenRegexDoesNotMatch(): void
    {
        $vars = new Horde_Variables(['email' => 'not-an-email']);
        $var = new TextVariable('Email', 'email', false);
        $var->init('/^[a-z]+@[a-z]+\.[a-z]+$/');

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    public function testIsValidPassesWhenRegexMatches(): void
    {
        $vars = new Horde_Variables(['email' => 'test@example.com']);
        $var = new TextVariable('Email', 'email', false);
        $var->init('/^[a-z]+@[a-z]+\.[a-z]+$/');

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    // ========================================================================
    // Getter Tests
    // ========================================================================

    public function testGetSize(): void
    {
        $var = new TextVariable('Name', 'name', false);
        $var->init('', 60);

        $this->assertEquals(60, $var->getSize());
    }

    public function testGetMaxLength(): void
    {
        $var = new TextVariable('Name', 'name', false);
        $var->init('', 40, 100);

        $this->assertEquals(100, $var->getMaxLength());
    }

    // ========================================================================
    // about() Tests - V3 uses return type hint: about(): array
    // ========================================================================

    public function testAboutReturnsArrayWithReturnType(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $about = $var->about();

        $this->assertIsArray($about);
        $this->assertArrayHasKey('name', $about);
        $this->assertArrayHasKey('params', $about);
    }
}
