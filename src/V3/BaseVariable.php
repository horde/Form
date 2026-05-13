<?php

/**
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Form
 */

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde;
use Horde_Form_Translation;
use Horde\Form\Form;

/**
 * This class represents a single form variable that may be rendered as one or
 * more form fields.
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Form
 */
class BaseVariable implements Variable
{
    /**
     * The form instance this variable is assigned to.
     */
    public Form|\Horde_Form $form;

    /**
     * A short description of this variable's purpose.
     *
     * @var string
     */
    public $humanName;

    /**
     * The internally used name.
     *
     * @var string
     */
    public $varName;

    /**
     * Whether this is a required variable.
     *
     * @var boolean
     */
    public $required;

    /**
     * Whether this is a readonly variable.
     *
     * @var boolean
     */
    public $readonly;

    /**
     * A long description of the variable's purpose, special instructions, etc.
     *
     * @var string
     */
    public $description;

    /**
     * The variable help text.
     *
     * @var string
     */
    public $help;

    /**
     * Whether this is an array variable.
     *
     * @var boolean
     */
    public $_arrayVal;

    /**
     * The default value.
     *
     * @var mixed
     */
    public $_defValue = null;

    /**
     * A {@link Action} instance.
     */
    public Action|\Horde_Form_Action|null $_action = null;

    /**
     * Whether this variable is disabled.
     *
     * @var boolean
     */
    public $_disabled = false;

    /**
     * TODO
     *
     * @var boolean
     */
    public $_autofilled = false;

    /**
     * Whether this is a hidden variable.
     *
     * @var boolean
     */
    public $_hidden = false;

    /**
     * TODO
     *
     * @var array
     */
    public $_options = [];

    /**
     * Messages from validate() method.
     */
    protected string $message = '';

    /**
     * Returns the validation error message.
     *
     * @return string  The validation error message
     *
     * @api
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Creates a new form variable.
     *
     * @param string $humanName    Short description of the variable's purpose
     * @param string $varName      Internally used variable name
     * @param bool $required       Whether this is a required variable
     * @param bool $readonly       Whether this is a readonly variable
     * @param string|null $description  Long description, special instructions, etc.
     *
     * @api
     */
    public function __construct(
        $humanName,
        $varName,
        $required,
        $readonly = false,
        $description = null
    ) {
        $this->humanName   = $humanName;
        $this->varName     = $varName;
        $this->required    = $required;
        $this->readonly    = $readonly;
        $this->description = $description;
        $this->_arrayVal   = strpos($varName, '[]') !== false;
        $this->_action     = null;  // Fix: Initialize typed property
    }

    /**
     * Assign this variable to the specified form.
     *
     * @param Horde_Form $form  The form instance to assign this variable to.
      *
      * @api
     */
    public function setFormOb($form)
    {
        $this->form = $form;
    }

    /**
     * Sets a default value for this variable.
     *
     * @param mixed $value  A variable value.
      *
      * @api
     */
    public function setDefault($value)
    {
        $this->_defValue = $value;
    }

    /**
     * Returns this variable's default value.
     *
     * @return mixed  This variable's default value.
      *
      * @api
     */
    public function getDefault()
    {
        return $this->_defValue;
    }

    /**
     * Assigns an action to this variable.
     *
     * Example:
     * ```php
     * $v = $form->addVariable('My Variable', 'var1', 'text', false);
     * $v->setAction(BaseAction::factory('Submit'));
     * ```
     *
     * @param Action $action  An {@link Action} instance.
      *
      * @api
     */
    public function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * Returns whether this variable has an attached action.
     *
     * @return boolean  True if this variable has an attached action.
      *
      * @api
     */
    public function hasAction()
    {
        return !is_null($this->_action);
    }

    /**
     * Returns the attached action, or null.
     *
     * @return Action|null
      *
      * @api
     */
    public function getAction(): ?Action
    {
        return $this->_action;
    }

    /**
     * Makes this a hidden variable.
      *
      * @api
     */
    public function hide()
    {
        $this->_hidden = true;
    }

    /**
     * Returns whether this is a hidden variable.
     *
     * @return boolean  True if this a hidden variable.
      *
      * @api
     */
    public function isHidden()
    {
        return $this->_hidden;
    }

    /**
     * Disables this variable.
     *
     * Disabled fields are not submitted by browsers and are skipped
     * during form processing and validation.
     *
     * @return void
      *
      * @api
     */
    public function disable()
    {
        $this->_disabled = true;
    }

    /**
     * Returns whether this variable is disabled.
     *
     * @return boolean  True if this variable is disabled.
      *
      * @api
     */
    public function isDisabled()
    {
        return $this->_disabled;
    }

    /**
     * Return the short description of this variable.
     *
     * @return string  A short description
      *
      * @api
     */
    public function getHumanName()
    {
        return $this->humanName;
    }

    /**
     * Returns the internally used variable name.
     *
     * @return string  This variable's internal name.
      *
      * @api
     */
    public function getVarName()
    {
        return $this->varName;
    }

    /**
     * Returns this variable's type.
     *
     * @return BaseVariable  This variable's instance.
     */
    // TODO: Eliminate after V3 transition
    public function getType()
    {
        // TODO: Eliminate after V3 transition
        self::Deprecated("Warning: Method 'getType()' is deprecated, please remove '->getType()'");
        return $this;
    }

    /**
     * Returns the name of this variable's type.
     *
     * @return string  This variable's {@link Horde_Form_Type} name.
     *
     * Override with a simple return 'literal' string in your own types.
      *
      * @api
     */
    public function getTypeName(): string
    {
        $parts = explode('\\', $this::class);
        $app =  strtolower($parts[0]);
        $name =  strtolower(substr($parts[count($parts) - 1], 0, -8));
        if ($app !== 'horde') {
            // legacy
            $name = $app . '_form_type_' . $name;
        }
        return $name;
    }

    /**
     * Returns whether this is a required variable.
     *
     * @return boolean  True if this is a required variable.
      *
      * @api
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Returns whether this is a readonly variable.
     *
     * @return boolean  True if this a readonly variable.
      *
      * @api
     */
    public function isReadonly()
    {
        return $this->readonly;
    }

    /**
     * Returns the possible values of this variable.
     *
     * @return array  The possible values of this variable or null.
      *
      * @api
     */
    public function getValues(...$params): ?array
    {
        return null;
    }

    /**
     * Returns whether this variable has a long description.
     *
     * @return boolean  True if this variable has a long description.
      *
      * @api
     */
    public function hasDescription(): bool
    {
        return !empty($this->description);
    }

    /**
     * Returns this variable's long description.
     *
     * @return string  This variable's long description.
      *
      * @api
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns whether this is an array variable.
     *
     * @return boolean  True if this an array variable.
      *
      * @api
     */
    public function isArrayVal()
    {
        return $this->_arrayVal;
    }

    /**
     * Returns whether this variable is to upload a file.
     *
     * @return boolean  True if variable is to upload a file.
      *
      * @api
     */
    public function isUpload()
    {
        return $this->getTypeName() == 'file';
    }

    /**
     * Assigns a help text to this variable.
     *
     * @param string $help  The variable help text.
      *
      * @api
     */
    public function setHelp($help)
    {
        $this->form->_help = true;
        $this->help = $help;
    }

    /**
     * Returns whether this variable has some help text assigned.
     *
     * @return boolean  True if this variable has a help text.
      *
      * @api
     */
    public function hasHelp()
    {
        return !empty($this->help);
    }

    /**
     * Returns the help text of this variable.
     *
     * @return string  This variable's help text.
      *
      * @api
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * Sets a variable option.
     *
     * @param string $option  The option name.
     * @param mixed $val      The option's value.
      *
      * @api
     */
    public function setOption($option, $val)
    {
        $this->_options[$option] = $val;
    }

    /**
     * Returns a variable option's value.
     *
     * @param string $option  The option name.
     *
     * @return mixed          The option's value.
      *
      * @api
     */
    public function getOption($option)
    {
        return $this->_options[$option] ?? null;
    }

    /**
     * Processes the submitted value of this variable according to the rules of
     * the variable type.
     *
     * @param Horde_Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     *
     * @return mixed           Processed value of the variable, depending on the variable type.
     */

    /**
     * V3 wrapper for getInfo() - returns value directly.
     *
     * In lib/, getInfo() had signature: getInfo($vars, &$info)
     * In V3, we return the value directly (no reference parameter).
     *
     * @param Horde_Variables $vars  The variables object
     * @param mixed ...$args  Ignored (for interface compatibility)
     * @return mixed  The variable value
     * @deprecated The second parameter ($info) is deprecated/ignored
      *
      * @api
     */
    public function getInfo($vars, ...$args)
    {
        if (count($args) > 0) {
            self::Deprecated('Warning: The second ($info) parameter in getInfo() is deprecated/ignored');
        }
        return $this->getInfoV3($vars);
    }

    /**
     * Extract variable value from form variables.
     *
     * Internal method used by getInfo() wrapper. Subclasses override this
     * to provide type-specific value extraction logic.
     *
     * @param Horde_Variables $vars  Form variables
     *
     * @return mixed  Extracted value
     *
     * @internal
     */
    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        return $this->getValue($vars);
    }

    /**
     * Returns whether this variable if it had the "trackchange" option set
     * has actually been changed.
     *
     * @param Horde_Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     *
     * @return ?boolean Null if this variable doesn't have the "trackchange"
     *                  option set or the form wasn't submitted yet. A boolean
     *                  indicating whether the variable was changed otherwise.
      *
      * @api
     */
    public function wasChanged($vars)
    {
        if (!$this->getOption('trackchange')) {
            return null;
        }
        $old = $vars->get('__old_' . $this->getVarName());
        if (is_null($old)) {
            return null;
        }
        return $old != $vars->get($this->getVarName());
    }

    /**
     * Validates this variable.
     *
     * @param Horde_Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     *
     * @return boolean  True if the variable validated.
      *
      * @api
     */
    public function validate($vars, ...$args)
    {
        if (count($args) > 0) {
            self::Deprecated('Warning: The second ($message) parameter in validate() is deprecated/ignored');
        }

        if ($this->_arrayVal) {
            $vals = $this->getValue($vars);
            if (!is_array($vals)) {
                if ($this->required) {
                    return $this->invalid('This field is required.');
                }
                return true;
            }

            // Check if array is empty when required
            if (empty($vals) && $this->required) {
                return $this->invalid('This field is required.');
            }

            foreach ($vals as $i => $value) {
                if ($value === null && $this->required) {
                    return $this->invalid('This field is required.');
                }

                if (!$this->isValid($vars, $value)) {
                    return false;
                }
            }
        } else {
            $value = $this->getValue($vars);
            if (!$this->isValid($vars, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the submitted or default value of this variable.
     * If an action is attached to this variable, the value will get passed to
     * the action object.
     *
     * @param Horde_Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     * @param integer $index   If the variable is an array variable, this
     *                         specifies the array element to return.
     *
     * @return mixed  The variable or element value.
     */
    /* final */ public function getValue($vars, $index = null)
    {
        if ($this->_arrayVal) {
            $name = str_replace('[]', '', $this->varName);
        } else {
            $name = $this->varName;
        }

        $value = $vars->get($name);
        $wasset = $vars->exists($name);
        if (!$wasset) {
            $value = $this->getDefault();
        }

        if ($this->_arrayVal && !is_null($index)) {
            if (!$wasset && !is_array($value)) {
                $return = $value;
            } else {
                $return = $value[$index] ?? null;
            }
        } else {
            $return = $value;
        }

        if ($this->hasAction()) {
            $this->_action->setValues($vars, $return, $this->_arrayVal);
        }

        return $return;
    }

    /**
     * Resolve this variable's value from a plain array.
     *
     * V3-native alternative to getValue(Horde_Variables). Does the same
     * lookup/default-fallback but works directly with arrays and does NOT
     * trigger attached actions (actions are a submission-time concern,
     * not a rendering concern).
     *
     * Supports bracket-notation variable names (e.g., 'billing[street]')
     * by traversing nested arrays.
     *
     * @param array<string, mixed> $vars  Form variables as plain array
     * @param int|null $index  Array element index (for array variables)
     * @return mixed  The resolved value
     */
    public function resolveValue(array $vars, ?int $index = null): mixed
    {
        if ($this->_arrayVal) {
            $name = str_replace('[]', '', $this->varName);
        } else {
            $name = $this->varName;
        }

        [$value, $wasset] = $this->resolveNestedKey($vars, $name);
        if (!$wasset) {
            $value = $this->getDefault();
        }

        if ($this->_arrayVal && $index !== null) {
            if (!$wasset && !is_array($value)) {
                return $value;
            }
            return $value[$index] ?? null;
        }

        return $value;
    }

    /**
     * Resolve a possibly bracket-notated key from a nested array.
     *
     * Handles both simple keys ('name') and bracket notation
     * ('billing[street]') by traversing the array hierarchy.
     *
     * @param array $vars  Form variables
     * @param string $name  Variable name, possibly with brackets
     * @return array{mixed, bool}  [value, wasSet]
     */
    private function resolveNestedKey(array $vars, string $name): array
    {
        // Fast path: no brackets, simple key lookup
        if (!str_contains($name, '[')) {
            return [$vars[$name] ?? null, array_key_exists($name, $vars)];
        }

        // Parse bracket notation: 'billing[street]' → ['billing', 'street']
        $keys = explode('[', str_replace(']', '', $name));
        $current = $vars;
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return [null, false];
            }
            $current = $current[$key];
        }
        return [$current, true];
    }

    // Former Type-related methods

    /**
     * Logs a deprecation warning message.
     *
     * Helper method for logging deprecation warnings during the lib/ to V3
     * migration. Includes file and line number from the call stack.
     *
     * @param string $message  The deprecation warning message
     * @param int $level       Stack trace depth (default: 2)
     *
     * @return void
     *
     * @todo This method should be removed after V3 transition is complete
     */
    public static function Deprecated($message, $level = 1)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level + 1);
        if (isset($trace[$level])) {
            $trace = $trace[$level];
            $message .= sprintf(' in %s (line %d)', $trace['file'], $trace['line']);
        }

        Horde::log($message, 'WARN');
    }

    /**
     * Gets internal property value by name.
     *
     * Accesses internal properties using underscore-prefixed naming convention
     * (e.g., 'size' accesses $this->_size).
     *
     * @param string $property  Property name (without underscore prefix)
     *
     * @return mixed  Property value, or null if not set
      *
      * @api
     */
    public function getProperty($property)
    {
        $prop = '_' . $property;
        return $this->$prop ?? null;
    }

    /**
     * Magic getter for accessing internal properties.
     *
     * Provides property access using underscore-prefixed naming convention.
     * Special handling for deprecated 'type' property which triggers a
     * deprecation warning.
     *
     * @param string $property  Property name
     *
     * @return mixed  Property value, or $this for deprecated 'type' property
     */
    public function __get($property)
    {
        // TODO: Eliminate after V3 transition
        if ($property == 'type') {
            self::Deprecated("Warning: Variable property 'type' is deprecated, please remove '->type'");
            return $this;
        }

        return $this->getProperty($property);
    }

    /**
     * Sets internal property value by name.
     *
     * Sets internal properties using underscore-prefixed naming convention
     * (e.g., 'size' sets $this->_size).
     *
     * @param string $property  Property name (without underscore prefix)
     * @param mixed $value      Value to set
     *
     * @return void
      *
      * @api
     */
    public function setProperty($property, $value)
    {
        $prop = '_' . $property;
        $this->$prop = $value;
    }

    /**
     * Magic setter for internal properties.
     *
     * Delegates to setProperty() for underscore-prefixed property access.
     *
     * @param string $property  Property name
     * @param mixed $value      Value to set
     *
     * @return void
     */
    public function __set($property, $value)
    {
        $this->setProperty($property, $value);
    }

    /**
     * Initialize (kind of constructor) - Parameter list may vary on overloading
      *
      * @api
     */
    public function init(...$params) {}

    /**
     * Hook called on form submission.
     *
     * Override this method in subclasses to perform actions when the form
     * containing this variable is submitted. This is called after validation
     * but before data processing.
     *
     * @param Horde_Variables $vars  Submitted form variables
     *
     * @return void
      *
      * @api
     */
    public function onSubmit($vars) {}

    /**
     * Use $this->getMessage() to retrieve error messages.
      *
      * @internal
     */
    protected function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        $this->message = '<strong>Error:</strong> Variable::isValid() called - should be overridden<br />';
        return false;
    }

    public function invalid(string $message): bool
    {
        $this->message = $message;
        return false;
    }

    public function about(): array
    {
        return [ 'name' => $this->getTypeName() ];
    }

}
