<?php

/**
 * Comprehensive tests for the Horde_Form class.
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
use Horde_Form_Variable;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the core Horde_Form functionality.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(Horde_Form::class)]
class FormTest extends TestCase
{
    // ========================================================================
    // Constructor Tests
    // ========================================================================

    public function testConstructorWithNullVars(): void
    {
        $vars = null;
        $form = new Horde_Form($vars);

        $this->assertInstanceOf(Horde_Form::class, $form);
        $this->assertNull($form->getVars());
    }

    public function testConstructorWithTitle(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars, 'My Form');

        $this->assertEquals('My Form', $form->getTitle());
    }

    public function testConstructorWithTitleAndName(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars, 'My Form', 'my_form');

        $this->assertEquals('My Form', $form->getTitle());
        $this->assertEquals('my_form', $form->getName());
    }

    public function testConstructorAutoGeneratesName(): void
    {
        $vars = new Horde_Variables();
        $form1 = new Horde_Form($vars);
        $form2 = new Horde_Form($vars);

        $name1 = $form1->getName();
        $name2 = $form2->getName();

        $this->assertNotEquals($name1, $name2, 'Auto-generated names should be unique');
        $this->assertStringStartsWith('horde_form_', $name1);
        $this->assertStringStartsWith('horde_form_', $name2);
    }

    public function testConstructorUsesReferenceForVars(): void
    {
        $vars = new Horde_Variables(['initial' => 'value']);
        $form = new Horde_Form($vars);

        // Modify through form
        $form->getVars()->set('added', 'new_value');

        // Should affect original $vars (reference behavior)
        $this->assertEquals('new_value', $vars->get('added'));
    }

    // ========================================================================
    // Title and Name Tests
    // ========================================================================

    public function testGetSetTitle(): void
    {
        $form = new Horde_Form(null, 'Original Title');
        $this->assertEquals('Original Title', $form->getTitle());

        $form->setTitle('New Title');
        $this->assertEquals('New Title', $form->getTitle());
    }

    // ========================================================================
    // addVariable Tests - Parameter Variations
    // ========================================================================

    public function testAddVariableWith4Parameters(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var = $form->addVariable('Name', 'name', 'text', true);

        $this->assertInstanceOf(Horde_Form_Variable::class, $var);
        $this->assertEquals('Name', $var->humanName);
        $this->assertEquals('name', $var->getVarName());
        $this->assertTrue($var->isRequired());
    }

    public function testAddVariableWith5Parameters(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var = $form->addVariable('Name', 'name', 'text', true, false);

        $this->assertInstanceOf(Horde_Form_Variable::class, $var);
        $this->assertTrue($var->isRequired());
        $this->assertFalse($var->isReadonly());
    }

    public function testAddVariableWith6Parameters(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var = $form->addVariable(
            'Name',
            'name',
            'text',
            true,
            false,
            'Enter your name'
        );

        $this->assertEquals('Enter your name', $var->getDescription());
    }

    public function testAddVariableWith7Parameters(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var = $form->addVariable(
            'Country',
            'country',
            'enum',
            true,
            false,
            'Select country',
            [['us' => 'USA', 'uk' => 'UK']]
        );

        $values = $var->getValues();
        $this->assertArrayHasKey('us', $values);
        $this->assertArrayHasKey('uk', $values);
    }

    public function testAddVariableReturnsVariable(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var = $form->addVariable('Test', 'test', 'text', true);

        $this->assertInstanceOf(Horde_Form_Variable::class, $var);
    }

    public function testAddVariableAddsToFormVariables(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Field1', 'field1', 'text', true);
        $form->addVariable('Field2', 'field2', 'text', true);

        $variables = $form->getVariables();
        $this->assertCount(2, $variables);
    }

    public function testAddVariableWithEnumTypeAutoFillsSingleValue(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        // Enum with single option and no prompt should auto-fill
        $var = $form->addVariable(
            'Single',
            'single',
            'enum',
            true,
            false,
            null,
            [['only' => 'Only Option']]
        );

        $this->assertTrue($var->_autofilled);
        $this->assertEquals('only', $vars->get('single'));
    }

    public function testAddVariableWithFileTypeSetsMultipartEnctype(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Upload', 'upload', 'file', false);

        $this->assertEquals('multipart/form-data', $form->getEnctype());
    }

    public function testAddVariableWithImageTypeSetsMultipartEnctype(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Image', 'image', 'image', false);

        $this->assertEquals('multipart/form-data', $form->getEnctype());
    }

    // ========================================================================
    // addHidden Tests
    // ========================================================================

    public function testAddHidden(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var = $form->addHidden('', 'hidden_field', 'text', false);

        $this->assertInstanceOf(Horde_Form_Variable::class, $var);
        $this->assertTrue($var->isHidden());
    }

    public function testAddHiddenNotIncludedInRegularVariables(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Visible', 'visible', 'text', true);
        $form->addHidden('', 'hidden', 'text', false);

        $variables = $form->getVariables(true, false);
        $this->assertCount(1, $variables, 'Hidden variables should not be in regular list');
    }

    public function testAddHiddenIncludedWhenRequested(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Visible', 'visible', 'text', true);
        $form->addHidden('', 'hidden', 'text', false);

        $variables = $form->getVariables(true, true);
        $this->assertCount(2, $variables, 'Hidden variables should be included');
    }

    // ========================================================================
    // Section Tests
    // ========================================================================

    public function testSetSection(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->setSection('section1', 'Section 1');
        $form->addVariable('Field1', 'field1', 'text', true);

        $sections = $form->getSectionInfo();
        $this->assertArrayHasKey('section1', $sections);
        $this->assertEquals('Section 1', $sections['section1']['title']);
    }

    public function testVariablesInDifferentSections(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->setSection('section1', 'Section 1');
        $form->addVariable('Field1', 'field1', 'text', true);

        $form->setSection('section2', 'Section 2');
        $form->addVariable('Field2', 'field2', 'text', true);

        $allVariables = $form->getVariables(false);

        $this->assertArrayHasKey('section1', $allVariables);
        $this->assertArrayHasKey('section2', $allVariables);
        $this->assertCount(1, $allVariables['section1']);
        $this->assertCount(1, $allVariables['section2']);
    }

    public function testDefaultSectionIsBase(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        // Add variable without setting section
        $form->addVariable('Field1', 'field1', 'text', true);

        $allVariables = $form->getVariables(false);
        $this->assertArrayHasKey('__base', $allVariables);
    }

    // ========================================================================
    // insertVariableBefore Tests
    // ========================================================================

    public function testInsertVariableBeforeWithNullAppendsToEnd(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('First', 'first', 'text', true);
        $form->insertVariableBefore(null, 'Second', 'second', 'text', true);

        $variables = $form->getVariables();
        $this->assertEquals('first', $variables[0]->getVarName());
        $this->assertEquals('second', $variables[1]->getVarName());
    }

    public function testInsertVariableBeforeInsertsInCorrectPosition(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('First', 'first', 'text', true);
        $form->addVariable('Third', 'third', 'text', true);
        $form->insertVariableBefore('third', 'Second', 'second', 'text', true);

        $variables = $form->getVariables();
        $this->assertEquals('first', $variables[0]->getVarName());
        $this->assertEquals('second', $variables[1]->getVarName());
        $this->assertEquals('third', $variables[2]->getVarName());
    }

    // ========================================================================
    // removeVariable Tests
    // ========================================================================

    public function testRemoveVariableByName(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Field1', 'field1', 'text', true);
        $form->addVariable('Field2', 'field2', 'text', true);

        $result = $form->removeVariable($var = 'field1');

        $this->assertTrue($result);
        $variables = $form->getVariables();
        $this->assertCount(1, $variables);
        $this->assertEquals('field2', $variables[0]->getVarName());
    }

    public function testRemoveVariableByObject(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $var1 = $form->addVariable('Field1', 'field1', 'text', true);
        $form->addVariable('Field2', 'field2', 'text', true);

        $result = $form->removeVariable($var1);

        $this->assertTrue($result);
        $variables = $form->getVariables();
        $this->assertCount(1, $variables);
    }

    public function testRemoveVariableReturnsFalseIfNotFound(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Field1', 'field1', 'text', true);

        $result = $form->removeVariable($var = 'nonexistent');

        $this->assertFalse($result);
    }

    // ========================================================================
    // getType Tests - Type Conversion
    // ========================================================================

    public function testGetTypeWithStringReturnsTypeObject(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $type = $form->getType('text');

        $this->assertInstanceOf(\Horde_Form_Type_text::class, $type);
    }

    public function testGetTypeWithObjectReturnsObject(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $typeObj = new \Horde_Form_Type_text();
        $result = $form->getType($typeObj);

        $this->assertSame($typeObj, $result);
    }

    public function testGetTypeWithInvalidTypeReturnsInvalid(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $type = $form->getType('nonexistent_type');

        $this->assertInstanceOf(\Horde_Form_Type_invalid::class, $type);
    }

    public function testGetTypeWithParametersInitializesType(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $type = $form->getType('enum', [['opt1' => 'Option 1', 'opt2' => 'Option 2']]);

        $this->assertInstanceOf(\Horde_Form_Type_enum::class, $type);
        $values = $type->getValues();
        $this->assertArrayHasKey('opt1', $values);
    }

    public function testGetTypeConvertsNamedParamsToPositional(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        // Named parameters should be wrapped in array
        $type = $form->getType('monthdayyear', ['start_year' => 1900, 'end_year' => 2050]);

        $this->assertInstanceOf(\Horde_Form_Type_monthdayyear::class, $type);
    }

    // ========================================================================
    // Validation Tests
    // ========================================================================

    public function testValidateReturnsFalseIfNotSubmitted(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->addVariable('Name', 'name', 'text', true);

        $result = $form->validate($vars);

        $this->assertFalse($result);
    }

    public function testValidateReturnsTrueForValidForm(): void
    {
        $vars = new Horde_Variables([
            'name' => 'John Doe',
            'formname' => 'test_form'
        ]);
        $form = new Horde_Form($vars, '', 'test_form');

        $form->addVariable('Name', 'name', 'text', true);
        $vars->set($form->getName() . '_submitted', '1');

        $result = $form->validate($vars);

        $this->assertTrue($result);
        $this->assertTrue($form->isValid());
    }

    public function testValidateReturnsFalseForRequiredFieldMissing(): void
    {
        $vars = new Horde_Variables([
            'formname' => 'test_form'
        ]);
        $form = new Horde_Form($vars, '', 'test_form');

        $form->addVariable('Name', 'name', 'text', true);
        $vars->set($form->getName() . '_submitted', '1');

        $result = $form->validate($vars);

        $this->assertFalse($result);
        $this->assertFalse($form->isValid());
    }

    // ========================================================================
    // Error Management Tests
    // ========================================================================

    public function testGetErrors(): void
    {
        $vars = new Horde_Variables(['formname' => 'test_form']);
        $form = new Horde_Form($vars, '', 'test_form');

        $form->addVariable('Name', 'name', 'text', true);
        $vars->set($form->getName() . '_submitted', '1');
        $form->validate($vars);

        $errors = $form->getErrors();
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testGetError(): void
    {
        $vars = new Horde_Variables(['formname' => 'test_form']);
        $form = new Horde_Form($vars, '', 'test_form');

        $form->addVariable('Name', 'name', 'text', true);
        $vars->set($form->getName() . '_submitted', '1');
        $form->validate($vars);

        $error = $form->getError('name');
        $this->assertNotNull($error);
        $this->assertIsString($error);
    }

    public function testSetError(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->setError('fieldname', 'Custom error message');

        $error = $form->getError('fieldname');
        $this->assertEquals('Custom error message', $error);
    }

    public function testClearError(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->setError('fieldname', 'Error message');
        $form->clearError('fieldname');

        $error = $form->getError('fieldname');
        $this->assertNull($error);
    }

    public function testClearValidation(): void
    {
        $vars = new Horde_Variables();
        $form = new Horde_Form($vars);

        $form->setError('field1', 'Error 1');
        $form->setError('field2', 'Error 2');

        $form->clearValidation();

        $errors = $form->getErrors();
        $this->assertEmpty($errors);
    }

    // ========================================================================
    // getInfo Tests
    // ========================================================================

    public function testGetInfoExtractsValues(): void
    {
        $vars = new Horde_Variables([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $form = new Horde_Form($vars);

        $form->addVariable('Name', 'name', 'text', true);
        $form->addVariable('Email', 'email', 'email', true);

        $info = [];
        $result = $form->getInfo($vars, $info);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function testGetInfoWithNullVarsUsesFormVars(): void
    {
        $vars = new Horde_Variables([
            'name' => 'John Doe'
        ]);
        $form = new Horde_Form($vars);

        $form->addVariable('Name', 'name', 'text', true);

        $info = [];
        $result = $form->getInfo(null, $info);

        $this->assertEquals('John Doe', $result['name']);
    }
}
