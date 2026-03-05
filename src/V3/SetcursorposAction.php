<?php
declare(strict_types=1);

/**
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

/**
 * SetCursorPosAction places the cursor in a text field at a specific position.
 *
 * The params array contains the desired cursor position (start, end).
 *
 * Format of the $params passed to the constructor:
 * ```php
 * $params = [10, 15];  // Set cursor from position 10 to 15
 * // Or single position:
 * $params = [5];       // Set cursor at position 5
 * ```
 *
 * Example: Place cursor at end of field:
 * ```php
 * $action = new SetCursorPosAction([0, 999]);
 * $var->setAction($action);
 * ```
 *
 * Example: Select first 5 characters:
 * ```php
 * $action = new SetCursorPosAction([0, 5]);
 * $var->setAction($action);
 * ```
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2006-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class SetcursorposAction extends BaseAction
{
    /**
     * This action triggers on page load.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onload'];

    /**
     * Get JavaScript code for setting cursor position.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name this action applies to
     * @return string  JavaScript code
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        // TODO: Add form_helpers.js to asset manager
        // For now, assume it's available or provide inline implementation

        $formName = $form->getName();
        $pos = implode(',', $this->params);

        return "setCursorPosition_{$this->id}(document.forms['" .
            htmlspecialchars($formName) . "'].elements['" .
            htmlspecialchars($varname) . "'].id, {$pos});";
    }

    /**
     * Print JavaScript code for this action.
     *
     * @return string  JavaScript code
     */
    public function printJavaScript(): string
    {
        // Provide inline implementation of cursor positioning
        return <<<JS
// Set cursor position function for action {$this->id}
function setCursorPosition_{$this->id}(elementId, start, end) {
    var element = document.getElementById(elementId);
    if (!element) {
        return;
    }

    // Default end to start if not provided
    if (end === undefined) {
        end = start;
    }

    // For text inputs and textareas
    if (element.setSelectionRange) {
        element.focus();
        element.setSelectionRange(start, end);
    } else if (element.createTextRange) {
        // IE fallback
        var range = element.createTextRange();
        range.collapse(true);
        range.moveStart('character', start);
        range.moveEnd('character', end - start);
        range.select();
    }
}
JS;
    }
}
