<?php

/**
 * Integration tests for V3 form usage patterns.
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
use Horde\Form\V3\TextVariable;
use Horde\Form\V3\EnumVariable;
use Horde_Variables;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for V3 real-world usage patterns.
 *
 * WARNING: Most tests are skipped because V3 BaseForm is empty stub.
 * These tests document expected behavior when V3 is complete.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 */
class V3FormIntegrationTest extends TestCase
{
    // ========================================================================
    // Basic Variable Usage (Works in V3)
    // ========================================================================

    public function testVariableCreationAndValidation(): void
    {
        $vars = new Horde_Variables(['name' => 'John Doe', 'email' => 'john@example.com']);

        $nameVar = new TextVariable('Full Name', 'name', true);
        $emailVar = new TextVariable('Email', 'email', true);

        $nameVar->init();
        $emailVar->init();

        $this->assertTrue($nameVar->validate($vars));
        $this->assertTrue($emailVar->validate($vars));
        $this->assertEquals('John Doe', $nameVar->getValue($vars));
        $this->assertEquals('john@example.com', $emailVar->getValue($vars));
    }

    public function testEnumVariableWithDropdown(): void
    {
        $vars = new Horde_Variables(['category' => 'tech']);

        $var = new EnumVariable('Category', 'category', true);
        $var->init([
            'tech' => 'Technology',
            'science' => 'Science',
            'art' => 'Art'
        ], true);

        $this->assertTrue($var->validate($vars));
        $this->assertEquals('tech', $var->getValue($vars));
    }

    // ========================================================================
    // Form-Based Tests (All Skipped - BaseForm Empty)
    // ========================================================================

    public function testSimpleFeedbackFormLifecycle(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub');

        // Pattern from ansel/faces/report.php
        // $vars = new Horde_Variables(['name' => 'John', 'message' => 'Test feedback']);
        // $form = new BaseForm($vars, 'Feedback Form');
        //
        // $form->addVariable('Your Name', 'name', 'text', true);
        // $form->addVariable('Message', 'message', 'longtext', true);
        //
        // $this->assertTrue($form->validate());
        //
        // $info = [];
        // $form->getInfo($vars, $info);
        // $this->assertEquals('John', $info['name']);
        // $this->assertEquals('Test feedback', $info['message']);
    }

    public function testMultiSectionForm(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub');

        // Pattern from nag/lib/Form/Task.php
        // $vars = new Horde_Variables();
        // $form = new BaseForm($vars, 'Task Form');
        //
        // $form->setSection('Basic Information');
        // $form->addVariable('Task Name', 'name', 'text', true);
        //
        // $form->setSection('Details');
        // $form->addVariable('Description', 'desc', 'longtext', false);
        //
        // $this->assertCount(2, $form->getSections());
    }

    public function testFormWithActions(): void
    {
        $this->markTestSkipped('V3 does not have Action system yet');

        // Pattern for dropdown reload
        // $vars = new Horde_Variables();
        // $form = new BaseForm($vars, 'Dynamic Form');
        //
        // $categoryVar = $form->addVariable('Category', 'category', 'enum', true, false, null, [
        //     ['tech' => 'Technology', 'science' => 'Science']
        // ]);
        // $categoryVar->setAction(Horde_Form_Action::factory('reload'));
        //
        // $this->assertTrue($categoryVar->hasAction());
    }

    public function testFileUploadForm(): void
    {
        $this->markTestSkipped('V3 does not have FileVariable yet');

        // $vars = new Horde_Variables();
        // $form = new BaseForm($vars, 'Upload Form');
        //
        // $form->addVariable('File', 'upload', 'file', true);
        //
        // $this->assertEquals('multipart/form-data', $form->getEnctype());
    }

    public function testInheritedFormClass(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub');

        // Pattern: Application extends BaseForm
        // class MyCustomForm extends BaseForm
        // {
        //     public function __construct($vars)
        //     {
        //         parent::__construct($vars, 'My Form');
        //         $this->addVariable('Name', 'name', 'text', true);
        //     }
        // }
        //
        // $vars = new Horde_Variables(['name' => 'John']);
        // $form = new MyCustomForm($vars);
        // $this->assertInstanceOf(BaseForm::class, $form);
    }

    // ========================================================================
    // V3-Specific Integration Tests (Type/Variable Merge)
    // ========================================================================

    public function testVariableIsItsOwnType(): void
    {
        $var = new TextVariable('Name', 'name', false);

        // In V3, getType() returns the variable itself (with deprecation warning)
        $type = @$var->getType();

        $this->assertSame($var, $type);
        $this->assertEquals('text', $var->getTypeName());
    }

    public function testDeprecatedTypePropertyAccess(): void
    {
        $var = new TextVariable('Name', 'name', false);

        // In V3, accessing ->type property returns self (with deprecation warning)
        $type = @$var->type;

        $this->assertSame($var, $type);
    }

    public function testInvalidMethodSetsMessage(): void
    {
        $var = new TextVariable('Name', 'name', true);
        $vars = new Horde_Variables(['name' => '']);

        $result = $var->validate($vars);

        $this->assertFalse($result);
        $this->assertNotEmpty($var->getMessage());
    }

    public function testValidationWithoutMessageParameter(): void
    {
        // V3 removes $message parameter from validation
        $var = new TextVariable('Name', 'name', true);
        $vars = new Horde_Variables(['name' => 'John']);

        $result = $var->validate($vars);

        // No $message parameter needed!
        $this->assertTrue($result);
    }

    public function testGetInfoWrapperForCompatibility(): void
    {
        $var = new TextVariable('Name', 'name', false);
        $vars = new Horde_Variables(['name' => 'John Doe']);

        $info = null;
        $var->getInfo($vars, $info);

        // V3 has getInfo() wrapper for seamless transition
        $this->assertEquals('John Doe', $info);
    }

    // ========================================================================
    // V3 Multiple Variable Types Together
    // ========================================================================

    public function testMultipleVariableTypesInSingleForm(): void
    {
        $this->markTestSkipped('V3 BaseForm is empty stub');

        // When BaseForm is implemented, test mixing types:
        // - TextVariable
        // - EnumVariable
        // - LongtextVariable
        // - BooleanVariable
        // - DateVariable
        // etc.
    }

    public function testComplexValidationScenario(): void
    {
        $vars = new Horde_Variables([
            'email' => 'invalid-email',
            'age' => '150',
            'category' => 'nonexistent'
        ]);

        // Test multiple validation failures
        $emailVar = new TextVariable('Email', 'email', true);
        $emailVar->init('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/');

        $categoryVar = new EnumVariable('Category', 'category', true);
        $categoryVar->init(['tech' => 'Tech', 'art' => 'Art'], true);

        $this->assertFalse($emailVar->validate($vars));
        $this->assertFalse($categoryVar->validate($vars));
    }

    // ========================================================================
    // Array Field Tests (V3 Support Unknown)
    // ========================================================================

    public function testArrayFieldHandling(): void
    {
        $vars = new Horde_Variables([
            'items' => ['item1', 'item2', 'item3']
        ]);

        $var = new TextVariable('Items', 'items[]', false);

        $this->assertTrue($var->_arrayVal);
        $this->assertEquals('item2', $var->getValue($vars, 1));
    }

    // ========================================================================
    // Migration Scenarios (V2 → V3)
    // ========================================================================

    public function testMigrationFromV2ToV3Pattern(): void
    {
        // V2 pattern (lib/):
        // $form->addVariable('Name', 'name', 'text', true);
        // $var->getType()->isValid($var, $vars, $value, $message);

        // V3 pattern (src/V3/):
        // Variable IS the type
        // $var->validate($vars) - no $message parameter

        $var = new TextVariable('Name', 'name', true);
        $vars = new Horde_Variables(['name' => 'John']);

        $result = $var->validate($vars);

        $this->assertTrue($result);
        // No separate Type object!
        // No $message reference parameter!
    }
}
