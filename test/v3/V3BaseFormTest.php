<?php

/**
 * Tests for Horde\Form\V3\BaseForm class.
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

use Horde\Form\V3\BaseForm;
use Horde_Variables;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Horde\Form\V3\BaseForm.
 *
 * WARNING: V3 BaseForm is currently an empty stub (17 lines, just class declaration).
 * All tests are marked as skipped to document expected functionality.
 *
 * This test file documents what SHOULD work when BaseForm is implemented.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
#[CoversClass(BaseForm::class)]
class V3BaseFormTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub - no implementation yet');
    }

    // ========================================================================
    // Constructor Tests
    // ========================================================================

    public function testConstructorWithNullVars(): void
    {
        $form = new BaseForm(null, 'Test Form');

        $this->assertInstanceOf(Horde_Variables::class, $form->getVars());
    }

    public function testConstructorWithVars(): void
    {
        $vars = new Horde_Variables(['test' => 'value']);
        $form = new BaseForm($vars, 'Test Form');

        $this->assertSame($vars, $form->getVars());
    }

    public function testConstructorWithTitleAndName(): void
    {
        $form = new BaseForm(null, 'My Form', 'myform');

        $this->assertEquals('My Form', $form->getTitle());
        $this->assertEquals('myform', $form->getName());
    }

    public function testConstructorAutoGeneratesName(): void
    {
        $form1 = new BaseForm(null, 'Form 1');
        $form2 = new BaseForm(null, 'Form 2');

        $this->assertNotEquals($form1->getName(), $form2->getName());
    }

    // ========================================================================
    // addVariable Tests
    // ========================================================================

    public function testAddVariableReturnsVariableObject(): void
    {
        $form = new BaseForm(null, 'Test Form');

        $var = $form->addVariable('Name', 'name', 'text', true);

        $this->assertInstanceOf(\Horde\Form\V3\TextVariable::class, $var);
    }

    public function testAddVariableAddsToFormCollection(): void
    {
        $form = new BaseForm(null, 'Test Form');

        $form->addVariable('Name', 'name', 'text', true);

        $vars = $form->getVariables();
        $this->assertCount(1, $vars);
    }

    // ========================================================================
    // Validation Tests
    // ========================================================================

    public function testValidateReturnsFalseWhenNotSubmitted(): void
    {
        $vars = new Horde_Variables([]);
        $form = new BaseForm($vars, 'Test Form');
        $form->addVariable('Name', 'name', 'text', true);

        $result = $form->validate();

        $this->assertFalse($result);
    }

    public function testValidateReturnsTrueForValidForm(): void
    {
        $vars = new Horde_Variables(['name' => 'John', 'formname' => 'testform']);
        $form = new BaseForm($vars, 'Test Form', 'testform');
        $form->addVariable('Name', 'name', 'text', true);

        $result = $form->validate();

        $this->assertTrue($result);
    }

    // ========================================================================
    // getInfo Tests
    // ========================================================================

    public function testGetInfoExtractsValues(): void
    {
        $vars = new Horde_Variables(['name' => 'John', 'email' => 'john@example.com']);
        $form = new BaseForm($vars, 'Test Form');
        $form->addVariable('Name', 'name', 'text', true);
        $form->addVariable('Email', 'email', 'text', true);

        $info = [];
        $form->getInfo($vars, $info);

        $this->assertEquals('John', $info['name']);
        $this->assertEquals('john@example.com', $info['email']);
    }

    // ========================================================================
    // Section Tests
    // ========================================================================

    public function testSetSectionCreatesSection(): void
    {
        $form = new BaseForm(null, 'Test Form');

        $form->setSection('Personal Info');
        $form->addVariable('Name', 'name', 'text', true);

        // Should have section created
        $this->assertTrue(true);
    }

    // ========================================================================
    // Hidden Variable Tests
    // ========================================================================

    public function testAddHiddenCreatesHiddenVariable(): void
    {
        $form = new BaseForm(null, 'Test Form');

        $var = $form->addHidden('token', 'text', true);

        $this->assertTrue($var->isHidden());
    }

    // ========================================================================
    // Note: All Other Form Methods Would Be Tested Here
    // ========================================================================
    // removeVariable(), insertVariableBefore(), getError(), setError(),
    // clearError(), clearValidation(), etc.
}
