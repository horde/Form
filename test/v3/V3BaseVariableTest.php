<?php

/**
 * Tests for Horde\Form\V3\BaseVariable class.
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

use Horde\Form\V3\BaseVariable;
use Horde\Form\V3\TextVariable;
use Horde\Form\V3\EnumVariable;
use Horde_Variables;
use Horde_Form;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Horde\Form\V3\BaseVariable.
 *
 * V3 merges the Type and Variable concepts into a single *Variable class hierarchy.
 * This eliminates the separate Type/Variable split and reference passing between them.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(BaseVariable::class)]
class V3BaseVariableTest extends TestCase
{
    // ========================================================================
    // Constructor Tests
    // ========================================================================

    public function testConstructorSetsProperties(): void
    {
        $var = new TextVariable('Full Name', 'name', true, false, 'Enter your name');

        $this->assertEquals('Full Name', $var->humanName);
        $this->assertEquals('name', $var->varName);
        $this->assertTrue($var->required);
        $this->assertFalse($var->readonly);
        $this->assertEquals('Enter your name', $var->description);
    }

    public function testConstructorDetectsArrayVariables(): void
    {
        $var = new TextVariable('Items', 'items[]', false);

        $this->assertTrue($var->_arrayVal);
    }

    public function testConstructorDetectsNonArrayVariables(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertFalse($var->_arrayVal);
    }

    // ========================================================================
    // Form Assignment Tests
    // ========================================================================

    public function testSetFormOb(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub - cannot test form assignment');

        // $vars = new Horde_Variables();
        // $form = new Horde_Form($vars);
        // $var = new TextVariable('Name', 'name', false);
        //
        // $var->setFormOb($form);
        //
        // $this->assertSame($form, $var->form);
    }

    // ========================================================================
    // Default Value Tests
    // ========================================================================

    public function testSetAndGetDefault(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $var->setDefault('Default Value');

        $this->assertEquals('Default Value', $var->getDefault());
    }

    // ========================================================================
    // Action Tests
    // ========================================================================

    public function testSetAction(): void
    {
        $this->markTestSkipped('V3 does not have Action system yet');

        // $var = new TextVariable('Name', 'name', false);
        // $action = Horde_Form_Action::factory('reload');
        //
        // $var->setAction($action);
        //
        // $this->assertSame($action, $var->_action);
    }

    public function testHasActionReturnsTrueWhenActionSet(): void
    {
        $this->markTestSkipped('V3 does not have Action system yet');
    }

    public function testHasActionReturnsFalseWhenNoAction(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertFalse($var->hasAction());
    }

    // ========================================================================
    // State Tests
    // ========================================================================

    public function testHideAndIsHidden(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertFalse($var->isHidden());

        $var->hide();

        $this->assertTrue($var->isHidden());
    }

    public function testDisableAndIsDisabled(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertFalse($var->isDisabled());

        $var->disable();

        $this->assertTrue($var->isDisabled());
    }

    // ========================================================================
    // Getter Tests
    // ========================================================================

    public function testGetHumanName(): void
    {
        $var = new TextVariable('Full Name', 'name', false);

        $this->assertEquals('Full Name', $var->getHumanName());
    }

    public function testGetVarName(): void
    {
        $var = new TextVariable('Name', 'full_name', false);

        $this->assertEquals('full_name', $var->getVarName());
    }

    public function testGetTypeName(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertEquals('text', $var->getTypeName());
    }

    public function testIsRequired(): void
    {
        $requiredVar = new TextVariable('Name', 'name', true);
        $optionalVar = new TextVariable('Name', 'name', false);

        $this->assertTrue($requiredVar->isRequired());
        $this->assertFalse($optionalVar->isRequired());
    }

    public function testIsReadonly(): void
    {
        $readonlyVar = new TextVariable('Name', 'name', false, true);
        $editableVar = new TextVariable('Name', 'name', false, false);

        $this->assertTrue($readonlyVar->isReadonly());
        $this->assertFalse($editableVar->isReadonly());
    }

    public function testGetValuesForEnumType(): void
    {
        $var = new EnumVariable('Category', 'category', true);
        $var->init(['opt1' => 'Option 1', 'opt2' => 'Option 2']);

        $values = $var->getValues();

        $this->assertIsArray($values);
        $this->assertEquals(['opt1' => 'Option 1', 'opt2' => 'Option 2'], $values);
    }

    // ========================================================================
    // Description Tests
    // ========================================================================

    public function testHasDescriptionReturnsTrueWhenSet(): void
    {
        $var = new TextVariable('Name', 'name', false, false, 'This is a description');

        $this->assertTrue($var->hasDescription());
    }

    public function testGetDescription(): void
    {
        $var = new TextVariable('Name', 'name', false, false, 'Enter your full name');

        $this->assertEquals('Enter your full name', $var->getDescription());
    }

    // ========================================================================
    // Help Tests
    // ========================================================================

    public function testSetHelp(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub - setHelp() requires form');

        // $vars = new Horde_Variables();
        // $form = new Horde_Form($vars);
        // $var = new TextVariable('Name', 'name', false);
        // $var->setFormOb($form);
        //
        // $var->setHelp('help_topic');
        //
        // $this->assertTrue($var->hasHelp());
    }

    public function testHasHelpReturnsFalseWhenNotSet(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertFalse($var->hasHelp());
    }

    // ========================================================================
    // Upload Detection Tests
    // ========================================================================

    public function testIsUploadReturnsFalseForTextType(): void
    {
        $this->markTestSkipped('V3 does not have FileVariable yet');

        // $var = new TextVariable('Name', 'name', false);
        //
        // $this->assertFalse($var->isUpload());
    }

    public function testIsUploadReturnsTrueForFileType(): void
    {
        $this->markTestSkipped('V3 does not have FileVariable yet');

        // $var = new FileVariable('File', 'upload', false);
        //
        // $this->assertTrue($var->isUpload());
    }

    // ========================================================================
    // Options Tests
    // ========================================================================

    public function testSetAndGetOption(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $var->setOption('trackchange', true);

        $this->assertTrue($var->getOption('trackchange'));
    }

    public function testGetOptionReturnsNullForUnsetOption(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $this->assertNull($var->getOption('nonexistent'));
    }

    // ========================================================================
    // getValue Tests (V3 pattern different from lib/)
    // ========================================================================

    public function testGetValueReturnsSubmittedValue(): void
    {
        $vars = new Horde_Variables(['name' => 'John Doe']);
        $var = new TextVariable('Name', 'name', false);

        $value = $var->getValue($vars);

        $this->assertEquals('John Doe', $value);
    }

    public function testGetValueReturnsDefaultWhenNotSubmitted(): void
    {
        $vars = new Horde_Variables([]);
        $var = new TextVariable('Name', 'name', false);
        $var->setDefault('Default Name');

        $value = $var->getValue($vars);

        $this->assertEquals('Default Name', $value);
    }

    public function testGetValueForArrayVariableWithIndex(): void
    {
        $vars = new Horde_Variables(['items' => ['a', 'b', 'c']]);
        $var = new TextVariable('Items', 'items[]', false);

        $value = $var->getValue($vars, 1);

        $this->assertEquals('b', $value);
    }

    // ========================================================================
    // Validation Tests (V3 uses new signature: isValid($vars, $value): bool)
    // ========================================================================

    public function testValidateReturnsTrueForValidValue(): void
    {
        $vars = new Horde_Variables(['name' => 'John Doe']);
        $var = new TextVariable('Name', 'name', false);

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseForRequiredFieldMissing(): void
    {
        $vars = new Horde_Variables(['name' => '']);
        $var = new TextVariable('Name', 'name', true);

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    public function testValidateForArrayVariable(): void
    {
        $vars = new Horde_Variables(['items' => ['a', 'b', 'c']]);
        $var = new TextVariable('Items', 'items[]', false);

        $result = $var->validate($vars);

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseForRequiredArrayEmpty(): void
    {
        $vars = new Horde_Variables(['items' => []]);
        $var = new TextVariable('Items', 'items[]', true);

        $result = $var->validate($vars);

        $this->assertFalse($result);
    }

    // ========================================================================
    // V3-Specific: Deprecation Warning Tests
    // ========================================================================

    public function testGetTypeReturnsThisWithDeprecationWarning(): void
    {
        $var = new TextVariable('Name', 'name', false);

        // getType() should return $this (not a separate Type object) with deprecation
        $type = @$var->getType();  // Suppress deprecation warning

        $this->assertSame($var, $type);
    }

    public function testMagicGetTypePropertyReturnsThisWithDeprecation(): void
    {
        $var = new TextVariable('Name', 'name', false);

        // Accessing ->type property should return $this with deprecation
        $type = @$var->type;  // Suppress deprecation warning

        $this->assertSame($var, $type);
    }

    // ========================================================================
    // V3-Specific: invalid() Method Tests
    // ========================================================================

    public function testInvalidMethodSetsMessageAndReturnsFalse(): void
    {
        $var = new TextVariable('Name', 'name', false);

        $result = $var->invalid('Custom error message');

        $this->assertFalse($result);
        $this->assertEquals('Custom error message', $var->getMessage());
    }

    // ========================================================================
    // V3-Specific: getInfo() Wrapper Tests
    // ========================================================================

    public function testGetInfoReturnsValue(): void
    {
        $vars = new Horde_Variables(['name' => 'John Doe']);
        $var = new TextVariable('Name', 'name', false);

        $info = null;
        $result = $var->getInfo($vars, $info);

        // V3 has getInfo() wrapper for compatibility
        $this->assertEquals('John Doe', $info);
    }

    // ========================================================================
    // Change Tracking Tests (may not be in V3)
    // ========================================================================

    public function testWasModifiedReturnsNullWhenNotTracking(): void
    {
        $this->markTestSkipped('V3 change tracking implementation unknown');

        // $vars = new Horde_Variables(['name' => 'John Doe']);
        // $var = new TextVariable('Name', 'name', false);
        //
        // $result = $var->wasModified($vars);
        //
        // $this->assertNull($result);
    }
}
