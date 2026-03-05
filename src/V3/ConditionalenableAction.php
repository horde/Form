<?php
declare(strict_types=1);

/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Matt Kynaston <matt@kynx.org>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

/**
 * ConditionalEnableAction enables or disables a form element based on the
 * value of another element.
 *
 * Format of the $params passed to the constructor:
 * ```php
 * $params = [
 *     'target'  => 'field_name',     // Name of element this is conditional on
 *     'enabled' => true,              // true or false
 *     'values'  => [1, 2, 3]          // Target values to check
 * ];
 * ```
 *
 * Example: Enable field if 'country' is 'US' or 'CA':
 * ```php
 * $action = new ConditionalEnableAction([
 *     'target'  => 'country',
 *     'enabled' => true,
 *     'values'  => ['US', 'CA']
 * ]);
 * $var->setAction($action);
 * ```
 *
 * @author    Matt Kynaston <matt@kynx.org>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class ConditionalenableAction extends BaseAction
{
    /**
     * This action triggers on page load.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onload'];

    /**
     * Get JavaScript code for conditional enable behavior.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name this action applies to
     * @return string  JavaScript code
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        // TODO: Inject Horde_PageOutput or use modern asset management
        // For now, assume form_helpers.js is loaded
        // $GLOBALS['injector']->getInstance('Horde_PageOutput')->addScriptFile('form_helpers.js', 'horde');

        $formName = $form->getName();
        $target = $this->params['target'] ?? '';
        $enabled = $this->params['enabled'] ?? true;

        // Normalize enabled to string for JavaScript
        if (!is_string($enabled)) {
            $enabled = $enabled ? 'true' : 'false';
        }

        // Get values to check
        $vals = $this->params['values'] ?? [];
        $vals = is_array($vals) ? $vals : [$vals];

        // Build JavaScript arguments
        $args = "'{$varname}', {$enabled}, '" . implode("','", $vals) . "'";

        // Return JavaScript code
        // This uses the checkEnabled() function from form_helpers.js
        return "if (addEvent(document.getElementById('{$formName}').{$target}, 'onchange', \"checkEnabled(this, {$args});\")) { "
            . "  checkEnabled(document.getElementById('{$formName}').{$varname}, {$args}); };";
    }
}
