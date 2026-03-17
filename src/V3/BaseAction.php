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

use Horde\Form\Form;

/**
 * Base implementation of the Action interface.
 *
 * Provides common functionality for all actions. Subclasses override specific
 * methods to implement their behavior.
 *
 * V3 improvements:
 * - Removed singleton pattern (antipattern)
 * - Removed PHP 4 constructor
 * - Strict typing
 * Named parameters
 * - Modern factory pattern
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Action PSR-0 legacy equivalent in lib/Horde/Form/Action.php
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
abstract class BaseAction implements Action
{
    /**
     * Unique action identifier.
     */
    protected string $id;

    /**
     * Action parameters.
     *
     * @var array<string, mixed>
     */
    protected array $params;

    /**
     * Trigger events for this action.
     *
     * @var array<string>|null
     */
    protected ?array $trigger = null;

    /**
     * Create a new action.
     *
     * @param array<string, mixed>|null $params  Action parameters
      *
      * @api
     */
    public function __construct(?array $params = null)
    {
        $this->params = $params ?? [];
        $this->id = md5((string)mt_rand());
    }

    /**
     * Get action trigger events.
     *
     * @return array<string>|null  Event names or null
      *
      * @api
     */
    public function getTrigger(): ?array
    {
        return $this->trigger;
    }

    /**
     * Get action ID.
      *
      * @api
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get JavaScript code for this action.
     *
     * Default implementation returns empty string. Subclasses override.
     *
     * @param Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name
     * @return string  JavaScript code
      *
      * @api
     */
    public function getActionScript(Form $form, $renderer, string $varname): string
    {
        return '';
    }

    /**
     * Print JavaScript for this action.
     *
     * Default implementation does nothing. Subclasses override if needed.
      *
      * @api
     */
    public function printJavaScript(): void
    {
        // Default: no output
    }

    /**
     * Get target field name for this action.
     *
     * @return string|null  Target field name or null
      *
      * @api
     */
    public function getTarget(): ?string
    {
        return $this->params['target'] ?? null;
    }

    /**
     * Set values based on action logic.
     *
     * Default implementation does nothing. Subclasses override.
     *
     * @param mixed $vars  Form variables
     * @param mixed $sourceVal  Source value
     * @param int|null $index  Array index (if applicable)
     * @param bool $arrayVal  Whether value is an array
      *
      * @api
     */
    public function setValues($vars, $sourceVal, ?int $index = null, bool $arrayVal = false): void
    {
        // Default: no action
    }

    /**
     * Factory method: Create Action instance from action name.
     *
     * Maps action names like 'ConditionalEnable', 'SumFields' to Action classes.
     * Supports app-specific actions via [app, action] array format.
     *
     * Examples:
     * - factory('ConditionalEnable', $params) → ConditionalEnableAction
     * - factory(['myapp', 'CustomAction'], $params) → Myapp\Form\V3\CustomActionAction
     *
     * @param string|array $action  Action name or [app, action]
     * @param array<string, mixed>|null $params  Action parameters
     * @return Action  Created action instance
     * @throws \InvalidArgumentException  If action class not found
      *
      * @api
     */
    public static function factory(string|array $action, ?array $params = null): Action
    {
        if (is_array($action)) {
            // App-specific action: ['myapp', 'CustomAction']
            [$app, $actionName] = $action;
            $class = ucfirst($app) . '\\Form\\V3\\' . ucfirst($actionName) . 'Action';
        } else {
            // Standard Horde action
            $actionName = basename($action);
            $class = 'Horde\\Form\\V3\\' . ucfirst($actionName) . 'Action';
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(
                "Action class '$class' not found for action '$actionName'"
            );
        }

        return new $class($params);
    }
}
