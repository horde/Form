<?php

declare(strict_types=1);

/**
 * Copyright 2001-2026 Robert E. Coyle <robertecoyle@hotmail.com>
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
use Horde\Token\Token;
use Horde\Token\Exception\TokenException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Base implementation of the Form interface for Horde Form V3.
 *
 * This is a modernized implementation of Horde_Form with:
 * - Type/Variable merge (no separate Type objects)
 * - PSR-7 ServerRequest support (including file uploads)
 * - Named parameters throughout
 * - Strict typing
 * - Modern PHP patterns (no singleton, minimal reference passing)
 *
 * ## Input types
 *
 * V3 accepts multiple input types for backward compatibility:
 * - Horde_Variables (legacy Horde apps)
 * - PSR-7 ServerRequest (modern apps — recommended)
 * - Plain arrays (testing, simple apps)
 *
 * All inputs are normalized to array internally for consistent operation.
 *
 * ## File uploads
 *
 * When a PSR-7 ServerRequestInterface is passed to the constructor,
 * uploaded files are extracted via getUploadedFiles() and stored
 * internally. During validate() and getInfo(), any variable implementing
 * FileUploadAware receives the corresponding UploadedFileInterface
 * before its validation or extraction logic runs.
 *
 * This eliminates the need for $_FILES and $GLOBALS['browser'] when
 * using the PSR-7 input path. The legacy fallback remains active when
 * form data is provided as an array or Horde_Variables.
 *
 * Files can also be set explicitly via setUploadedFiles() when the
 * request was decomposed before reaching the form.
 *
 * @see Horde_Form PSR-0 legacy equivalent in lib/Horde/Form.php
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
    use VariableFactoryTrait;

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
     * Uploaded files from PSR-7 request.
     *
     * @var array<string, UploadedFileInterface|array>
     */
    private array $uploadedFiles = [];

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
     * Form field groups (sections and plain groups).
     *
     * Keyed by group name. Each entry is a FieldGroup (structural)
     * or Section (structural + visual metadata).
     *
     * @var array<string, FieldGroup>
     */
    private array $groups = [];

    /**
     * Currently active group name for addVariable() calls.
     */
    private ?string $currentGroup = null;

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
     * CSRF token service (null = CSRF protection disabled).
     */
    private ?Token $tokenService = null;

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
     * Registered form-level validators.
     *
     * @var array<FormValidator>
     */
    private array $validators = [];

    /**
     * Create a new form.
     *
     * @param Horde_Variables|ServerRequestInterface|array $vars  Form data
     * @param string $title  Form display title
     * @param string|null $name  Form name (auto-generated from class if null)
     * @param Token|null $token  CSRF token service (null = no CSRF protection)
      *
      * @api
     */
    public function __construct(
        Horde_Variables|ServerRequestInterface|array $vars,
        string $title = '',
        ?string $name = null,
        ?Token $token = null
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
        $this->tokenService = $token;
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
      *
      * @internal
     */
    private function normalizeVars(
        Horde_Variables|ServerRequestInterface|array $vars
    ): array {
        if ($vars instanceof Horde_Variables) {
            return iterator_to_array($vars);
        }
        if (is_array($vars)) {
            return $vars;
        }
        // PSR-7: extract uploaded files alongside form data.
        $this->uploadedFiles = $vars->getUploadedFiles();
        // Read from the source matching the HTTP method.
        // POST/PUT/PATCH carry data in the body; GET/DELETE/HEAD in query.
        return match ($vars->getMethod()) {
            'POST', 'PUT', 'PATCH' => $vars->getParsedBody() ?? [],
            default => $vars->getQueryParams(),
        };
    }

    /**
     * Get form variables.
     *
     * Returns a copy to prevent external mutation.
     *
     * @return array<string, mixed>
      *
      * @api
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
      *
      * @api
     */
    public function setVars(Horde_Variables|ServerRequestInterface|array $vars): void
    {
        $this->vars = $this->normalizeVars($vars);
    }

    /**
     * Set uploaded files for file-type form variables.
     *
     * When a PSR-7 ServerRequestInterface is passed to the constructor,
     * uploaded files are extracted automatically. Use this method when
     * form data is provided as an array or Horde_Variables but uploaded
     * files are available separately (e.g., from a PSR-7 request that
     * was decomposed before reaching the form).
     *
     * The array structure mirrors ServerRequestInterface::getUploadedFiles():
     * keys are field names, values are UploadedFileInterface instances or
     * nested arrays thereof.
     *
     * @param array<string, UploadedFileInterface|array> $uploadedFiles
     *
     * @api
     */
    public function setUploadedFiles(array $uploadedFiles): void
    {
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * Resolve the uploaded file for a given variable name.
     *
     * Navigates the uploadedFiles tree using bracket notation:
     * - Simple: "photo" → $uploadedFiles['photo']
     * - Nested: "object[photo][new]" → $uploadedFiles['object']['photo']['new']
     */
    private function getUploadedFileFor(Variable $var): ?UploadedFileInterface
    {
        if (empty($this->uploadedFiles)) {
            return null;
        }

        $name = $var->getVarName();

        // For ImageVariable, the upload field is varname[new]
        if ($var instanceof ImageVariable) {
            $name .= '[new]';
        }

        if (!str_contains($name, '[')) {
            $file = $this->uploadedFiles[$name] ?? null;
            return $file instanceof UploadedFileInterface ? $file : null;
        }

        // Navigate nested: "field[sub][key]" → ['field']['sub']['key']
        $parts = explode('[', str_replace(']', '', $name));
        $current = $this->uploadedFiles;
        foreach ($parts as $part) {
            if (!is_array($current) || !isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }

        return $current instanceof UploadedFileInterface ? $current : null;
    }

    /**
     * Get a single variable value.
     *
     * @param string $name  Variable name
     * @param mixed $default  Default value if not set
     * @return mixed  Variable value or default
      *
      * @api
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
      *
      * @api
     */
    public function setVar(string $name, mixed $value): void
    {
        $this->vars[$name] = $value;
    }

    /**
     * Get form title.
      *
      * @api
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set form title.
      *
      * @api
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get extra form content.
      *
      * @api
     */
    public function getExtra(): string
    {
        return $this->extra;
    }

    /**
     * Set extra form content (HTML, etc.).
      *
      * @api
     */
    public function setExtra(string $extra): void
    {
        $this->extra = $extra;
    }

    /**
     * Get form name.
      *
      * @api
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
      *
      * @api
     */
    public function useToken(?bool $token = null): bool
    {
        if ($token !== null) {
            $this->useFormToken = $token;
        }
        return $this->useFormToken;
    }

    /**
     * Set the CSRF token service.
     *
     * @param Token|null $token  Token service (null = disable CSRF)
      *
      * @api
     */
    public function setTokenService(?Token $token): void
    {
        $this->tokenService = $token;
    }

    /**
     * Generate a CSRF token for this form.
     *
     * Returns null if token protection is disabled or no token
     * service is configured.
     *
     * @return string|null  Token string or null
      *
      * @api
     */
    public function generateToken(): ?string
    {
        if (!$this->useFormToken || $this->tokenService === null) {
            return null;
        }
        return (string) $this->tokenService->generate($this->name);
    }

    /**
     * Get the hidden field name for the CSRF token.
     *
     * @return string  Field name (e.g., "myform_formToken")
      *
      * @api
     */
    public function getTokenFieldName(): string
    {
        return $this->name . '_formToken';
    }

    /**
     * Set current form section.
     *
     * Sections are FieldGroups with visual metadata (title, description,
     * image, expanded state). Variables added after this call are placed
     * in this section until a new section is set.
     *
     * @param string|int $section  Section identifier
     * @param string $desc  Section description
     * @param string $image  Section icon/image URL
     * @param bool $expanded  Whether section starts expanded
     * @param string $prefix  Name prefix for variables ('' = no prefix)
      *
      * @api
     */
    public function setSection(
        string|int $section = '',
        string $desc = '',
        string $image = '',
        bool $expanded = true,
        string $prefix = '',
    ): void {
        $name = (string) $section;
        $this->currentGroup = $name;

        // Auto-open first section if none open yet
        if (count($this->groups) === 0 && !$this->getOpenSection()) {
            $this->setOpenSection($section);
        }

        // Create or update the Section object
        if (isset($this->groups[$name]) && $this->groups[$name] instanceof Section) {
            $existing = $this->groups[$name];
            $existing->setDescription($desc);
            $existing->setImage($image);
            $existing->setExpanded($expanded);
        } else {
            $this->groups[$name] = new Section(
                name: $name,
                title: $name,
                description: $desc,
                image: $image,
                expanded: $expanded,
                prefix: $prefix,
            );
        }
    }

    /**
     * Register a field group (or section) and set it as the current group.
     *
     * Variables added after this call go into the registered group.
     * If the group has a prefix, variables are automatically name-scoped.
     *
     * @param FieldGroup $group  The group to register
     * @return static  Fluent interface
      *
      * @api
     */
    public function addGroup(FieldGroup $group): static
    {
        $name = $group->getName();
        $this->groups[$name] = $group;
        $this->currentGroup = $name;
        return $this;
    }

    /**
     * Get a registered group by name.
     *
     * @param string $name  Group name
     * @return FieldGroup|null  The group, or null if not found
      *
      * @api
     */
    public function getGroup(string $name): ?FieldGroup
    {
        return $this->groups[$name] ?? null;
    }

    /**
     * Activate a single group and disable all others.
     *
     * Used in wizard forms to mark one step as editable while all
     * other steps become read-only (displayed with preserved values).
     *
     * @param string $name  Name of the group to activate
      *
      * @api
     */
    public function setActiveGroup(string $name): void
    {
        foreach ($this->groups as $groupName => $group) {
            $group->setEnabled($groupName === $name);
        }
    }

    /**
     * Enable one group and disable all groups that appear before it.
     *
     * Groups after the named group are left unchanged. Useful for
     * wizard patterns where completed steps should become read-only
     * and the current step should be editable.
     *
     * @param string $name  Name of the group to enable (all prior groups disabled)
      *
      * @api
     */
    public function setGroupsEnabledUpTo(string $name): void
    {
        foreach ($this->groups as $groupName => $group) {
            if ($groupName === $name) {
                $group->setEnabled(true);
                break;
            }
            $group->setEnabled(false);
        }
    }

    /**
     * Get section description.
      *
      * @api
     */
    public function getSectionDesc(string|int $section): string
    {
        $group = $this->groups[(string) $section] ?? null;
        return $group instanceof Section ? $group->getDescription() : '';
    }

    /**
     * Get section image/icon URL.
      *
      * @api
     */
    public function getSectionImage(string|int $section): string
    {
        $group = $this->groups[(string) $section] ?? null;
        return $group instanceof Section ? $group->getImage() : '';
    }

    /**
     * Set which section is currently open.
     *
     * @param string|int $section  Section identifier
      *
      * @api
     */
    public function setOpenSection(string|int $section): void
    {
        $this->vars['__formOpenSection'] = $section;
    }

    /**
     * Get which section is currently open.
     *
     * @return string|int|null  Open section identifier or null
      *
      * @api
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
      *
      * @api
     */
    public function getSectionExpandedState(string|int $section, bool $boolean = false): bool|string
    {
        $group = $this->groups[(string) $section] ?? null;
        $expanded = $group instanceof Section ? $group->isExpanded() : true;

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
      *
      * @api
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
      *
      * @api
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
        // Ensure group exists
        if ($this->currentGroup === null) {
            $this->currentGroup = '__base';
        }
        if (!isset($this->groups[$this->currentGroup])) {
            $this->groups[$this->currentGroup] = new FieldGroup($this->currentGroup);
        }

        $group = $this->groups[$this->currentGroup];

        // Apply group prefix to variable name
        $scopedName = $group->getPrefix() !== ''
            ? $group->getPrefix() . '[' . $varName . ']'
            : $varName;

        // Create variable using factory method
        $var = $this->createVariable(
            humanName: $humanName,
            varName: $scopedName,
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
            if (count($values) === 1 && !array_key_exists($scopedName, $this->vars)) {
                $keys = array_keys($values);
                $this->setVar($scopedName, $keys[0]);
            }
        }

        // Set multipart encoding for file uploads
        if (in_array($var->getTypeName(), ['file', 'image'])) {
            $this->enctype = 'multipart/form-data';
        }

        // Insert variable into the current group
        $group->insertVariable($var, $before);

        return $var;
    }

    /**
     * Remove a variable from the form.
     *
     * @param Variable|string $var  Variable instance or variable name
     * @return bool  True if variable was found and removed
      *
      * @api
     */
    public function removeVariable(Variable|string $var): bool
    {
        foreach ($this->groups as $group) {
            if ($group->removeVariable($var)) {
                return true;
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
      *
      * @api
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
     * @param bool|null $enabledOnly  null = all groups, true = enabled only, false = disabled only
     * @return array<Variable>|array<string, array<Variable>>
      *
      * @api
     */
    public function getVariables(bool $flat = true, bool $withHidden = false, ?bool $enabledOnly = null): array
    {
        if ($flat) {
            $vars = [];
            foreach ($this->groups as $name => $group) {
                if ($enabledOnly !== null && $group->isEnabled() !== $enabledOnly) {
                    continue;
                }
                foreach ($group->getVariables() as $var) {
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

        // Structured by group name → variable array (same shape as before)
        $result = [];
        foreach ($this->groups as $name => $group) {
            if ($enabledOnly !== null && $group->isEnabled() !== $enabledOnly) {
                continue;
            }
            $result[$name] = $group->getVariables();
        }
        return $result;
    }

    /**
     * Set form buttons.
     *
     * @param array<string>|string|bool $submit  Submit button label(s) or true for default
     * @param string|bool $reset  Reset button label or false for none
      *
      * @api
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
      *
      * @api
     */
    public function appendButtons(array|string $submit): void
    {
        if (!is_array($submit)) {
            $submit = [$submit];
        }

        $this->submit = array_merge($this->submit, $submit);
    }

    /**
     * Get submit button labels.
     *
     * Each element is either a string label or an associative array
     * with keys like 'value', 'class' for styled buttons.
     *
     * @return array<string|array>  Button configurations
      *
      * @api
     */
    public function getButtons(): array
    {
        return $this->submit;
    }

    /**
     * Get reset button label.
     *
     * @return string|false  Reset button label or false if no reset button
      *
      * @api
     */
    public function getReset(): string|false
    {
        return $this->reset;
    }

    /**
     * Get the label of the submit button that was clicked.
     *
     * Reads the 'submitbutton' value from the form's submitted data.
     * Returns empty string if no button was identified.
      *
      * @api
     */
    public function getClickedButton(): string
    {
        return (string) ($this->vars['submitbutton'] ?? '');
    }

    /**
     * Register a form-level validator.
     *
     * Form validators run after field-level validation and can perform
     * cross-field checks. Multiple validators are executed in registration
     * order.
     *
     * @param FormValidator $validator  Validator instance
     * @return static  Fluent interface
      *
      * @api
     */
    public function addValidator(FormValidator $validator): static
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Validate form.
     *
     * Validates all variables against current form data. Collects errors.
     *
     * @param mixed $vars  Optional variables to validate against (null = use form's vars)
     * @return bool  True if validation passed, false if errors exist
      *
      * @api
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

        // CSRF token validation
        if ($this->useFormToken && $this->tokenService !== null) {
            $tokenField = $this->name . '_formToken';
            $submittedToken = $varsToValidate[$tokenField] ?? '';
            if ($submittedToken === '') {
                $this->errors['_formToken'] = _("Missing form token — possible cross-site request.");
            } else {
                try {
                    $this->tokenService->validateUnique($submittedToken, $this->name);
                } catch (TokenException $e) {
                    $this->errors['_formToken'] = _("This form has already been processed.");
                }
            }
        }

        // Validate all variables in enabled groups
        foreach ($this->getVariables(flat: true, withHidden: false, enabledOnly: true) as $var) {
            if ($var instanceof FileUploadAware) {
                $var->setUploadedFile($this->getUploadedFileFor($var));
            }
            if (!$var->validate($varsObject)) {
                $this->errors[$var->getVarName()] = $var->getMessage();
            }
        }

        // Validate hidden variables
        foreach ($this->hiddenVariables as $var) {
            if ($var instanceof FileUploadAware) {
                $var->setUploadedFile($this->getUploadedFileFor($var));
            }
            if (!$var->validate($varsObject)) {
                $this->errors[$var->getVarName()] = $var->getMessage();
            }
        }

        // Run group-level validators (only enabled groups)
        foreach ($this->groups as $group) {
            if (!$group->isEnabled()) {
                continue;
            }
            $group->validate($varsToValidate, $this->errors);
        }

        // Run registered form-level validators
        foreach ($this->validators as $validator) {
            $validator->validate($varsToValidate, $this->errors);
        }

        // Run subclass override point
        $this->validateForm($varsToValidate, $this->errors);

        return count($this->errors) === 0;
    }

    /**
     * Form-level validation override point.
     *
     * Subclasses can override this to add cross-field validation
     * without needing a separate FormValidator class. Called after
     * field-level validation and registered FormValidator instances.
     *
     * @param array<string, mixed> $vars    Form data
     * @param array<string, string> &$errors  Error map, passed by reference
      *
      * @api
     */
    protected function validateForm(array $vars, array &$errors): void
    {
        // No-op default. Subclasses override.
    }

    /**
     * Check if form is valid (has no errors).
     *
     * Must call validate() first.
     *
     * @return bool  True if no validation errors exist
      *
      * @api
     */
    public function isValid(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, string>  Map of variable name => error message
      *
      * @api
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
      *
      * @api
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
      *
      * @api
     */
    public function setError(string $varName, string $message): void
    {
        $this->errors[$varName] = $message;
    }

    /**
     * Clear all errors or error for specific variable.
     *
     * @param string|null $varName  Variable name to clear (null = clear all)
      *
      * @api
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
      *
      * @api
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
      *
      * @internal
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

            if ($var instanceof FileUploadAware) {
                $var->setUploadedFile($this->getUploadedFileFor($var));
            }

            $varName = $var->getVarName();

            // Handle array values (field names ending with [])
            if ($var->isArrayVal()) {
                $info[$varName] = $var->getInfo($varsObject);
            } else {
                $info[$varName] = $var->getInfo($varsObject);
            }
        }

        return $this->nestBracketKeys($info);
    }

    /**
     * Convert flat bracket-notation keys to nested arrays.
     *
     * Mirrors PHP's bracket-notation decoding for GET/POST data.
     * Keys without brackets pass through unchanged.
     *
     * Example: ['billing[street]' => '123 Main', 'name' => 'Alice']
     * becomes: ['billing' => ['street' => '123 Main'], 'name' => 'Alice']
     *
     * @param array<string, mixed> $info  Flat key-value pairs
     * @return array<string, mixed>  Nested array structure
     *
     * @internal
     */
    private function nestBracketKeys(array $info): array
    {
        $result = [];
        foreach ($info as $key => $value) {
            if (!str_contains((string) $key, '[')) {
                $result[$key] = $value;
                continue;
            }

            // Parse 'billing[street]' → ['billing', 'street']
            $keys = explode('[', str_replace(']', '', (string) $key));
            $current = &$result;
            foreach (array_slice($keys, 0, -1) as $k) {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
            $current[end($keys)] = $value;
            unset($current);
        }

        return $result;
    }

    /**
     * Generate hidden inputs to preserve this form's values across steps.
     *
     * Used in multi-step wizards: after Step 1 validates, its values
     * must survive as hidden fields when Step 2 renders.
     *
     * @return string  HTML hidden input elements
      *
      * @api
     */
    public function preserve(): string
    {
        $output = [];

        // Generate a fresh CSRF token for the preserved form
        $token = $this->generateToken();
        if ($token !== null) {
            $this->preserveValue($output, $this->getTokenFieldName(), $token);
        }

        foreach ($this->getVariables(flat: true, withHidden: false) as $var) {
            $varName = $var->getVarName();
            $value = $var->resolveValue($this->vars);
            $this->preserveValue($output, $varName, $value);
        }

        foreach ($this->hiddenVariables as $var) {
            $varName = $var->getVarName();
            $value = $var->resolveValue($this->vars);
            $this->preserveValue($output, $varName, $value);
        }

        return implode("\n", $output);
    }

    /**
     * Recursively emit hidden input(s) for a value.
     *
     * @param array &$output  Output buffer
     * @param string $name    Field name
     * @param mixed $value    Field value (scalar or array)
      *
      * @internal
     */
    private function preserveValue(array &$output, string $name, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->preserveValue($output, $name . '[' . $k . ']', $v);
            }
        } else {
            $output[] = sprintf(
                '<input type="hidden" name="%s" value="%s">',
                htmlspecialchars($name),
                htmlspecialchars((string) ($value ?? ''))
            );
        }
    }

    /**
     * Check if form has been submitted.
     *
     * @return bool  True if form was submitted
      *
      * @api
     */
    public function isSubmitted(): bool
    {
        if ($this->submitted !== null) {
            return $this->submitted;
        }

        // Check for form submission indicator
        // TODO: Implement proper submission detection
        $this->submitted = isset($this->vars['formname'])
                          && $this->vars['formname'] === $this->name;

        return $this->submitted;
    }

    /**
     * Get form encoding type.
     *
     * @return string|null  Encoding type (e.g., 'multipart/form-data') or null
      *
      * @api
     */
    public function getEnctype(): ?string
    {
        return $this->enctype;
    }
}
