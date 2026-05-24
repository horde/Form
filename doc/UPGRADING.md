# Upgrading to Horde Form V3

**Status**: V3 is being battle-tested in Whups (2026-04): admin forms,
search, all ticket CRUD controllers and a 4-step creation wizard.
BaseForm, Renderer and Actions are implemented.
Expect API refinements before stable release.

---

## Concepts: lib/ to V3

### What maps to what

| lib/ (legacy) | V3 (src/) | Notes |
|---|---|---|
| `Horde_Form` | `Horde\Form\V3\BaseForm` | Implements `Horde\Form\Form` interface |
| `Horde_Form_Variable` | `Horde\Form\V3\Variable` (interface) | Implemented by per-type classes |
| `Horde_Form_Type_text` | `Horde\Form\V3\TextVariable` | Type merged into Variable |
| `Horde_Form_Type_enum` | `Horde\Form\V3\EnumVariable` | Type merged into Variable |
| `Horde_Form_Renderer` | `Horde\Form\V3\BaseRenderer` | Returns strings, does not echo |
| `Horde_Form_Action` | `Horde\Form\V3\BaseAction` | Same concept, modernized |
| `Horde_Variables` | `array` (internal) | V3 normalizes all input to array |
| `$form->getInfo($vars, $info)` (by-ref) | `$info = $form->getInfo()` (return) | No reference passing |
| `$type->isValid($var, $vars, $val, $msg)` | `$var->isValid($vars, $val): bool` | Error via `$var->invalid()` |
| Monolithic Renderer | ControlRenderer + LayoutStrategy + ErrorRenderer | Strategy pattern |

### What disappeared

| lib/ concept | V3 replacement |
|---|---|
| Separate Type objects | Merged into Variable subclasses |
| `Horde_Form::singleton()` | Removed. Use normal instantiation. |
| Reference parameters (`&$info`, `&$message`) | Return values and method calls |
| `Horde_Core_Ui_VarRenderer` | `ControlRenderer` interface |
| Echo-based rendering | All render methods return strings |
| `renderFormInactive()` (echo-based) | `renderInactive()` (string-returning, plain text values) |
| `$_formvars` hidden field (expected-vars whitelist) | Form validates against its own variable list |
| `Horde_Variables::filter()` / `sanitize()` | Output escaping in the renderer (see below) |
| `printJavaScript()` (echo-based) | `getHelperScript(): string` on `ActionV3Interface` |
| Counter-based form names (`horde_form_0`) | Class-name-based (`horde_form_v3_baseform`) |

### What's new

| V3 feature | Purpose |
|---|---|
| `Horde\Form\Form` interface | Typed contract for all form implementations |
| PSR-7 `ServerRequestInterface` input | `new BaseForm($request, 'Title')` |
| Plain array input | `new BaseForm(['name' => 'val'], 'Title')` |
| `LayoutStrategy` interface | Swap table/div/list layout without touching controls |
| `ControlRenderer` interface | Swap HTML/JSON/CLI rendering |
| `ErrorRenderer` interface | Inline errors, summary box or custom |
| `AssetManager` | Collects JS/CSS dependencies during rendering |
| `preserve()` method | Hidden inputs for multi-step wizard forms |
| `renderInactive()` method | Display-only rendering (plain text values, no form tag) |
| `getHelperScript()` on actions | String-returning JS helper (replaces legacy `printJavaScript()`) |
| CSP-friendly action wiring | `addEventListener` bindings emitted after `</form>` |
| `FormValidator` interface | Cross-field validation via registered validators or subclass override |
| `FieldGroup` / `Section` | Structural variable grouping with optional name prefix |
| `addGroup()` | Register a FieldGroup (or Section) and set it as current |
| `getVar()` / `setVar()` | Individual variable access on form data |
| Named parameters | `addVariable(humanName: ..., varName: ..., ...)` |

---

## Intended usage vs upgrade compromises

V3 is designed around clean boundaries: typed input, plain-array
internals, string-returning renderers and output escaping at the
render layer.  Several patterns exist **only** to ease the transition
from lib/.  This section makes the distinction explicit so that new
code does not accidentally adopt the transitional patterns.

### Input: what BaseForm accepts

**Intended** (new code):

```php
// PSR-7 request — the right choice for PSR-15 controllers
$form = new BaseForm($request, 'Edit Queue');

// Plain array — good for tests and CLI tools
$form = new BaseForm(['name' => 'Default'], 'Edit Queue');
```

**Upgrade compromise** (existing Horde apps):

```php
// Horde_Variables — accepted for backward compatibility
$vars = Horde_Variables::getDefaultVariables();
$form = new BaseForm($vars, 'Edit Queue');
```

`Horde_Variables` is accepted in the constructor union type because
existing Horde apps build one from `$_REQUEST` early in the request
cycle.  Internally, `normalizeVars()` immediately calls
`iterator_to_array()` on it — no Variables-specific features
(`filter()`, `sanitize()`, `$_expected` whitelist) survive into the
form.  New code should pass a PSR-7 request or a plain array.

### Internal data: why plain array

V3 stores form data as `private array $vars`.  This is intentional,
not a simplification.  The three things `Horde_Variables` provides
beyond a plain array — the `$_expected` whitelist, the `$_sanitized`
flag and the nested-key magic accessor — are **not needed** inside
the form:

- **Expected-fields whitelist**: The form knows which variables it
  declared.  Validation checks the form's own variable list, not a
  hidden field in the POST data.
- **Sanitization tracking**: V3 stores raw values and escapes on
  output (see "Output escaping" below).  Input-layer sanitization
  conflates two concerns.
- **Nested-key syntax** (`$vars->{'address[street]'}`): PHP's
  `$_POST` and PSR-7's `getParsedBody()` deliver nested arrays
  natively.  The string-based path syntax is a workaround for flat
  key spaces that PSR-7 does not need.

### Output escaping: renderer responsibility

V3 applies `htmlspecialchars()` at the render boundary, not the data
layer.  Every point where a value enters HTML goes through escaping:

- `HtmlControlRenderer::buildTag()` escapes all attribute values
- `renderLabel()` escapes the human name
- `renderHelp()` escapes the description
- `renderLongtext()` escapes textarea content
- `renderEnum()` escapes option keys and labels
- `TableLayout::wrapField()` escapes row classes
- `TableLayout::wrapSection()` escapes title and description
- `BaseRenderer::render()` escapes form name, action URL, etc.
- `BaseForm::preserve()` escapes hidden input names and values

**Do not** pre-sanitize values before passing them to the form.
The form stores raw data; the renderer escapes on output.

### Horde_Variables in validate() and getInfo()

**Upgrade compromise**: `BaseForm::validate()` and
`BaseForm::getInfoFromVariables()` re-wrap the internal array as
`new Horde_Variables($array)` before passing it to Variable objects.
This is because V3 Variable types inherit `getValue()` and
`validate()` methods from `BaseVariable`, which in turn delegates to
the `VariableMigrationInterface` — and that interface expects
`Horde_Variables`.

```
Input (Variables|PSR-7|array)
  -> normalizeVars() -> plain array        <- stored here
  -> new Horde_Variables(array)            <- re-wrapped for Variable API
  -> $var->validate($varsObject)
  -> $var->getValue($varsObject)
```

This re-wrapping is a **transitional shim**.  The `Horde_Variables`
constructed from a plain array has no `$_expected` whitelist, no
`$_sanitized` flag and no special behavior — it is just an
ArrayAccess wrapper.

**Rendering and preserve() no longer use this shim.**  The renderer,
`renderHidden()` and `preserve()` now call `$var->resolveValue()`
(see below), which works directly with arrays.  The shim only
remains in `validate()` and `getInfoFromVariables()`.

### resolveValue() — V3-native value resolution

`BaseVariable::resolveValue(array $vars, ?int $index = null): mixed`
is the V3-native alternative to `getValue(Horde_Variables)`.  It does
the same lookup/default-fallback logic but:

- Works directly with plain arrays (no `Horde_Variables` wrapping)
- Does **not** trigger attached Actions (actions are a submission-time
  concern, not a rendering concern)
- Is declared in `VariableV3Interface`, so all V3 variables implement it

**Who calls what:**

| Caller | Method | Why |
|---|---|---|
| `HtmlControlRenderer::getValue()` | `resolveValue()` | Rendering — no actions needed |
| `BaseRenderer::renderHidden()` | `resolveValue()` | Rendering hidden inputs |
| `BaseForm::preserve()` | `resolveValue()` | Serializing values for multi-step forms |
| `BaseForm::validate()` | `getValue()` via `Horde_Variables` | Validation — may need actions |
| `BaseForm::getInfoFromVariables()` | `getInfo()` via `Horde_Variables` | Extraction — type-specific processing |

New code reading variable values for display purposes should use
`resolveValue()`.  Code that needs to validate or extract typed
values for processing should continue using `getValue()`/`getInfo()`
until those methods are migrated to accept arrays directly.

### The addVariable() API

The `addVariable()` signature is intentionally close to lib/:

```php
// lib/
$form->addVariable('Queue Name', 'name', 'text', true);

// V3 — same positional arguments
$form->addVariable('Queue Name', 'name', 'text', true);

// V3 — but you can also use named parameters
$form->addVariable(
    humanName: 'Queue Name',
    varName: 'name',
    type: 'text',
    required: true,
    description: 'Internal queue identifier'
);
```

The `$type` parameter is a string, not a Type object.  BaseForm maps
it to a Variable class internally:

- `'text'` -> `Horde\Form\V3\TextVariable`
- `'enum'` -> `Horde\Form\V3\EnumVariable`
- `'whups:priority'` -> `Whups\Form\V3\PriorityVariable`

This mapping is **intended**, not a compromise — it keeps the form
definition readable and decoupled from class names.

---

## Rendering architecture

### lib/ (monolithic, echo-based)

```php
$renderer = $form->getRenderer();
$form->renderActive($renderer, $vars, 'submit.php', 'post');
// Outputs HTML directly via echo
```

The legacy renderer is a single class that owns layout, controls,
error display and asset management.  It writes directly to the
output buffer.

### V3 (composable, string-returning)

```php
$controlRenderer = new HtmlControlRenderer();
$layout = new TableLayout();
$errorRenderer = new InlineErrorRenderer();

$renderer = new BaseRenderer(
    controlRenderer: $controlRenderer,
    layoutStrategy: $layout,
    errorRenderer: $errorRenderer
);

$html = $renderer->render($form, 'submit.php', 'post');
```

The renderer is composed of three strategy objects:

| Strategy | Responsibility | Implementations |
|---|---|---|
| `ControlRenderer` | Renders `<input>`, `<select>`, labels, help text | `HtmlControlRenderer` |
| `LayoutStrategy` | Wraps fields into rows, sections, form structure | `TableLayout` |
| `ErrorRenderer` | Formats validation errors | `InlineErrorRenderer` |

All render methods **return strings** — nothing is echoed.  This
makes rendering testable, composable and compatible with PSR-7
response bodies.

### TableLayout: the upgrade compromise

`TableLayout` emits 2-column HTML `<table>` markup identical to
lib/'s `Horde_Form_Renderer`.  This is deliberate: existing Horde
themes have CSS targeting `<table>`, `<tr>`, `<td>` elements inside
`.horde-form`.  TableLayout produces HTML that existing theme CSS
can style without changes.

The inline `style="width: 15%"` on the label `<td>` is a known
compromise — themes that want labels above controls must use
`width: auto !important` in CSS to override it.

Future layouts (`DivLayout`, `ListLayout`) will use semantic HTML
without tables.  Switch by injecting a different `LayoutStrategy`.

---

## CSS class mapping

V3 renderers emit different class names from lib/.  Themes must
handle both if they support forms from both libraries.

| Concept | lib/ class | V3 class |
|---|---|---|
| Form wrapper | `.horde-form` | `.horde-form` (same) |
| Form table | `.horde-form table` | `.horde-form .form-table` |
| Label cell | `td.horde-form-label` | `td.label` |
| Required marker | `.horde-form-error` (on `<span>`) | `span.required` |
| Optional marker | (none) | `.optional` |
| Help text | `.horde-form-field-description` | `.help-text` |
| Validation error (inline) | (inline text) | `.field-error` |
| Error summary | `.horde-form-error` | `.form-errors` |
| Control cell | (no class) | `td.control` |
| Error state on control | (none) | `td.control.error` |
| Button area | `.horde-form-buttons` | `.form-buttons` |
| Button container | (directly in button area) | `.form-buttons-inner` |
| Form header | `.horde-form-header` | `.form-header` |
| Section header | (ad-hoc) | `.section-header` |
| Striped rows | (ad-hoc) | `.odd` |

**Important**: The `required` class appears on both `<td>` and
`<span>` in V3 (TableLayout puts it on both).  CSS rules must target
`span.required` specifically — targeting `.required` alone would
color the entire label cell (including help text) red.

Reference stylesheets are provided in `doc/css/`:
- `form-v3-classic.css` — approximates legacy Horde appearance
- `form-v3-modern.css` — clean, label-above-control layout

Themes that support both lib/ and V3 forms need **dual selectors**:

```css
/* Labels: cover both legacy and V3 */
.horde-form td.horde-form-label,
.horde-form td.label { ... }

/* Buttons: cover both */
.horde-form-buttons,
.horde-form .form-buttons { ... }

/* Help text: cover both */
.horde-form-field-description,
.horde-form .help-text { ... }
```

---

## Required / optional field markers

### lib/ convention

- Required: red `*` asterisk on the label
- Optional: no marker

### V3 convention (Material Design 3 aligned)

- Required: subtle `*` marker (not red unless the field has a
  validation error)
- Optional: `(optional)` text label, translatable via `_("optional")`
- Help text: no marker prefix (the description speaks for itself)

The `HtmlControlRenderer` produces:

```html
<!-- Required field -->
<label for="name">Queue Name <span class="required">*</span></label>

<!-- Optional field -->
<label for="slug">Slug <span class="optional">(optional)</span></label>

<!-- Help text (no prefix) -->
<span class="help-text">Slugs allow direct access to this queue.</span>
```

The markers are configurable via constructor parameters:

```php
$controlRenderer = new HtmlControlRenderer(
    requiredMarker: '*',    // change to '(required)' etc.
    helpMarker: '',         // default: no prefix
    controlMode: 'modern'   // 'modern' | 'legacy' | 'fallback'
);
```

---

## CSRF token protection

### lib/ approach (two-layer, globals-dependent)

Legacy forms use a belt-and-suspenders CSRF defence:

1. **Generate**: `Horde_Token::generateId($formName)` creates a random
   ID.  The ID is stored in `$GLOBALS['session']` under
   `form_secrets/{id}` and embedded as a hidden input
   `{formName}_formToken`.
2. **Validate**: On submit, the form fetches `Horde_Token` from
   `$GLOBALS['injector']`, calls `$tokenSource->verify($token)` for
   replay protection, then checks `$GLOBALS['session']` for the
   secret.  If the form was auto-filled, the token error is
   suppressed.

This couples the form library to `$GLOBALS['session']`,
`$GLOBALS['injector']` and `Horde_Token::generateId()` (a static
call).

### V3 approach (injected service, no globals)

V3 accepts a `Horde\Token\Token` service via constructor injection:

```php
use Horde\Form\V3\BaseForm;
use Horde\Token\Token;

// With CSRF protection (production)
$token = $injector->getInstance(Token::class);
$form = new BaseForm($vars, 'Edit Queue', token: $token);

// Without CSRF protection (tests, CLI)
$form = new BaseForm($vars, 'Edit Queue');
```

**Generate**: The renderer calls `$form->generateToken()`, which
delegates to `$tokenService->generate($formName)`.  The token is
HMAC-SHA256 signed — no session storage needed for authenticity.
The hidden field is named `{formName}_formToken` (same as legacy).

**Validate**: `$form->validate()` calls
`$tokenService->validateUnique($submitted, $formName)`.  This
verifies the HMAC signature, checks expiry and marks the token as
used (replay protection via the configured storage backend).  No
`$GLOBALS` involved.

**Disable**: Call `$form->useToken(false)` before rendering or pass
no `Token` to the constructor.

### Framework binding

`Horde\Token\Token` is registered in the Horde injector via
`Horde\Core\Factory\TokenServiceFactory`.  It reads the same
`$conf['token']` settings (driver, timeout, table) and session
secret as the legacy `Horde_Core_Factory_Token`.  Both services
coexist — legacy forms get `Horde_Token`, V3 forms get
`Horde\Token\Token`, from the same config.

### What changed

| Aspect | lib/ | V3 |
|---|---|---|
| Token generation | `Horde_Token::generateId()` (random ID) | `Token::generate()` (HMAC-SHA256 signed) |
| Authenticity check | Session secret (`$GLOBALS['session']`) | HMAC signature (self-validating) |
| Replay protection | `Horde_Token::verify()` via injector | `Token::validateUnique()` via injected service |
| Coupling | `$GLOBALS['session']`, `$GLOBALS['injector']` | Constructor parameter, no globals |
| Disable CSRF | `$form->useToken(false)` | `$form->useToken(false)` or omit Token |
| Hidden field name | `{formName}_formToken` | `{formName}_formToken` (same) |

---

## Migration steps

### 1. Form class

```php
// Before (lib/)
$form = new Horde_Form($vars, 'Edit Queue');

// After (V3) — without CSRF (tests, simple forms)
use Horde\Form\V3\BaseForm;
$form = new BaseForm($vars, 'Edit Queue');

// After (V3) — with CSRF (production)
use Horde\Form\V3\BaseForm;
use Horde\Token\Token;
$token = $injector->getInstance(Token::class);
$form = new BaseForm($vars, 'Edit Queue', token: $token);
```

Or subclass BaseForm for reusable form definitions:

```php
class EditQueueForm extends BaseForm
{
    public function __construct(
        Horde_Variables|ServerRequestInterface|array $vars,
        array $queues = []
    ) {
        parent::__construct($vars, _("Edit Queue"));
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Slug"), 'slug', 'text', false,
            description: _("URL-friendly identifier"));
        $this->setButtons([_("Save")]);
    }
}
```

### 2. Variable definitions

The `addVariable()` call is unchanged in basic usage:

```php
// Works in both lib/ and V3
$form->addVariable('Name', 'name', 'text', true);
$form->addVariable('Type', 'type', 'enum', true, false, null, [$typeList]);
```

### 3. getInfo() — the main breaking change

```php
// lib/ — modifies $info by reference
$form->getInfo($vars, $info);
echo $info['name'];

// V3 — returns array
$info = $form->getInfo();
echo $info['name'];
```

Note: V3's `getInfo()` can be called with no arguments (uses the
form's own data) or with explicit vars.

### 4. Rendering

```php
// lib/ — echo-based
$renderer = $form->getRenderer();
$form->renderActive($renderer, $vars, $action, 'post');

// V3 — string-returning
$renderer = new BaseRenderer(
    controlRenderer: new HtmlControlRenderer(),
    layoutStrategy: new TableLayout(),
    errorRenderer: new InlineErrorRenderer()
);
$html = $renderer->render($form, $action, 'post');
echo $html;  // or pass to a template / PSR-7 response
```

### 5. Custom Variable types

```php
// lib/ — separate Type class
class MyApp_Form_Type_zipcode extends Horde_Form_Type
{
    public function init($country = 'US') { ... }
    public function isValid($var, $vars, $value, $message) { ... }
}

// V3 — Variable subclass (Type merged in)
namespace MyApp\Form\V3;
use Horde\Form\V3\BaseVariable;

class ZipcodeVariable extends BaseVariable
{
    public function init(string $country = 'US'): void { ... }
    protected function isValid(Horde_Variables $vars, $value): bool
    {
        if (empty($value)) {
            return $this->invalid(_("ZIP code is required."));
        }
        return true;
    }
}

// Usage unchanged:
$form->addVariable('ZIP', 'zip', 'myapp:zipcode', true, false, null, ['US']);
```

### 6. Actions (onChange reload, etc.)

```php
// lib/
$var->setAction(Horde_Form_Action::factory('reload'));

// V3
use Horde\Form\V3\BaseAction;
$var->setAction(BaseAction::factory('reload'));

// V3 — direct instantiation (preferred)
use Horde\Form\V3\SubmitAction;
$var->setAction(new SubmitAction());
```

#### Action script wiring

V3 actions are wired automatically by the renderer. When a variable
has an attached action, `BaseRenderer::render()` emits a `<script>`
block after `</form>` with CSP-friendly `addEventListener` bindings
instead of legacy inline `onchange="..."` attributes.

The emitted script has two sections:

1. **Helper functions** — emitted first, defined by actions that
   override `getHelperScript()` (e.g. `UpdatefieldAction` defines
   `updateField_{id}()`, `ConditionalsetvalueAction` defines
   `mapValue_{id}()` and its lookup table)
2. **Event bindings** — `addEventListener('change', ...)`,
   `addEventListener('keyup', ...)`, etc. based on each action's
   `getTrigger()` array.  `onload` triggers use
   `document.addEventListener('DOMContentLoaded', ...)`.

Example output for a SubmitAction on an enum variable:

```html
</form>
<script>
document.forms['myform'].elements['category'].addEventListener('change', function() { document.myform.submit() });
</script>
```

**No configuration needed** — attaching an action to a variable is
sufficient.  The renderer discovers actions via `$var->hasAction()`
and `$var->getAction()`.

#### `printJavaScript()` → `getHelperScript()` migration

V3 replaces the legacy `printJavaScript(): void` pattern (which used
`echo` / output buffering) with `getHelperScript(): string` on the
`ActionV3Interface`.  This is a **breaking change** for custom V3
action subclasses that override `printJavaScript()`.

| Interface | Method | Purpose |
|---|---|---|
| `ActionMigrationInterface` | `printJavaScript(): void` | Legacy echo-based — kept for lib/ compat |
| `ActionV3Interface` | `getHelperScript(): string` | V3-native string return |

`BaseAction` implements both: `printJavaScript()` is a no-op void
method, `getHelperScript()` returns `''` by default.

**If you have a custom V3 action** that overrides `printJavaScript()`
to return a string, rename the method to `getHelperScript()`.  The
renderer calls `getHelperScript()`, not `printJavaScript()`:

```php
// Before (broken — printJavaScript overriding void with string)
public function printJavaScript(): string
{
    return 'function myHelper() { ... }';
}

// After (V3-native)
public function getHelperScript(): string
{
    return 'function myHelper() { ... }';
}
```

### 7. Buttons

```php
// lib/
$form->setButtons(true);  // default "Submit"

// V3 — same, but also supports array syntax
$form->setButtons([_("Save"), _("Save and Continue")]);
$form->setButtons(submit: [_("Save")], reset: _("Reset"));
```

### 8. Multi-step wizard forms

V3 supports multi-step wizard forms where each step is a separate
`BaseForm` subclass.  The controller validates all steps progressively
and renders the appropriate step.

#### Value preservation across steps

Each later step must carry forward earlier step values as hidden
fields.  V3 does **not** do this automatically — the form must
declare hidden fields for prior-step values:

```php
class WizardStepTwoForm extends BaseForm
{
    public function __construct(array $vars, array $types)
    {
        parent::__construct($vars, _("Step 2"));

        // Preserve step 1 value.
        $this->addHidden('', 'queue', 'int', true, true);

        // Step 2's own field.
        $this->addVariable(_("Type"), 'type', 'enum', true, false, null, [$types]);
    }
}
```

When step 2 renders, the hidden `queue` field carries the step 1
value in the `<form>`.  When the user submits step 2, the POST data
includes both `queue` (from hidden) and `type` (from the dropdown).

For steps with many prior-step values, accept a list of IDs to
generate hidden fields dynamically:

```php
class WizardStepFourForm extends BaseForm
{
    public function __construct(array $vars, array $users, array $attributeIds = [])
    {
        parent::__construct($vars, _("Step 4"));

        // Known prior-step fields.
        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'state', 'int', true, true);
        $this->addHidden('', 'summary', 'text', true, true);
        // ... etc.

        // Dynamic attributes — IDs passed from controller.
        foreach ($attributeIds as $attrId) {
            $this->addHidden('', 'attributes[' . $attrId . ']', 'text', false, true);
        }

        // Step 4's own fields.
        $this->addVariable(_("Owners"), 'owners', 'multienum', false, false, null, [$users]);
    }
}
```

The `preserve()` method is an alternative that generates hidden
inputs for all of a form's variables as a string.  However, because
`render()` produces a self-contained `<form>` element, injecting
`preserve()` output into another form's rendered HTML requires string
manipulation.  Declaring hidden fields explicitly is more robust.

#### Progressive validation

In a multi-step wizard, the controller validates all steps
progressively.  When step 3 is submitted, steps 1 and 2 must also
validate (their data is in the POST via hidden fields).

**Do not** gate earlier-step validation on `isSubmitted()`.  The
submitted formname matches only the active step's form, so earlier
steps' `isSubmitted()` returns false.  Instead, call `validate()`
directly on all steps:

```php
// Step 1: no CSRF, just validate data.
$form1 = new StepOneForm($formVars, $queues);
if (!$form1->validate()) {
    return $this->renderStep(1, $form1);
}

// Step 2: no CSRF, just validate data.
$form2 = new StepTwoForm($formVars, $types);
if (!$form2->validate()) {
    return $this->renderStep(2, $form2);
}

// Step 3: has CSRF — see next section.
$form3 = new StepThreeForm($formVars, $states);
// ...
```

Use `isSubmitted()` + `validate()` only for the **final step** where
you need to distinguish "not yet submitted" (show the form) from
"submitted with errors" (show validation errors).

#### CSRF tokens in multi-step wizards

Only one step can validate its CSRF token per request — the step
that was actually submitted.  Earlier steps' tokens were consumed
on previous requests; later steps haven't been submitted yet.

**Pattern**: Disable CSRF on intermediate steps.  Enable it only on
the step that is the natural final submission:

```php
class StepOneForm extends BaseForm
{
    public function __construct(array $vars, array $queues)
    {
        parent::__construct($vars, _("Step 1"));
        $this->useFormToken = false;  // Intermediate step — no CSRF.
        // ...
    }
}

class StepThreeForm extends BaseForm
{
    public function __construct(array $vars, array $states)
    {
        parent::__construct($vars, _("Step 3"));
        // CSRF enabled by default — this is the final step in common case.
        // ...
    }
}
```

When a later step (step 4) exists conditionally, the controller
disables the intermediate step's token before re-validation:

```php
$form3 = new StepThreeForm($formVars, $states);

// Step 4 was submitted — step 3's token was consumed previously.
if ($formname !== $form3->getName()) {
    $form3->useToken(false);
}

if (!$form3->validate()) {
    return $this->renderStep(3, $form3);
}
```

This avoids a token-missing validation error when re-validating a
step that was already successfully submitted in a prior request.

#### Rendering inactive steps as summaries

V3 supports `renderInactive()` for display-only form rendering.
Completed wizard steps are rendered as read-only output where
form values appear as plain text (enum shows label, boolean shows
Yes/No, password shows asterisks) with no `<form>` tag, no buttons,
no hidden fields and no CSRF token:

```php
$renderer = new HtmlRenderer(new TableLayout());

// Step 1: completed — render as display-only summary.
echo $renderer->renderInactive($form1);
echo '<br />';

// Step 2: completed — render as display-only summary.
echo $renderer->renderInactive($form2);
echo '<br />';

// Step 3: active — render as editable form.
echo $renderer->render($form3, $actionUrl, 'post');
```

The forms used for `renderInactive()` are the same objects created
for validation.  They already contain the submitted values and the
enum arrays needed for label resolution.  No separate lookup tables
or manual HTML are required.

Alternatively, you can use `setMode('inactive')` / `render()`:

```php
$renderer->setMode('inactive');
$html = $renderer->render($completedForm, '', '');
$renderer->setMode('active');
```

#### File upload preservation across steps

When a file is uploaded in step N but step N+1 exists, the uploaded
file must be preserved.  PHP discards `$_FILES` between requests,
so the controller moves the file to temp storage:

```php
// After step 3 validates and before rendering step 4:
$info = $form3->getInfo();
if (!empty($info['newattachment']['name'])) {
    $tmpPath = Horde::getTempFile('whups', false);
    move_uploaded_file($info['newattachment']['tmp_name'], $tmpPath);
    $session->set('app', 'deferred/' . $info['newattachment']['name'], $tmpPath);
    $formVars['deferred_attachment'] = $info['newattachment']['name'];
}
```

Step N+1's form carries the filename as a hidden field.  On final
submission, the ticket-creation logic checks both `newattachment`
(direct upload) and `deferred_attachment` (preserved from a prior
step).

---

## Form-level validation

### lib/ approach

Legacy `Horde_Form` has no cross-field validation mechanism.  Forms
that need it override `validate()` entirely or perform checks in
controller code after validation, leading to duplicated or scattered
validation logic.

### V3 approach

V3 provides two complementary mechanisms for validation beyond
individual field checks:

1. **`FormValidator` interface** — reusable, composable validators
   registered via `addValidator()`.
2. **`validateForm()` override** — a protected method subclasses
   override for form-specific cross-field checks.

Both run after field-level validation and CSRF token checks.
Execution order: field validation → registered validators (in
registration order) → `validateForm()` override.

#### FormValidator interface

```php
namespace Horde\Form\V3;

interface FormValidator
{
    public function validate(array $vars, array &$errors): void;
}
```

Validators receive the full form data and the error map by reference.
They can inspect existing errors (from field validation or earlier
validators) and add new ones keyed by variable name:

```php
use Horde\Form\V3\FormValidator;

class DateRangeValidator implements FormValidator
{
    public function __construct(
        private readonly string $fromField,
        private readonly string $toField,
    ) {}

    public function validate(array $vars, array &$errors): void
    {
        $from = $vars[$this->fromField] ?? '';
        $to = $vars[$this->toField] ?? '';
        if ($from !== '' && $to !== '' && $from > $to) {
            $errors[$this->toField] = _("Must be after start date.");
        }
    }
}

// Usage
$form->addValidator(new DateRangeValidator('date_from', 'date_to'));
```

`addValidator()` returns `$this` for fluent chaining:

```php
$form
    ->addValidator(new DateRangeValidator('from', 'to'))
    ->addValidator(new FieldsMatchValidator('password', 'confirm'));
```

#### validateForm() override

For form-specific checks that aren't reusable, override the
protected `validateForm()` method in your form subclass:

```php
class MyForm extends BaseForm
{
    protected function validateForm(array $vars, array &$errors): void
    {
        if (($vars['min'] ?? 0) > ($vars['max'] ?? 0)) {
            $errors['max'] = _("Maximum must be greater than minimum.");
        }
    }
}
```

This runs after all registered `FormValidator` instances, so it can
see errors they added and decide whether to add more.

#### When to use which

| Scenario | Mechanism |
|---|---|
| Reusable constraint (date range, fields match) | Named `FormValidator` class |
| One-off form-specific check | `validateForm()` override |
| Simple inline check (tests, prototypes) | Anonymous `FormValidator` class |

---

## FieldGroup and Section

### lib/ approach

Legacy `Horde_Form` sections are metadata-only arrays — a section name,
description, image and expanded state stored in `$this->sections`.
Variables are bucketed by section name in `$this->variables`.  Sections
have no behavior: no name scoping, no per-section validation, no
reusability.

### V3 approach

V3 introduces a two-tier model:

| Class | Purpose |
|---|---|
| `FieldGroup` | Structural base: holds variables, optional name prefix, `validateGroup()` override |
| `Section extends FieldGroup` | Adds visual metadata: title, description, image, expanded state |

Both implement `FormValidator`, so group-level validation participates
in the same pipeline as registered validators.

#### FieldGroup: structural variable grouping

```php
use Horde\Form\V3\FieldGroup;

// Plain group — no prefix, variables keep their original names
$group = new FieldGroup('contact');

// Prefixed group — variables get bracket-notation names
$group = new FieldGroup('billing', prefix: 'billing');
$group->addVariable('Street', 'street', 'text', true);
// Variable name: 'billing[street]'
```

When a group has a prefix, `addVariable()` scopes the variable name
using bracket notation (`prefix[varName]`).  PHP's POST parsing
automatically decodes this into nested arrays:

```
<input name="billing[street]" value="123 Main">
→ $_POST['billing']['street'] === '123 Main'
```

`resolveValue()` on `BaseVariable` handles the reverse lookup:
`billing[street]` navigates `$vars['billing']['street']` transparently.

#### Section: FieldGroup with visual metadata

```php
use Horde\Form\V3\Section;

$section = new Section(
    name: 'billing',
    title: 'Billing Address',
    description: 'Enter your billing details',
    image: '/icons/billing.png',
    expanded: true,
    prefix: 'billing',   // optional — same scoping as FieldGroup
);
```

Sections appear in the rendered form as collapsible headers with
title, description and optional image.  The existing `setSection()`
API creates Section objects internally.

#### Using groups in BaseForm

**Via `setSection()` (backward compatible):**

```php
$form = new BaseForm($vars, 'Checkout');

// Without prefix — same behavior as lib/
$form->setSection('Personal', 'Your personal details');
$form->addVariable('Name', 'name', 'text', true);

// With prefix — variables get scoped names
$form->setSection('Billing', 'Billing address', '', true, 'billing');
$form->addVariable('Street', 'street', 'text', true);
// → variable name: 'billing[street]'
```

The `prefix` parameter is the only addition to the `setSection()`
signature.  Existing callers that don't pass it see no change.

**Via `addGroup()` (new API):**

```php
$form = new BaseForm($vars, 'Checkout');

$form->addGroup(new FieldGroup('billing', 'billing'));
$form->addVariable('Street', 'street', 'text', true);

$form->addGroup(new FieldGroup('shipping', 'shipping'));
$form->addVariable('Street', 'street', 'text', true);
```

`addGroup()` accepts any `FieldGroup` (or subclass like `Section`)
and sets it as the current group for subsequent `addVariable()` calls.
It returns `$this` for fluent chaining.

**Retrieving groups:**

```php
$group = $form->getGroup('billing');  // FieldGroup|null
$vars = $form->getVariables(flat: true);   // all variables
$vars = $form->getVariables(flat: false);  // array<groupName, Variable[]>
```

#### Group-level validation

`FieldGroup` implements `FormValidator`.  Subclasses override
`validateGroup()` for cross-field validation scoped to the group:

```php
class BillingGroup extends FieldGroup
{
    public function __construct()
    {
        parent::__construct('billing', 'billing');
    }

    protected function validateGroup(array $vars, array &$errors): void
    {
        // $vars contains only the billing sub-array:
        // ['street' => '...', 'city' => '...', 'zip' => '...']
        if (empty($vars['zip']) && ($vars['country'] ?? '') === 'US') {
            $errors['billing[zip]'] = _("ZIP code required for US addresses.");
        }
    }
}

$form->addGroup(new BillingGroup());
$form->addVariable('Street', 'street', 'text', true);
$form->addVariable('City', 'city', 'text', true);
$form->addVariable('ZIP', 'zip', 'text', false);
$form->addVariable('Country', 'country', 'enum', true, false, null, [$countries]);
```

For prefixed groups, `validateGroup()` receives only the group's
sub-array (e.g., `$vars['billing']`), not the full form data.
For unprefixed groups, it receives the complete form data.

#### Validation pipeline order

1. **CSRF token** — `Token::validateUnique()`
2. **Field-level** — each Variable's `isValid()`
3. **Group-level** — each FieldGroup's `validateGroup()`
4. **Registered validators** — `addValidator()` instances
5. **Form-level** — `validateForm()` subclass override

Groups are validated after fields but before registered validators,
so group validators can see field errors but registered validators
can see both field and group errors.

#### Bracket-notation value resolution

Variables with prefixed names (e.g., `billing[street]`) work
transparently across the form API:

- **`resolveValue()`** — navigates nested arrays for rendering
- **`getValue()`** — works via `Horde_Variables` (upgrade shim)
- **`preserve()`** — emits `<input name="billing[street]" ...>`
- **`getInfo()`** — returns values keyed by full bracket-notation name

---

## File uploads via PSR-7

### Overview

When a `ServerRequestInterface` is passed to BaseForm's constructor,
uploaded files are extracted automatically via `getUploadedFiles()` and
injected into `FileVariable` and `ImageVariable` instances before
`validate()` and `getInfo()` run.  No `$_FILES` access or
`$GLOBALS['browser']` dependency is needed on the PSR-7 path.

### Usage

```php
use Horde\Form\V3\BaseForm;

// PSR-7 request from your middleware / router
$form = new BaseForm($request, 'Upload Document');
$form->addVariable('Title', 'title', 'text', true);
$form->addVariable('Document', 'document', 'file', true);

if ($form->validate()) {
    $info = $form->getInfo();
    // $info['document'] contains:
    //   'name'          => 'report.pdf' (client filename)
    //   'type'          => 'application/pdf' (client media type)
    //   'size'          => 45678 (bytes)
    //   'tmp_name'      => '/tmp/horde_form_upload_abc123' (on-disk temp file)
    //   'file'          => same as tmp_name
    //   'error'         => UPLOAD_ERR_OK
    //   'uploaded_file' => UploadedFileInterface instance
    rename($info['document']['tmp_name'], $permanentPath);
}
```

### Important: move_uploaded_file() does NOT work

The temp file is created by writing the PSR-7 stream to disk.  PHP's
`move_uploaded_file()` rejects it because PHP did not create it via
its upload mechanism.  Use `rename()` or the `UploadedFileInterface`
object's `moveTo()` method instead:

```php
// Option A: rename (simple, works on same filesystem)
rename($info['document']['tmp_name'], $dest);

// Option B: PSR-7 moveTo (works cross-filesystem, preferred)
$info['document']['uploaded_file']->moveTo($dest);
```

### Legacy fallback

When form data is provided as an array or `Horde_Variables` (i.e., not
a full PSR-7 request), no uploaded files are extracted from the request.
`FileVariable` and `ImageVariable` fall back to reading `$_FILES` and
using `$GLOBALS['browser']->wasFileUploaded()`.  Existing code using
this pattern is unchanged.

### Explicit file injection

When you decompose the PSR-7 request before passing form data:

```php
$formVars = $request->getParsedBody();
$form = new BaseForm($formVars, 'Upload');
$form->setUploadedFiles($request->getUploadedFiles());
```

This is equivalent to passing the full request.

### Custom file-type variables

To create a custom variable type that participates in PSR-7 file
injection, implement `FileUploadAware`:

```php
use Horde\Form\V3\BaseVariable;
use Horde\Form\V3\FileUploadAware;
use Psr\Http\Message\UploadedFileInterface;

class AvatarVariable extends BaseVariable implements FileUploadAware
{
    private ?UploadedFileInterface $uploadedFile = null;

    public function setUploadedFile(?UploadedFileInterface $file): void
    {
        $this->uploadedFile = $file;
    }

    protected function isValid($vars, $value): bool
    {
        if ($this->uploadedFile !== null) {
            if ($this->uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return $this->invalid('Upload failed.');
            }
            if ($this->uploadedFile->getSize() > 2_000_000) {
                return $this->invalid('Avatar must be under 2MB.');
            }
            return true;
        }
        // Legacy fallback...
    }
}
```

BaseForm resolves the upload by matching the variable's name against the
request's uploaded files tree.  For `ImageVariable`, the key is
`{varname}[new]` to match the image upload widget's HTML structure.

---

## Asset management and page output integration

Form V3's `AssetManager` interface (`Horde\Form\V3\Renderer\AssetManager`)
collects JS/CSS dependencies declared by form controls during rendering.

### Default behavior (standalone)

By default, `HtmlRenderer` creates an `HtmlAssetManager` that renders
collected assets as inline `<script>` and `<link>` tags appended after
the closing `</form>` tag. This is self-contained and requires no
external dependencies.

```php
$renderer = new HtmlRenderer();
$html = $renderer->render($form, $url, 'post');
// $html includes <link> and <script> tags at the end
```

### Page-integrated rendering

When forms are rendered inside a full Horde page (with header, topbar,
footer), assets should be placed in `<head>` or deferred to end-of-body
rather than appearing inline. Use `PageOutputAssetManager` from
`horde/core`:

```php
use Horde\Core\PageOutput\PageOutputAssetManager;
use Horde\Core\PageOutput\AssetCollector;
use Horde\Form\V3\HtmlRenderer;

// In a PSR-15 controller with DI:
class MyController
{
    public function __construct(
        private readonly AssetCollector $assetCollector,
    ) {}

    public function handle(): ResponseInterface
    {
        $assetManager = new PageOutputAssetManager($this->assetCollector);
        $renderer = new HtmlRenderer(assetManager: $assetManager);

        // Form HTML contains no <script>/<link> tags
        $formHtml = $renderer->render($form, $url, 'post');

        // Assets are rendered by PageComposer in head/foot
    }
}
```

`PageOutputAssetManager` delegates all `addScript()`, `addStylesheet()`,
`addInlineScript()`, and `addInlineStyle()` calls to Core's
`AssetCollector`. Its `render()` method returns an empty string because
`PageComposer` handles actual HTML output with correct placement and
deduplication.

### Which to use

| Scenario | AssetManager | Result |
|----------|--------------|--------|
| Standalone page / CLI / testing | `HtmlAssetManager` (default) | Inline tags after form |
| Inside Horde chrome (header/footer) | `PageOutputAssetManager` | Assets in head/deferred foot |
| Custom page builder | Implement `AssetManager` | Your choice |

---

## Known limitations (2026-05)

- **Sub-forms**: Replaced by `FieldGroup` / `Section` model (see
  "FieldGroup and Section" below).  Groups provide structural variable
  grouping with optional name-prefix scoping and per-group validation.
  Sections add visual metadata (title, description, image, expanded)
  on top.  Full sub-form orchestration (multiple independent `BaseForm`
  instances with shared CSRF) remains a future scope item.
- **DivLayout / ListLayout**: Not yet implemented.  TableLayout is
  the only LayoutStrategy.  Responsive, table-less rendering is a
  future scope item.
- **validate() / getInfo() still wrap Horde_Variables**: These
  submission-time paths still construct `new Horde_Variables($array)`.
  Rendering paths have been migrated to `resolveValue()`.

---

## Bugs found and fixed during Whups battle-test (2026-04)

These were discovered while converting the Whups admin controller
from lib/ to V3.  All are fixed — documented here for awareness.

### renderLink() was broken

`HtmlControlRenderer::renderLink()` rendered `<input type="url">`
(a URL text input) instead of `<a href>` hyperlinks.  `LinkVariable`
stores link definitions in `$var->values` (array of hashes with
`url`, `text`, `target`, `onclick`, `title`, `accesskey`, `class`
keys), but the renderer ignored this entirely.

**Fixed**: `renderLink()` now reads `$var->values` directly and
renders one `<a href>` tag per link definition.

### renderSet() strict comparison mismatch

`renderSet()` used `in_array($key, $selected, true)` (strict type
comparison).  When form data arrives via HTTP, selected values are
strings (`"1"`, `"2"`) but `$values` array keys may be integers.
This caused checkboxes to never appear checked.

**Fixed**: Changed to loose comparison `in_array($key, $selected)`.

### getFieldId() duplicate IDs

`getFieldId($var, $new = true)` reset its counter to 0 on every
call with `$new = true`, producing duplicate IDs (e.g. all checkboxes
in a set had `id="state"`).

**Fixed**: Counter now increments on subsequent `$new = true` calls.

### Checkbox display crushed by legacy CSS

V3's `renderSet()` wraps each checkbox+label in
`<div class="checkbox">`.  Legacy Horde themes have a rule
`.checkbox, .radio { height: 14px; width: 14px; }` targeting old
`<input class="checkbox">` elements.  This crushes the wrapper div.

**Fixed**: Added override rules to `base/themes/default/screen.css`
and the reference stylesheets:

```css
.horde-form div.checkbox {
    display: inline-block;
    width: auto !important;
    height: auto !important;
    /* ... */
}
```

### Driver methods returning false vs null

Legacy driver methods like `getDefaultState()` and
`getDefaultPriority()` return `false` when no default is set.  V3
form constructors with typed parameters (`int|string|null`) reject
`false`.

**Pattern**: Coerce at the call site with `?: null`.

---

## Updating this document

This is a living document.  Amend it as V3 matures:

- When a limitation is resolved, move it from "Known limitations"
  to the relevant section with usage examples.
- When a transitional shim is removed, update "Intended usage vs
  upgrade compromises" to reflect the change.
- Keep the concept mapping table and CSS class mapping table
  current as renderers evolve.
