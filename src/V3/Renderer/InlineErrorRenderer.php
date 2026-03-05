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
 * Inline error renderer.
 *
 * Displays errors inline next to fields and as a summary at top of form.
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class InlineErrorRenderer implements ErrorRenderer
{
    /**
     * Render a single field error.
     */
    public function renderFieldError(string $varName, string $message): string
    {
        return sprintf(
            '<span class="field-error" data-field="%s">%s</span>',
            htmlspecialchars($varName),
            htmlspecialchars($message)
        );
    }

    /**
     * Render all form errors (summary).
     */
    public function renderFormErrors(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        $items = [];
        foreach ($errors as $varName => $message) {
            $items[] = sprintf(
                '<li><strong>%s:</strong> %s</li>',
                htmlspecialchars($varName),
                htmlspecialchars($message)
            );
        }

        return sprintf(
            '<div class="form-errors"><p>Please correct the following errors:</p><ul>%s</ul></div>',
            implode('', $items)
        );
    }
}
