<?php

declare(strict_types=1);

/**
 * Fixtures for BaseVariable::getTypeName() tests.
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * Declared in dedicated namespace blocks so getTypeName() can be exercised
 * against class-name shapes that PSR-4 autoloading in test/ (which is mapped
 * to Horde\Form\Test\) cannot express directly.
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */

namespace Horde\Fakeapp\Form\V3 {

    use Horde\Form\V3\BaseVariable;

    /**
     * Vendor-namespaced app variable shape: `Horde\{App}\...\FooVariable`.
     *
     * Expected getTypeName(): `fakeapp_form_type_folders`.
     */
    class FoldersVariable extends BaseVariable
    {
    }
}

namespace Fakeapp\Form\V3 {

    use Horde\Form\V3\BaseVariable;

    /**
     * Legacy pre-PSR-4 app variable shape: `{App}\...\FooVariable`.
     *
     * Expected getTypeName(): `fakeapp_form_type_folders`.
     */
    class FoldersVariable extends BaseVariable
    {
    }
}
