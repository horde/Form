<?php
declare(strict_types=1);

/**
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

use Horde_Variables;
use Horde_String;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base implementation of the Form interface for Horde Form V3.
 *
 * This is a modernized implementation of Horde_Form with:
 * - Type/Variable merge (no separate Type objects)
 * - PSR-7 ServerRequest support
 * - Named parameters throughout
 * - Strict typing
 * - Modern PHP patterns (no singleton, minimal reference passing)
 *
 * V3 accepts multiple input types for backward compatibility:
 * - Horde_Variables (legacy Horde apps)
 * - PSR-7 ServerRequest (modern apps)
 * - Plain arrays (testing, simple apps)
 *
 * All inputs are normalized to array internally for consistent operation.
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2001-2007 Robert E. Coyle
 * @copyright 2001-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class BaseForm implements \Horde\Form\Form
{
    /**
     * Form name identifier.
     */
    protected string $name;

    /**
     * Form display title.
     */
    protected string $title;

    /**
     * Extra form content (HTML, etc.).
     */
    protected string $extra = '';

    /**
     * Internal form data storage.
     *
     * Normalized to array regardless of input type for consistent,
     * typed internal operations.
     */
    private array $vars;

    /**
     * Submit button labels.
     *
     * @var array<string>
     */
    protected array $submit = [];

    /**
     * Reset button label (false if no reset button).
     */
    protected string|bool $reset = false;

    /**
     * Form validation errors.
     *
     * @var array<string, string>  Map of variable name => error message
     */
    protected array $errors = [];

    /**
     * Whether form has been submitted.
     */
    protected ?bool $submitted = null;

    /**
     * Form sections configuration.
     *
     * @var array<string, array{desc: string, expanded: bool, image: string}>
     */
    public array $sections = [];

    /**
     * Currently active section name.
     */
    protected string|int|null $currentSection = null;

    /**
     * Form variables organized by section.
     *
     * @var array<string, array<Variable>>
     */
    protected array $variables = [];

    /**
     * Hidden form variables.
     *
     * @var array<Variable>
     */
    protected array $hiddenVariables = [];

    /**
     * Whether to use form tokens for CSRF protection.
     */
    protected bool $useFormToken = true;

    /**
     * Whether form was autofilled.
     */
    protected bool $autofilled = false;

    /**
     * Form encoding type (e.g., 'multipart/form-data' for file uploads).
     */
    protected ?string $enctype = null;

    /**
     * Whether form has help text.
     */
    public bool $help = false;

    /**
     * Create a new form.
     *
     * @param Horde_Variables|ServerRequestInterface|array $vars  Form data
     * @param string $title  Form display title
     * @param string|null $name  Form name (auto-generated from class if null)
     */
    public function __construct(
        Horde_Variables|ServerRequestInterface|array $vars,
        string $title = '',
        ?string $name = null
    ) {
        // Generate form name from class name if not provided
        if (empty($name)) {
            $name = Horde_String::lower(static::class);
        }
        $name = str_replace('\\', '_', $name);

        // Normalize input to array
        $this->vars = $this->normalizeVars($vars);
        $this->title = $title;
        $this->name = $name;
    }

    /**
     * Normalize various input types to array.
     *
     * Accepts:
     * - Horde_Variables (legacy): Converted via iterator
     * - PSR-7 ServerRequest: Extracts parsed body
     * - Array: Used directly
     *
     * @param Horde_Variables|ServerRequestInterface|array $vars
     * @return array<string, mixed>
     */
    private function normalizeVars(
        Horde_Variables|ServerRequestInterface|array $vars
    ): array {
        return match(true) {
            $vars instanceof Horde_Variables => iterator_to_array($vars),
            $vars instanceof ServerRequestInterface => $vars->getParsedBody() ?? [],
            is_array($vars) => $vars,
        };
    }

    /**
     * Get form variables.
     *
     * Returns a copy to prevent external mutation.
     *
     * @return array<string, mixed>
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Set form variables.
     *
     * Replaces all form data. Accepts same types as constructor.
     *
     * @param Horde_Variables|ServerRequestInterface|array $vars
     */
    public function setVars(Horde_Variables|ServerRequestInterface|array $vars): void
    {
        $this->vars = $this->normalizeVars($vars);
    }

    /**
     * Get a single variable value.
     *
     * @param string $name  Variable name
     * @param mixed $default  Default value if not set
     * @return mixed  Variable value or default
     */
    public function getVar(string $name, mixed $default = null): mixed
    {
        return $this->vars[$name] ?? $default;
    }

    /**
     * Set a single variable value.
     *
     * Allows dynamic form data updates after construction.
     *
     * @param string $name  Variable name
     * @param mixed $value  Variable value
     */
    public function setVar(string $name, mixed $value): void
    {
        $this->vars[$name] = $value;
    }

    /**
     * Get form title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set form title.
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get extra form content.
     */
    public function getExtra(): string
    {
        return $this->extra;
    }

    /**
     * Set extra form content (HTML, etc.).
     */
    public function setExtra(string $extra): void
    {
        $this->extra = $extra;
    }

    /**
     * Get form name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set or get whether to use form tokens for CSRF protection.
     *
     * @param bool|null $token  If specified, sets whether to use tokens
     * @return bool  Current token usage setting
     */
    public function useToken(?bool $token = null): bool
    {
        if ($token !== null) {
            $this->useFormToken = $token;
        }
        return $this->useFormToken;
    }

    /**
     * Set current form section.
     *
     * Sections allow organizing form fields into collapsible groups.
     *
     * @param string|int $section  Section identifier
     * @param string $desc  Section description
     * @param string $image  Section icon/image URL
     * @param bool $expanded  Whether section starts expanded
     */
    public function setSection(
        string|int $section = '',
        string $desc = '',
        string $image = '',
        bool $expanded = true
    ): void {
        $this->currentSection = $section;

        // Auto-open first section if none open yet
        if (count($this->sections) === 0 && !$this->getOpenSection()) {
            $this->setOpenSection($section);
        }

        $this->sections[$section]['desc'] = $desc;
        $this->sections[$section]['expanded'] = $expanded;
        $this->sections[$section]['image'] = $image;
    }

    /**
     * Get section description.
     */
    public function getSectionDesc(string|int $section): string
    {
        return $this->sections[$section]['desc'] ?? '';
    }

    /**
     * Get section image/icon URL.
     */
    public function getSectionImage(string|int $section): string
    {
        return $this->sections[$section]['image'] ?? '';
    }

    /**
     * Set which section is currently open.
     *
     * @param string|int $section  Section identifier
     */
    public function setOpenSection(string|int $section): void
    {
        $this->vars['__formOpenSection'] = $section;
    }

    /**
     * Get which section is currently open.
     *
     * @return string|int|null  Open section identifier or null
     */
    public function getOpenSection(): string|int|null
    {
        return $this->vars['__formOpenSection'] ?? null;
    }

    /**
     * Get section expanded state.
     *
     * @param string|int $section  Section identifier
     * @param bool $boolean  If true, return bool; if false, return CSS value
     * @return bool|string  Expanded state as bool or 'block'/'none' for CSS
     */
    public function getSectionExpandedState(string|int $section, bool $boolean = false): bool|string
    {
        $expanded = $this->sections[$section]['expanded'] ?? true;

        if ($boolean) {
            return $expanded;
        }

        return $expanded ? 'block' : 'none';
    }

    /**
     * Add a variable to the form.
     *
     * Creates a new form field and adds it to the current section.
     *
     * @param string $humanName  Human-readable field label
     * @param string $varName  Internal variable name
     * @param string $type  Variable type (e.g., 'text', 'email', 'enum')
     * @param bool $required  Whether field is required
     * @param bool $readonly  Whether field is read-only
     * @param string|null $description  Field description/help text
     * @param array $params  Type-specific parameters (e.g., enum values)
     * @return Variable  The created variable instance
     */
    public function addVariable(
        string $humanName,
        string $varName,
        string $type,
        bool $required,
        bool $readonly = false,
        ?string $description = null,
        array $params = []
    ): Variable {
        return $this->insertVariableBefore(
            before: null,
            humanName: $humanName,
            varName: $varName,
            type: $type,
            required: $required,
            readonly: $readonly,
            description: $description,
            params: $params
        );
    }

    /**
     * Insert a variable before another variable.
     *
     * If $before is null, appends to current section.
     *
     * @param string|null $before  Variable name to insert before (null = append)
     * @param string $humanName  Human-readable field label
     * @param string $varName  Internal variable name
     * @param string $type  Variable type
     * @param bool $required  Whether field is required
     * @param bool $readonly  Whether field is read-only
     * @param string|null $description  Field description/help text
     * @param array $params  Type-specific parameters
     * @return Variable  The created variable instance
     */
    public function insertVariableBefore(
        ?string $before,
        string $humanName,
        string $varName,
        string $type,
        bool $required,
        bool $readonly = false,
        ?string $description = null,
        array $params = []
    ): Variable {
        // Create variable using factory method
        $var = $this->createVariable(
            humanName: $humanName,
            varName: $varName,
            type: $type,
            required: $required,
            readonly: $readonly,
            description: $description,
            params: $params
        );

        // Set form reference
        $var->setFormOb($this);

        // Auto-fill single-value enums
        if ($var->getTypeName() === 'enum') {
            $values = $var->getValues();
            if (count($values) === 1 && !array_key_exists($varName, $this->vars)) {
                $keys = array_keys($values);
                $this->setVar($varName, $keys[0]);
                // Mark as autofilled (property doesn't exist in V3 yet, but planned)
                // $var->_autofilled = true;
            }
        }

        // Set multipart encoding for file uploads
        if (in_array($var->getTypeName(), ['file', 'image'])) {
            $this->enctype = 'multipart/form-data';
        }

        // Ensure section is set
        if ($this->currentSection === null) {
            $this->currentSection = '__base';
        }

        // Insert variable at appropriate position
        if ($before === null) {
            // Append to end
            $this->variables[$this->currentSection][] = $var;
        } else {
            // Find position of $before variable
            $position = null;
            if (isset($this->variables[$this->currentSection])) {
                foreach ($this->variables[$this->currentSection] as $index => $existingVar) {
                    if ($existingVar->getVarName() === $before) {
                        $position = $index;
                        break;
                    }
                }
            }

            if ($position === null) {
                // $before not found, append to end
                $this->variables[$this->currentSection][] = $var;
            } else {
                // Insert at position
                $this->variables[$this->currentSection] = array_merge(
                    array_slice($this->variables[$this->currentSection], 0, $position),
                    [$var],
                    array_slice($this->variables[$this->currentSection], $position)
                );
            }
        }

        return $var;
    }

    /**
     * Factory method: Create Variable instance from type string.
     *
     * Maps type strings like 'text', 'email', 'enum' to Variable classes.
     * Supports app-specific types via 'app:typename' format (e.g., 'whups:priority').
     *
     * @param string $humanName  Human-readable field label
     * @param string $varName  Internal variable name
     * @param string $type  Variable type string
     * @param bool $required  Whether field is required
     * @param bool $readonly  Whether field is read-only
     * @param string|null $description  Field description
     * @param array $params  Type-specific parameters
     * @return Variable  Created variable instance
     */
    private function createVariable(
        string $humanName,
        string $varName,
        string $type,
        bool $required,
        bool $readonly = false,
        ?string $description = null,
        array $params = []
    ): Variable {
        // Handle app:typename format (e.g., 'whups:priority')
        if (str_contains($type, ':')) {
            [$app, $type] = explode(':', $type, 2);
            $class = ucfirst($app) . '\\Form\\V3\\' . ucfirst($type) . 'Variable';
        } else {
            // Standard Horde types
            $class = 'Horde\\Form\\V3\\' . ucfirst($type) . 'Variable';
        }

        // Fallback to InvalidVariable if class doesn't exist
        if (!class_exists($class)) {
            error_log("Warning: Form type class '$class' not found, using InvalidVariable");
            $class = 'Horde\\Form\\V3\\InvalidVariable';
        }

        // Create variable instance
        $var = new $class(
            humanName: $humanName,
            varName: $varName,
            required: $required,
            readonly: $readonly,
            description: $description
        );

        // Initialize with type-specific parameters
        $var->init(...$params);

        return $var;
    }

    /**
     * Remove a variable from the form.
     *
     * @param Variable|string $var  Variable instance or variable name
     * @return bool  True if variable was found and removed
     */
    public function removeVariable(Variable|string $var): bool
    {
        $varName = $var instanceof Variable ? $var->getVarName() : $var;

        foreach ($this->variables as $section => $sectionVars) {
            foreach ($sectionVars as $index => $existingVar) {
                if ($existingVar->getVarName() === $varName ||
                    ($var instanceof Variable && $existingVar === $var)) {
                    // Remove variable from section
                    array_splice($this->variables[$section], $index, 1);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add a hidden variable to the form.
     *
     * Hidden variables are not rendered but are included in form submission.
     *
     * @param string $humanName  Human-readable field label
     * @param string $varName  Internal variable name
     * @param string $type  Variable type
     * @param bool $required  Whether field is required
     * @param bool $readonly  Whether field is read-only (always true for hidden)
     * @param string|null $description  Field description
     * @param array $params  Type-specific parameters
     * @return Variable  The created variable instance
     */
    public function addHidden(
        string $humanName,
        string $varName,
        string $type,
        bool $required,
        bool $readonly = false,
        ?string $description = null,
        array $params = []
    ): Variable {
        $var = $this->createVariable(
            humanName: $humanName,
            varName: $varName,
            type: $type,
            required: $required,
            readonly: $readonly,
            description: $description,
            params: $params
        );

        $var->hide();
        $this->hiddenVariables[] = $var;

        return $var;
    }

    /**
     * Get form variables.
     *
     * @param bool $flat  If true, return flat array; if false, return by section
     * @param bool $withHidden  If true, include hidden variables
     * @return array<Variable>|array<string, array<Variable>>
     */
    public function getVariables(bool $flat = true, bool $withHidden = false): array
    {
        if ($flat) {
            $vars = [];
            foreach ($this->variables as $section => $sectionVars) {
                foreach ($sectionVars as $var) {
                    $vars[] = $var;
                }
            }
            if ($withHidden) {
                foreach ($this->hiddenVariables as $var) {
                    $vars[] = $var;
                }
            }
            return $vars;
        }

        return $this->variables;
    }

    /**
     * Set form buttons.
     *
     * @param array<string>|string|bool $submit  Submit button label(s) or true for default
     * @param string|bool $reset  Reset button label or false for none
     */
    public function setButtons(array|string|bool $submit, string|bool $reset = false): void
    {
        // Normalize submit to array
        if ($submit === true || $submit === null || $submit === '') {
            $submit = ['Submit'];  // Default
        } elseif (!is_array($submit)) {
            $submit = [$submit];
        }

        if ($reset === true) {
            $reset = 'Reset';  // Default
        }

        $this->submit = $submit;
        $this->reset = $reset;
    }

    /**
     * Append additional submit buttons.
     *
     * @param array<string>|string $submit  Button label(s) to append
     */
    public function appendButtons(array|string $submit): void
    {
        if (!is_array($submit)) {
            $submit = [$submit];
        }

        $this->submit = array_merge($this->submit, $submit);
    }

    /**
     * Validate form.
     *
     * Validates all variables against current form data. Collects errors.
     *
     * @param mixed $vars  Optional variables to validate against (null = use form's vars)
     * @return bool  True if validation passed, false if errors exist
     */
    public function validate($vars = null): bool
    {
        // Use form's own vars if not provided
        if ($vars === null) {
            $varsToValidate = $this->vars;
        } elseif ($vars instanceof Horde_Variables || $vars instanceof ServerRequestInterface || is_array($vars)) {
            $varsToValidate = $this->normalizeVars($vars);
        } else {
            $varsToValidate = $vars;
        }

        // Clear previous errors
        $this->errors = [];

        // Wrap array in Horde_Variables for BaseVariable compatibility
        $varsObject = new Horde_Variables($varsToValidate);

        // TODO: Form token validation (requires Horde_Token integration)
        // if ($this->useFormToken) { ... }

        // Validate all variables
        foreach ($this->getVariables(flat: true, withHidden: false) as $var) {
            $message = '';
            if (!$var->validate($varsObject, $message)) {
                $this->errors[$var->getVarName()] = $var->getMessage();
            }
        }

        // Validate hidden variables
        foreach ($this->hiddenVariables as $var) {
            $message = '';
            if (!$var->validate($varsObject, $message)) {
                $this->errors[$var->getVarName()] = $var->getMessage();
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Check if form is valid (has no errors).
     *
     * Must call validate() first.
     *
     * @return bool  True if no validation errors exist
     */
    public function isValid(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, string>  Map of variable name => error message
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error for specific variable.
     *
     * @param string $varName  Variable name
     * @return string|null  Error message or null if no error
     */
    public function getError(string $varName): ?string
    {
        return $this->errors[$varName] ?? null;
    }

    /**
     * Set error for specific variable.
     *
     * @param string $varName  Variable name
     * @param string $message  Error message
     */
    public function setError(string $varName, string $message): void
    {
        $this->errors[$varName] = $message;
    }

    /**
     * Clear all errors or error for specific variable.
     *
     * @param string|null $varName  Variable name to clear (null = clear all)
     */
    public function clearError(?string $varName = null): void
    {
        if ($varName === null) {
            $this->errors = [];
        } else {
            unset($this->errors[$varName]);
        }
    }

    /**
     * Extract form data into array.
     *
     * Retrieves validated values from all variables.
     *
     * @param mixed $vars  Optional variables to extract from (null = use form's vars)
     * @return array<string, mixed>  Associative array of field name => value
     */
    public function getInfo($vars = null): array
    {
        // Use form's own vars if not provided
        if ($vars === null) {
            $varsToUse = $this->vars;
        } elseif ($vars instanceof Horde_Variables || $vars instanceof ServerRequestInterface || is_array($vars)) {
            $varsToUse = $this->normalizeVars($vars);
        } else {
            $varsToUse = $vars;
        }

        return $this->getInfoFromVariables(
            variables: $this->getVariables(flat: true, withHidden: true),
            vars: $varsToUse
        );
    }

    /**
     * Extract data from specific set of variables.
     *
     * @param array<Variable> $variables  Variables to extract from
     * @param array $vars  Variable data
     * @return array<string, mixed>  Extracted field values
     */
    private function getInfoFromVariables(array $variables, array $vars): array
    {
        $info = [];

        // Wrap array in Horde_Variables for BaseVariable compatibility
        $varsObject = new Horde_Variables($vars);

        foreach ($variables as $var) {
            // Skip disabled fields (not submitted by browsers)
            if ($var->isDisabled()) {
                continue;
            }

            $varName = $var->getVarName();

            // Handle array values (field names ending with [])
            if ($var->isArrayVal()) {
                $info[$varName] = $var->getInfo($varsObject);
            } else {
                $info[$varName] = $var->getInfo($varsObject);
            }
        }

        return $info;
    }

    /**
     * Check if form has been submitted.
     *
     * @return bool  True if form was submitted
     */
    public function isSubmitted(): bool
    {
        if ($this->submitted !== null) {
            return $this->submitted;
        }

        // Check for form submission indicator
        // TODO: Implement proper submission detection
        $this->submitted = isset($this->vars['formname']) &&
                          $this->vars['formname'] === $this->name;

        return $this->submitted;
    }

    /**
     * Get form encoding type.
     *
     * @return string|null  Encoding type (e.g., 'multipart/form-data') or null
     */
    public function getEnctype(): ?string
    {
        return $this->enctype;
    }
}
