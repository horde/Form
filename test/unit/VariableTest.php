<?php

/**
 * Comprehensive tests for the Horde_Form_Variable class.
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

namespace Horde\Form\Test\Unit;

use Horde_Form;
use Horde_Form_Action;
use Horde_Form_Type_text;
use Horde_Form_Type_enum;
use Horde_Form_Variable;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Form_Variable class.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(Horde_Form_Variable::class)]
class VariableTest extends TestCase
{
    // ========================================================================
    // Constructor Tests
    // ========================================================================

    public function testConstructorSetsProperties(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable(
            'Human Name',
            'var_name',
            $type,
            true,
            false,
            'Description'
        );

        $this->assertEquals('Human Name', $var->humanName);
        $this->assertEquals('var_name', $var->varName);
        $this->assertSame($type, $var->type);
        $this->assertTrue($var->required);
        $this->assertFalse($var->readonly);
        $this->assertEquals('Description', $var->description);
    }

    public function testConstructorDetectsArrayVariable(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Array Field', 'field[]', $type, false);

        $this->assertTrue($var->isArrayVal());
    }

    public function testConstructorNonArrayVariable(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertFalse($var->isArrayVal());
    }

    // ========================================================================
    // Form Assignment Tests
    // ========================================================================

    public function testSetFormOb(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $form = new Horde_Form(new Horde_Variables());

        $var->setFormOb($form);

        $this->assertSame($form, $var->form);
    }

    // ========================================================================
    // Default Value Tests
    // ========================================================================

    public function testSetAndGetDefault(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $var->setDefault('default_value');

        $this->assertEquals('default_value', $var->getDefault());
    }

    // ========================================================================
    // Action Tests
    // ========================================================================

    public function testSetAction(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $action = Horde_Form_Action::factory('submit');

        $var->setAction($action);

        $this->assertTrue($var->hasAction());
    }

    public function testHasActionReturnsFalseWhenNoAction(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertFalse($var->hasAction());
    }

    // ========================================================================
    // Hidden Variable Tests
    // ========================================================================

    public function testHide(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertFalse($var->isHidden());

        $var->hide();

        $this->assertTrue($var->isHidden());
    }

    // ========================================================================
    // Disabled Variable Tests
    // ========================================================================

    public function testDisable(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertFalse($var->isDisabled());

        $var->disable();

        $this->assertTrue($var->isDisabled());
    }

    // ========================================================================
    // Getter Tests
    // ========================================================================

    public function testGetHumanName(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Human Name', 'var_name', $type, false);

        $this->assertEquals('Human Name', $var->getHumanName());
    }

    public function testGetVarName(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field_name', $type, false);

        $this->assertEquals('field_name', $var->getVarName());
    }

    public function testGetType(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertSame($type, $var->getType());
    }

    public function testGetTypeName(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertEquals('text', $var->getTypeName());
    }

    public function testIsRequired(): void
    {
        $type = new Horde_Form_Type_text();
        $varRequired = new Horde_Form_Variable('Field', 'field', $type, true);
        $varOptional = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertTrue($varRequired->isRequired());
        $this->assertFalse($varOptional->isRequired());
    }

    public function testIsReadonly(): void
    {
        $type = new Horde_Form_Type_text();
        $varReadonly = new Horde_Form_Variable('Field', 'field', $type, false, true);
        $varEditable = new Horde_Form_Variable('Field', 'field', $type, false, false);

        $this->assertTrue($varReadonly->isReadonly());
        $this->assertFalse($varEditable->isReadonly());
    }

    public function testGetValues(): void
    {
        $type = new Horde_Form_Type_enum();
        $type->init(['opt1' => 'Option 1', 'opt2' => 'Option 2']);
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $values = $var->getValues();

        $this->assertIsArray($values);
        $this->assertArrayHasKey('opt1', $values);
        $this->assertArrayHasKey('opt2', $values);
    }

    // ========================================================================
    // Description Tests
    // ========================================================================

    public function testHasDescription(): void
    {
        $type = new Horde_Form_Type_text();
        $varWithDesc = new Horde_Form_Variable('Field', 'field', $type, false, false, 'Help text');
        $varNoDesc = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertTrue($varWithDesc->hasDescription());
        $this->assertFalse($varNoDesc->hasDescription());
    }

    public function testGetDescription(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false, false, 'Help text');

        $this->assertEquals('Help text', $var->getDescription());
    }

    // ========================================================================
    // Help Tests
    // ========================================================================

    public function testSetHelp(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $form = new Horde_Form(new Horde_Variables());
        $var->setFormOb($form);

        $var->setHelp('help_topic');

        $this->assertTrue($var->hasHelp());
        $this->assertEquals('help_topic', $var->getHelp());
    }

    public function testHasHelpReturnsFalseWhenNoHelp(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertFalse($var->hasHelp());
    }

    // ========================================================================
    // Upload Detection Tests
    // ========================================================================

    public function testIsUploadReturnsTrueForFileType(): void
    {
        $type = new \Horde_Form_Type_file();
        $var = new Horde_Form_Variable('Upload', 'upload', $type, false);

        $this->assertTrue($var->isUpload());
    }

    public function testIsUploadReturnsFalseForNonFileType(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertFalse($var->isUpload());
    }

    // ========================================================================
    // Option Tests
    // ========================================================================

    public function testSetAndGetOption(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $var->setOption('trackchange', true);

        $this->assertTrue($var->getOption('trackchange'));
    }

    public function testGetOptionReturnsNullForUnsetOption(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);

        $this->assertNull($var->getOption('nonexistent'));
    }

    // ========================================================================
    // getValue Tests
    // ========================================================================

    public function testGetValueReturnsSubmittedValue(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $vars = new Horde_Variables(['field' => 'submitted_value']);

        $value = $var->getValue($vars);

        $this->assertEquals('submitted_value', $value);
    }

    public function testGetValueReturnsDefaultWhenNotSubmitted(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $var->setDefault('default_value');
        $vars = new Horde_Variables([]);

        $value = $var->getValue($vars);

        $this->assertEquals('default_value', $value);
    }

    public function testGetValueForArrayVariable(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field[]', $type, false);
        $vars = new Horde_Variables(['field' => ['value1', 'value2']]);

        $value = $var->getValue($vars);

        $this->assertIsArray($value);
        $this->assertEquals(['value1', 'value2'], $value);
    }

    public function testGetValueForArrayVariableWithIndex(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field[]', $type, false);
        $vars = new Horde_Variables(['field' => ['value1', 'value2', 'value3']]);

        $value = $var->getValue($vars, 1);

        $this->assertEquals('value2', $value);
    }

    // ========================================================================
    // Validation Tests
    // ========================================================================

    public function testValidateReturnsTrueForValidValue(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $vars = new Horde_Variables(['field' => 'value']);

        $result = $var->validate($vars, $message = '');

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseForRequiredFieldMissing(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, true);
        $vars = new Horde_Variables([]);

        $result = $var->validate($vars, $message = '');

        $this->assertFalse($result);
        $this->assertNotEmpty($var->getMessage());
    }

    public function testValidateArrayVariable(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field[]', $type, false);
        $vars = new Horde_Variables(['field' => ['value1', 'value2']]);

        $result = $var->validate($vars, $message = '');

        $this->assertTrue($result);
    }

    public function testValidateArrayVariableRequiredEmpty(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field[]', $type, true);
        $vars = new Horde_Variables([]);

        $result = $var->validate($vars, $message = '');

        $this->assertFalse($result);
    }

    // ========================================================================
    // wasChanged Tests
    // ========================================================================

    public function testWasChangedReturnsNullWhenNotTracking(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $vars = new Horde_Variables(['field' => 'new_value']);

        $result = $var->wasChanged($vars);

        $this->assertNull($result);
    }

    public function testWasChangedReturnsTrueWhenChanged(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $var->setOption('trackchange', true);
        $vars = new Horde_Variables([
            'field' => 'new_value',
            '__old_field' => 'old_value'
        ]);

        $result = $var->wasChanged($vars);

        $this->assertTrue($result);
    }

    public function testWasChangedReturnsFalseWhenUnchanged(): void
    {
        $type = new Horde_Form_Type_text();
        $var = new Horde_Form_Variable('Field', 'field', $type, false);
        $var->setOption('trackchange', true);
        $vars = new Horde_Variables([
            'field' => 'same_value',
            '__old_field' => 'same_value'
        ]);

        $result = $var->wasChanged($vars);

        $this->assertFalse($result);
    }
}
