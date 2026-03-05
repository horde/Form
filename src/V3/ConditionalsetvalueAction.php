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
 * ConditionalSetValueAction sets the value of one form variable based on
 * the value of the variable the action is attached to.
 *
 * This action maps source values to target values using a lookup table.
 *
 * Format of the $params passed to the constructor:
 * ```php
 * $params = [
 *     'target' => 'field_name',  // Name of field to update
 *     'map' => [                  // Value mapping
 *         'source_value1' => 'target_value1',
 *         'source_value2' => 'target_value2',
 *     ]
 * ];
 * ```
 *
 * Example: Set state code based on state name:
 * ```php
 * $action = new ConditionalSetValueAction([
 *     'target' => 'state_code',
 *     'map' => [
 *         'California' => 'CA',
 *         'New York' => 'NY',
 *         'Texas' => 'TX'
 *     ]
 * ]);
 * $var->setAction($action);
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
class ConditionalsetvalueAction extends BaseAction
{
    /**
     * This action triggers on change and page load.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onchange', 'onload'];

    /**
     * Get JavaScript code for conditional set value behavior.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name this action applies to
     * @return string  JavaScript code
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        $target = $this->params['target'] ?? '';

        // Generate field IDs
        $sourceId = $this->getFieldId($renderer, $varname);
        $targetId = $this->getFieldId($renderer, $target);

        return "mapValue_{$this->id}('{$sourceId}', '{$targetId}');";
    }

    /**
     * Set values in the variables object based on the source value.
     *
     * @param \Horde_Variables $vars  The variables object
     * @param mixed $sourceVal  The source value
     * @param bool $arrayVal  Whether dealing with array values
     */
    public function setValues(\Horde_Variables $vars, $sourceVal, bool $arrayVal = false): void
    {
        $map = $this->params['map'] ?? [];
        $target = $this->params['target'] ?? '';

        if (!$target) {
            return;
        }

        if ($arrayVal && is_array($sourceVal)) {
            // Handle array values
            $i = 0;
            foreach ($sourceVal as $val) {
                if (isset($map[$val])) {
                    $vars->set($target, $map[$val], $i);
                }
                $i++;
            }
        } else {
            // Handle single value
            if (isset($map[$sourceVal])) {
                $vars->set($target, $map[$sourceVal]);
            }
        }
    }

    /**
     * Print JavaScript code for this action.
     *
     * @return string  JavaScript code
     */
    public function printJavaScript(): string
    {
        $map = $this->params['map'] ?? [];

        // Build JavaScript array for the map
        $jsMap = [];
        foreach ($map as $key => $val) {
            $jsMap[] = json_encode((string)$key) . ': ' . json_encode((string)$val);
        }
        $jsMapStr = '{' . implode(', ', $jsMap) . '}';

        return <<<JS
// Map value function for action {$this->id}
var _map_{$this->id} = {$jsMapStr};

function mapValue_{$this->id}(sourceId, targetId) {
    var source = document.getElementById(sourceId);
    var target = document.getElementById(targetId);

    if (!source || !target) {
        return;
    }

    var sourceValue = source.value;
    if (source.selectedIndex !== undefined) {
        // For select elements, use selected option value
        sourceValue = source.options[source.selectedIndex]?.value || '';
    }

    // Check if we have a mapping for this value
    if (_map_{$this->id}[sourceValue] !== undefined) {
        var newval = _map_{$this->id}[sourceValue];
        var replace = true;
    } else {
        var newval = '';
        var replace = false;

        // Check if current target value is in our map
        for (var key in _map_{$this->id}) {
            if (target.value == _map_{$this->id}[key]) {
                replace = true;
                break;
            }
        }
    }

    // Only update if we should replace
    if (replace) {
        target.value = newval;
    }
}
JS;
    }

    /**
     * Get field ID from renderer.
     *
     * @param mixed $renderer  The renderer
     * @param string $varname  Variable name
     * @return string  Field ID
     */
    protected function getFieldId($renderer, string $varname): string
    {
        // Try to use renderer's method if available
        if (is_object($renderer) && method_exists($renderer, '_genID')) {
            return $renderer->_genID($varname, false);
        }

        // Otherwise return varname as-is
        return $varname;
    }
}
