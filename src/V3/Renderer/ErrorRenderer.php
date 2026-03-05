<?php
declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3\Renderer;

/**
 * ErrorRenderer interface for rendering validation errors.
 *
 * Different strategies for displaying errors:
 * - InlineErrors: Next to each field
 * - TopErrors: Summary at top of form
 * - TooltipErrors: JavaScript tooltips
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
interface ErrorRenderer
{
    /**
     * Render a single field error.
     *
     * @param string $varName  Variable name
     * @param string $message  Error message
     * @return string  Rendered error HTML
     */
    public function renderFieldError(string $varName, string $message): string;

    /**
     * Render all form errors (summary).
     *
     * @param array<string, string> $errors  Map of variable name => error message
     * @return string  Rendered errors HTML
     */
    public function renderFormErrors(array $errors): string;
}
