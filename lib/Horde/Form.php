<?php

/**
 * Copyright 2001-2026 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Form
 */
// Restrict legacy loader
if (!class_exists('Horde_Form_Type')) {
    require_once 'Horde/Form/Type.php';
}

use Horde\Util\ArrayUtils;

/**
 * Horde_Form Master Class.
 *
 * @see Horde\Form\V3\BaseForm PSR-4 equivalent in src/V3/
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2001-2007 Robert E. Coyle
 * @copyright 2001-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Form
 */

Horde_Form::$legacy = class_exists(Horde_Form_Type::class);

class Horde_Form
{
    public static bool $legacy = false;
    protected static $init_params_cache = [];

    protected $_name = '';
    protected $_title = '';
    protected $_extra = '';
    protected $_vars;
    protected $_submit = [];
    protected $_reset = false;
    protected $_errors = [];
    protected $_submitted = null;
    public $_sections = [];
    protected $_open_section = null;
    protected $_currentSection = null;
    protected $_variables = [];
    protected $_hiddenVariables = [];
    protected $_useFormToken = true;
    protected $_autofilled = false;
    protected $_enctype = null;
    public $_help = false;

    public function __construct($vars, $title = '', $name = null)
    {
        if (empty($name)) {
            static $counter = 0;
            $name = Horde_String::lower(get_class($this)) . '_' . (++$counter);
        }
        $name = str_replace('\\', '_', $name);

        $this->_vars = $vars;
        $this->_title = $title;
        $this->_name = $name;
    }

    /**
     * @deprecated Due for removal in 3.0.0 final release.
     */
    public function singleton($form, $vars, $title = '', $name = null)
    {
        static $instances = [];

        $signature = serialize([$form, $vars, $title, $name]);
        if (!isset($instances[$signature])) {
            if (class_exists($form)) {
                $instances[$signature] = new $form($vars, $title, $name);
            } else {
                $instances[$signature] = new Horde_Form($vars, $title, $name);
            }
        }

        return $instances[$signature];
    }

    public function setVars($vars)
    {
        $this->_vars = $vars;
    }

    public function getVars()
    {
        return $this->_vars;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function getExtra()
    {
        return $this->_extra;
    }

    public function setExtra($extra)
    {
        $this->_extra = $extra;
    }

    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets or gets whether the form should be verified by tokens.
     * Tokens are used to verify that a form is only submitted once.
     *
     * @param boolean $token  If specified, sets whether to use form tokens.
     *
     * @return boolean  Whether form tokens are being used.
     */
    public function useToken($token = null)
    {
        if (!is_null($token)) {
            $this->_useFormToken = $token;
        }
        return $this->_useFormToken;
    }

    /**
     * Get the renderer for this form, either a custom renderer or the
     * standard one.
     *
     * To use a custom form renderer, your form class needs to
     * override this function:
     * <code>
     * function getRenderer()
     * {
     *     return new CustomFormRenderer();
     * }
     * </code>
     *
     * ... where CustomFormRenderer is the classname of the custom
     * renderer class, which should extend Horde_Form_Renderer.
     *
     * @param array $params  A hash of renderer-specific parameters.
     *
     * @return object Horde_Form_Renderer  The form renderer.
     */
    public function getRenderer($params = [])
    {
        $renderer = new Horde_Form_Renderer($params);
        return $renderer;
    }


    /**
     * Create a new Horde_Form_Variable instance without attaching it to the form.
     *
     * This static factory method handles type instantiation and can be used
     * independently of any form instance. To add a variable to the form, use
     * addVariable() or insertVariableBefore() instead.
     *
     * Note: parameter order differs from addVariable() - $params precedes
     * $required to better reflect that type parameters are part of the type
     * definition rather than the variable's role in the form.
     *
     * @param string  $humanName   The human-readable label for the field.
     * @param string  $varName     The internal variable name.
     * @param string  $type        The field type identifier.
     * @param array   $params      Type initialization parameters.
     * @param boolean $required    Whether the field is required.
     * @param boolean $readonly    Whether the field is read-only.
     * @param string  $description Optional field description/help text.
     *
     * @return Horde_Form_Variable
     * @throws Horde_Exception If the type class does not exist.
     */
    public static function createVariable(
        $humanName,
        $varName,
        $type,
        $params = [],
        $required = false,
        $readonly = false,
        $description = null,
    ) {
        $arr = explode(':', $type, 2);
        if (count($arr) == 2) {
            $app = ucfirst($arr[0]);
            $name = $arr[1];
        } else {
            $app = 'Horde';
            $name = $arr[0];
        }

        $name = ucfirst($name);
        $class = $app . '\\Form\\V3\\' . $name . 'Variable';
        $modern = class_exists($class);
        if ($modern) {
            $var = new $class(
                $humanName,
                $varName,
                $required,
                $readonly,
                $description
            );
        } elseif (self::$legacy) {
            $class = $app . '_Form_Type_' . $name;
            $var = class_exists($class) ? new $class() : null;
        } else {
            $var = null;
        }

        if ($var === null) {
            throw new Horde_Exception(sprintf('Nonexistent class "%s" for field type "%s"', $class, $name));
        }

        // retrieve list of parameters
        $keys = self::$init_params_cache[$class] ?? null;
        if (is_null($keys)) {
            $keys = array_keys($var->about()['params'] ?? []);
            self::$init_params_cache[$class] = $keys;
        }

        if (!$params) {
            $params = [];
        }

        // convert named parameters to positional
        $i = 0;
        $ni = 0;
        foreach ($keys as $key) {
            if (array_key_exists($key, $params)) {
                // make sure prior index(es) exist
                while ($ni < $i) {
                    if (!array_key_exists($ni, $params)) {
                        $params[$ni] = null;
                    }
                    ++$ni;
                }
                $params[$ni++] = $params[$key];
                unset($params[$key]);
            }
            ++$i;
        }

        $var->init(...$params);
        if ($modern) {
            return $var;
        }

        return new Horde_Form_Variable(
            $humanName,
            $varName,
            $var,
            $required,
            $readonly,
            $description
        );
    }

    public function setSection($section = '', $desc = '', $image = '', $expanded = true)
    {
        $this->_currentSection = $section === '' ? null : (string) $section;
        if (!count($this->_sections) && !$this->getOpenSection()) {
            $this->setOpenSection($section);
        }
        $this->_sections[$section]['desc'] = $desc;
        $this->_sections[$section]['expanded'] = $expanded;
        $this->_sections[$section]['image'] = $image;
    }

    public function getSectionDesc($section)
    {
        return $this->_sections[$section]['desc'];
    }

    public function getSectionImage($section)
    {
        return $this->_sections[$section]['image'];
    }

    public function setOpenSection($section)
    {
        $this->_vars->set('__formOpenSection', $section);
    }

    public function getOpenSection()
    {
        return $this->_vars->get('__formOpenSection');
    }

    public function getSectionExpandedState($section, $boolean = false)
    {
        if ($boolean) {
            /* Only the boolean value is required. */
            return $this->_sections[$section]['expanded'];
        }

        /* Need to return the values for use in styles. */
        if ($this->_sections[$section]['expanded']) {
            return 'block';
        } else {
            return 'none';
        }
    }

    /**
     * Get information about all form sections.
     *
     * Returns an array of section metadata for all sections in the form.
     * Useful for rendering section navigation or inspecting section structure.
     * Migration aid for V3 compatibility.
     *
     * @return array  Array of section info keyed by section name, each containing:
     *                - title: Section description/title
     *                - image: Section image (if any)
     *                - expanded: Whether section is expanded (boolean)
     *
     * @since 3.0.0
     */
    public function getSectionInfo(): array
    {
        $info = [];
        foreach ($this->_sections as $section => $data) {
            $info[$section] = [
                'title' => $data['desc'] ?? '',
                'image' => $data['image'] ?? '',
                'expanded' => $data['expanded'] ?? true,
            ];
        }
        return $info;
    }

    /**
     * Get the form encoding type.
     *
     * Returns the encoding type needed for the form (e.g., 'multipart/form-data'
     * for forms with file upload fields). This is automatically set when file
     * or image field types are added to the form.
     * Migration aid for V3 compatibility.
     *
     * @return string|null  The encoding type, or null if not set
     *
     * @since 3.0.0
     */
    public function getEnctype(): ?string
    {
        return $this->_enctype;
    }

    /**
     * Add a new form field variable to the form.
     */
    public function addVariable(
        $humanName,
        $varName,
        $type,
        $required,
        $readonly = false,
        $description = null,
        $params = []
    ) {
        return $this->insertVariableBefore(
            null,
            $humanName,
            $varName,
            $type,
            $required,
            $readonly,
            $description,
            $params
        );
    }

    /**
     * TODO
     */
    public function insertVariableBefore(
        $before,
        $humanName,
        $varName,
        $type,
        $required,
        $readonly = false,
        $description = null,
        $params = []
    ) {
        $var = self::createVariable(
            $humanName,
            $varName,
            $type,
            $params,
            $required,
            $readonly,
            $description
        );

        /* Set the form object reference in the var. */
        $var->setFormOb($this);

        $typeName = $var->getTypeName(); // same as lower-cased $type
        if ($typeName == 'enum'
            && !strlen($var->getPrompt())
            && count($var->getValues()) == 1) {
            $vals = array_keys($var->getValues());
            $this->_vars->add($var->varName, $vals[0]);
            $var->_autofilled = true;
        } elseif ($typeName == 'file'
                  || $typeName == 'image') {
            $this->_enctype = 'multipart/form-data';
        }

        $section = $this->_currentSection ?? '__base';
        if (!isset($this->_variables[$section])) {
            $this->_variables[$section] = [];
        }

        $vars = &$this->_variables[$section];

        if (is_null($before)) {
            $vars[] = $var;
        } else {
            $count = count($vars);
            $num = 0;
            while ($num < $count && $vars[$num]->getVarName() !== $before) {
                ++$num;
            }
            if ($num < $count) {
                array_splice($vars, $num, 0, [$var]);
            } else {
                $vars[] = $var;
            }
        }

        return $var;
    }

    /**
     * Removes a variable from the form.
     *
     * As only variables can be passed by reference, you need to call this
     * method this way if you want to pass a variable name:
     * <code>
     * $form->removeVariable('varname');
     * </code>
     *
     * @param Horde_Form_Variable|string $var  Either the variable's name or
     *                                         the variable to remove from the
     *                                         form.
     *
     * @return boolean  True if the variable was found (and deleted).
     */
    public function removeVariable($var)
    {
        foreach ($this->_variables as $section => $sectionVars) {
            foreach ($sectionVars as $i => $variable) {
                if (is_object($var) && $variable === $var || $variable->getVarName() === $var) {
                    array_splice($this->_variables[$section], $i, 1);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * TODO
     *
     * @todo Remove $readonly parameter. Hidden fields are read-only by
     *       definition.
     */
    public function addHidden(
        $humanName,
        $varName,
        $type,
        $required,
        $readonly = false,
        $description = null,
        $params = []
    ) {
        $var = self::createVariable(
            $humanName,
            $varName,
            $type,
            $params,
            $required,
            $readonly,
            $description
        );
        $var->hide();
        $this->_hiddenVariables[] = $var;
        return $var;
    }

    public function getVariables($flat = true, $withHidden = false)
    {
        if ($flat) {
            $vars = [];
            foreach ($this->_variables as $section) {
                foreach ($section as $var) {
                    $vars[] = $var;
                }
            }
            if ($withHidden) {
                foreach ($this->_hiddenVariables as $var) {
                    $vars[] = $var;
                }
            }
            return $vars;
        } else {
            return $this->_variables;
        }
    }

    public function setButtons($submit, $reset = false)
    {
        if ($submit === true || is_null($submit) || empty($submit)) {
            /* Default to 'Submit'. */
            $submit = [Horde_Form_Translation::t("Submit")];
        } elseif (!is_array($submit)) {
            /* Default to array if not passed. */
            $submit = [$submit];
        }
        /* Only if $reset is strictly true insert default 'Reset'. */
        if ($reset === true) {
            $reset = Horde_Form_Translation::t("Reset");
        }

        $this->_submit = $submit;
        $this->_reset = $reset;
    }

    public function appendButtons($submit)
    {
        if (!is_array($submit)) {
            $submit = [$submit];
        }

        $this->_submit = array_merge($this->_submit, $submit);
    }

    public function preserveVarByPost($vars, $varname, $alt_varname = '')
    {
        $value = $vars->getExists($varname, $wasset);

        /* If an alternate name is given under which to preserve use that. */
        if ($alt_varname) {
            $varname = $alt_varname;
        }

        if ($wasset) {
            $this->_preserveVarByPost($varname, $value);
        }
    }

    /**
     * @access private
     */
    public function _preserveVarByPost($varname, $value)
    {
        if (is_array($value)) {
            foreach ($value as $id => $val) {
                $this->_preserveVarByPost($varname . '[' . $id . ']', $val);
            }
        } else {
            $varname = htmlspecialchars($varname);
            $value = htmlspecialchars($value);
            printf(
                '<input type="hidden" name="%s" value="%s" />' . "\n",
                $varname,
                $value
            );
        }
    }

    public function open($renderer, $vars, $action, $method = 'get', $enctype = null)
    {
        if (is_null($enctype) && !is_null($this->_enctype)) {
            $enctype = $this->_enctype;
        }
        $renderer->open($action, $method, $this->_name, $enctype);

        if (!empty($this->_name)) {
            $this->_preserveVarByPost('formname', $this->_name);
        }

        if ($this->_useFormToken) {
            $token = Horde_Token::generateId($this->_name);
            $GLOBALS['session']->set('horde', 'form_secrets/' . $token, true);
            $this->_preserveVarByPost($this->_name . '_formToken', $token);
        }

        /* Loop through vars and check for any special cases to preserve. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            /* Preserve value if change has to be tracked. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                $this->preserveVarByPost($vars, $varname, '__old_' . $varname);
            }
        }

        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }
    }

    public function close($renderer)
    {
        $renderer->close();
    }

    /**
     * Renders the form for editing.
     *
     * @param Horde_Form_Renderer $renderer  A renderer instance, optional
     *                                       since Horde 3.2.
     * @param Variables $vars                A Variables instance, optional
     *                                       since Horde 3.2.
     * @param string $action                 The form action (url).
     * @param string $method                 The form method, usually either
     *                                       'get' or 'post'.
     * @param string $enctype                The form encoding type. Determined
     *                                       automatically if null.
     * @param boolean $focus                 Focus the first form field?
     */
    public function renderActive(
        $renderer = null,
        $vars = null,
        $action = '',
        $method = 'get',
        $enctype = null,
        $focus = true
    ) {
        if (is_null($renderer)) {
            $renderer = $this->getRenderer();
        }
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        if (is_null($enctype) && !is_null($this->_enctype)) {
            $enctype = $this->_enctype;
        }
        $renderer->open($action, $method, $this->getName(), $enctype);
        $renderer->listFormVars($this);

        if (!empty($this->_name)) {
            $this->_preserveVarByPost('formname', $this->_name);
        }

        if ($this->_useFormToken) {
            $token = Horde_Token::generateId($this->_name);
            $GLOBALS['session']->set('horde', 'form_secrets/' . $token, true);
            $this->_preserveVarByPost($this->_name . '_formToken', $token);
        }

        if (count($this->_sections)) {
            $this->_preserveVarByPost('__formOpenSection', $this->getOpenSection());
        }

        /* Loop through vars and check for any special cases to
         * preserve. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            /* Preserve value if change has to be tracked. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                $this->preserveVarByPost($vars, $varname, '__old_' . $varname);
            }
        }

        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }

        $renderer->beginActive($this->getTitle(), $this->getExtra());
        $renderer->renderFormActive($this, $vars);
        $renderer->submit($this->_submit, $this->_reset);
        $renderer->end();
        $renderer->close($focus);
    }

    /**
     * Renders the form for displaying.
     *
     * @param Horde_Form_Renderer $renderer  A renderer instance, optional
     *                                       since Horde 3.2.
     * @param Variables $vars                A Variables instance, optional
     *                                       since Horde 3.2.
     */
    public function renderInactive($renderer = null, $vars = null)
    {
        if (is_null($renderer)) {
            $renderer = $this->getRenderer();
        }
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        $renderer->_name = $this->_name;
        $renderer->beginInactive($this->getTitle(), $this->getExtra());
        $renderer->renderFormInactive($this, $vars);
        $renderer->end();
    }

    public function preserve($vars)
    {
        if ($this->_useFormToken) {
            $token = Horde_Token::generateId($this->_name);
            $GLOBALS['session']->set('horde', 'form_secrets/' . $token, true);
            $this->_preserveVarByPost($this->_name . '_formToken', $token);
        }

        $variables = $this->getVariables();
        foreach ($variables as $var) {
            $varname = $var->getVarName();

            /* Save value of individual components. */
            switch ($var->getTypeName()) {
                case 'passwordconfirm':
                case 'emailconfirm':
                    $this->preserveVarByPost($vars, $varname . '[original]');
                    $this->preserveVarByPost($vars, $varname . '[confirm]');
                    break;

                case 'monthyear':
                    $this->preserveVarByPost($vars, $varname . '[month]');
                    $this->preserveVarByPost($vars, $varname . '[year]');
                    break;

                case 'monthdayyear':
                    $this->preserveVarByPost($vars, $varname . '[month]');
                    $this->preserveVarByPost($vars, $varname . '[day]');
                    $this->preserveVarByPost($vars, $varname . '[year]');
                    break;
            }

            $this->preserveVarByPost($vars, $varname);
        }
        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }
    }

    public function unsetVars($vars)
    {
        foreach ($this->getVariables() as $var) {
            $vars->remove($var->getVarName());
        }
    }

    /**
     * Validates the form, checking if it really has been submitted by calling
     * isSubmitted() and if true does any onSubmit() calls for variable types
     * in the form. The _submitted variable is then rechecked.
     *
     * @param Variables $vars       A Variables instance, optional since Horde
     *                              3.2.
     * @param boolean $canAutofill  Can the form be valid without being
     *                              submitted?
     *
     * @return boolean  True if the form is valid.
     */
    public function validate($vars = null, $canAutoFill = false)
    {
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        // Clear previous validation errors
        $this->_errors = [];

        /* Get submitted status. */
        if ($this->isSubmitted() || $canAutoFill) {
            /* Form was submitted or can autofill; check for any variable
             * types' onSubmit(). */
            $this->onSubmit($vars);

            /* Recheck submitted status. */
            if (!$this->isSubmitted() && !$canAutoFill) {
                return false;
            }
        } else {
            /* Form has not been submitted; return false. */
            return false;
        }

        $message = '';
        $this->_autofilled = true;

        if ($this->_useFormToken) {
            try {
                $tokenSource = $GLOBALS['injector']->getInstance('Horde_Token');
                $passedToken = $vars->get($this->_name . '_formToken');
                if (!empty($passedToken)
                    && !$tokenSource->verify($passedToken)) {
                    $this->_errors['_formToken'] = Horde_Form_Translation::t("This form has already been processed.");
                }
            } catch (Horde_Exception $e) {
            }
            if (!$GLOBALS['session']->get('horde', 'form_secrets/' . $passedToken)) {
                $this->_errors['_formSecret'] = Horde_Form_Translation::t("Required secret is invalid - potentially malicious request.");
            }
        }

        foreach ($this->getVariables() as $var) {
            $this->_autofilled = $var->_autofilled && $this->_autofilled;
            $valid = $var instanceof Horde_Form_Variable
                ? $var->validate($vars, $message)
                : $var->validate($vars);
            if (!$valid) {
                $this->_errors[$var->getVarName()] = $var->getMessage();
            }
        }

        if ($this->_autofilled) {
            unset($this->_errors['_formToken']);
        }

        foreach ($this->_hiddenVariables as $var) {
            $valid = $var instanceof Horde_Form_Variable
                ? $var->validate($vars, $message)
                : $var->validate($vars);
            if (!$valid) {
                $this->_errors[$var->getVarName()] = $var->getMessage();
            }
        }

        return $this->isValid();
    }

    public function clearValidation()
    {
        $this->_errors = [];
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getError($var)
    {
        $name = is_string($var) ? $var : $var->getVarName();
        return $this->_errors[$name] ?? null;
    }

    public function setError($var, $message)
    {
        $name = is_string($var) ? $var : $var->getVarName();
        $this->_errors[$name] = $message;
    }

    public function clearError($var)
    {
        $name = is_string($var) ? $var : $var->getVarName();
        unset($this->_errors[$name]);
    }

    public function isValid()
    {
        return count($this->_errors) == 0;
    }

    public function execute()
    {
        Horde::log('Warning: Horde_Form::execute() called, should be overridden', 'DEBUG');
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param ?Variables $vars  A Variables instance, optional since Horde 3.2.
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    public function getInfo($vars = null, $info = [])
    {
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        return $this->_getInfoFromVariables($this->getVariables(true, true), $vars, $info);
    }

    /**
     * Fetch the field values from a given array of variables.
     *
     * @access private
     *
     * @param array  $variables  An array of Horde_Form_Variable (Fields) objects to
     *                           fetch from.
     * @param object $vars       The Variables object that holds the values.
     * @param array  $info       The array to be filled with the submitted
     *                           field values.
     */
    public function _getInfoFromVariables($variables, $vars, $info)
    {
        foreach ($variables as $var) {
            if ($var->isDisabled()) {
                // Disabled fields are not submitted by some browsers, so don't
                // pretend they were.
                continue;
            }

            $value = $var->getInfo($vars);
            $varName = $var->getVarName();

            // An ArrayVal is a value with a varName ending with []
            if ($var->isArrayVal()) {
                if (is_array($value)) {
                    $varName = str_replace('[]', '', $varName);
                    foreach ($value as $i => $val) {
                        $info[$i][$varName] = $val;
                    }
                }
            } else {
                // A field name like example[key1][key2][key3]
                $keys = ArrayUtils::getFieldParts($varName);
                ArrayUtils::setElement($info, $keys, $value);
            }
        }
        return $info;
    }

    public function hasHelp()
    {
        return $this->_help;
    }

    /**
     * Determines if this form has been submitted or not. If the class
     * var _submitted is null then it will check for the presence of
     * the formname in the form variables.
     *
     * Other events can explicitly set the _submitted variable to
     * false to indicate a form submit but not for actual posting of
     * data (eg. onChange events to update the display of fields).
     *
     * @return boolean  True or false indicating if the form has been
     *                  submitted.
     */
    public function isSubmitted()
    {
        if (is_null($this->_submitted)) {
            return ($this->_vars->get('formname') == $this->getName());
        }

        return $this->_submitted;
    }

    /**
     * Checks if there is anything to do on the submission of the form by
     * looping through each variable's onSubmit() function.
     *
     * @param Horde_Variables $vars
     */
    public function onSubmit($vars)
    {
        /* Loop through all vars and check if there's anything to do on
         * submit. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            $var->onSubmit($vars);
            /* If changes to var being tracked don't register the form as
             * submitted if old value and new value differ. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                if (!is_null($vars->get('formname'))
                    && $vars->get($varname) != $vars->get('__old_' . $varname)) {
                    $this->_submitted = false;
                }
            }
        }
    }

    /**
     * Explicitly sets the state of the form submit.
     *
     * An event can override the automatic determination of the submit state
     * in the isSubmitted() function.
     *
     * @param boolean $state  Whether to set the state of the form as being
     *                        submitted.
     */
    public function setSubmitted($state = true)
    {
        $this->_submitted = $state;
    }

}
