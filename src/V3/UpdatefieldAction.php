<?php
declare(strict_types=1);

/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

/**
 * UpdateFieldAction updates the value of one form variable as the variable(s)
 * the action is attached to are updated.
 *
 * This action concatenates multiple field values into a target field using
 * a format string with sprintf-style placeholders.
 *
 * Format of the $params passed to the constructor:
 * ```php
 * $params = [
 *     'target' => 'field_name',     // Name of field to update
 *     'format' => '%s - %s',         // Format string (sprintf-style with %s)
 *     'fields' => ['field1', 'field2'] // Fields to concatenate
 * ];
 * ```
 *
 * Example: Build full name from first and last name:
 * ```php
 * $action = new UpdateFieldAction([
 *     'target' => 'fullname',
 *     'format' => '%s %s',
 *     'fields' => ['firstname', 'lastname']
 * ]);
 * $var->setAction($action);
 * ```
 *
 * Example: Build address string:
 * ```php
 * $action = new UpdateFieldAction([
 *     'target' => 'full_address',
 *     'format' => '%s, %s %s',
 *     'fields' => ['street', 'city', 'zipcode']
 * ]);
 * ```
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class UpdatefieldAction extends BaseAction
{
    /**
     * This action triggers on change, load, and keyup.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onchange', 'onload', 'onkeyup'];

    /**
     * Get JavaScript code for update field behavior.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name this action applies to
     * @return string  JavaScript code
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        return "updateField_{$this->id}();";
    }

    /**
     * This action doesn't set values server-side (JavaScript only).
     *
     * @param \Horde_Variables $vars  The variables object
     * @param mixed $sourceVal  The source value
     * @param int|null $index  Array index
     * @param bool $arrayVal  Whether dealing with array values
     */
    public function setValues(\Horde_Variables $vars, $sourceVal, ?int $index = null, bool $arrayVal = false): void
    {
        // This action is JavaScript-only, no server-side logic needed
    }

    /**
     * Print JavaScript code for this action.
     *
     * @return string  JavaScript code
     */
    public function printJavaScript(): string
    {
        $format = $this->params['format'] ?? '';
        $fields = $this->params['fields'] ?? [];
        $target = $this->params['target'] ?? '';

        if (!$format || empty($fields) || !$target) {
            return '';
        }

        // Parse the format string
        $pieces = explode('%s', $format);
        $valFirst = (substr($format, 0, 2) == '%s');

        if ($valFirst) {
            array_shift($pieces);
        }
        if (substr($format, -2) == '%s') {
            array_pop($pieces);
        }

        // Build JavaScript concatenation arguments
        $args = [];
        $fieldsCopy = $fields;

        if ($valFirst) {
            $fieldId = array_shift($fieldsCopy);
            $args[] = "document.getElementById('{$fieldId}').value";
        }

        while (count($pieces)) {
            $piece = array_shift($pieces);
            $args[] = "'" . addslashes($piece) . "'";

            if (!empty($fieldsCopy)) {
                $fieldId = array_shift($fieldsCopy);
                $args[] = "document.getElementById('{$fieldId}').value";
            }
        }

        // Build the JavaScript function
        $argsStr = implode(' + ', $args);

        return <<<JS
// Update field function for action {$this->id}
function updateField_{$this->id}() {
    var target = document.getElementById('{$target}');
    if (target) {
        target.value = ({$argsStr}).replace(/(^ +| +$)/, '').replace(/ +/g, ' ');
    }
}
JS;
    }
}
