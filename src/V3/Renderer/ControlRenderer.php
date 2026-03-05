<?php
declare(strict_types=1);

/**
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3\Renderer;

use Horde\Form\V3\Variable;
use Horde\Form\Form;

/**
 * ControlRenderer interface for rendering individual form controls.
 *
 * Separates control rendering (input, select, textarea) from form layout.
 * Each variable type can be rendered in different ways (HTML, JSON, etc.).
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
interface ControlRenderer
{
    /**
     * Render a form control for a variable.
     *
     * @param Variable $var  The variable to render
     * @param Form $form  The parent form
     * @param bool $readonly  Whether to render as readonly
     * @return string  Rendered control HTML
     */
    public function renderControl(Variable $var, Form $form, bool $readonly = false): string;

    /**
     * Render a label for a variable.
     *
     * @param Variable $var  The variable
     * @param Form $form  The parent form
     * @return string  Rendered label HTML
     */
    public function renderLabel(Variable $var, Form $form): string;

    /**
     * Render help text for a variable.
     *
     * @param Variable $var  The variable
     * @return string  Rendered help HTML
     */
    public function renderHelp(Variable $var): string;

    /**
     * Generate a unique field ID for a variable.
     *
     * @param Variable $var  The variable
     * @param bool $new  Whether to generate a new ID
     * @return string  Field ID
     */
    public function getFieldId(Variable $var, bool $new = false): string;
}
