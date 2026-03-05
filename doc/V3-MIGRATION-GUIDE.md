# Horde_Form: lib/ vs src/V3 Developer Guide

**Version**: 3.0.0-beta3+
**Date**: 2026-03-05
**Audience**: Horde application developers
**Status**: V3 is incomplete (BaseForm, Actions, Renderer not yet implemented)

---

## Quick Decision Guide

**Should I use lib/ or src/V3?**

```
┌─────────────────────────────────────────────────────────────┐
│  Are you maintaining an existing Horde 5/6 application?    │
│  ├─ YES → Use lib/ (legacy)                                │
│  └─ NO  → Continue ↓                                        │
│                                                              │
│  Do you need Actions (reload, submit, etc)?                 │
│  ├─ YES → Use lib/ (V3 Actions not ported yet)             │
│  └─ NO  → Continue ↓                                        │
│                                                              │
│  Do you need custom Renderer?                               │
│  ├─ YES → Use lib/ (V3 Renderer not ported yet)            │
│  └─ NO  → Continue ↓                                        │
│                                                              │
│  Are you starting a NEW application from scratch?           │
│  ├─ YES → Use V3 (modern, future-proof)                    │
│  └─ NO  → Use lib/ (safer for existing code)               │
└─────────────────────────────────────────────────────────────┘
```

**Summary**: Use **lib/** for existing/legacy apps. Use **V3** for new greenfield projects that don't need Actions/Renderer yet.

---

## Overview

Horde_Form has two parallel implementations:

1. **lib/** - Legacy implementation (Horde 5/6 era)
   - Complete: Form, Variable, Type, Action, Renderer
   - Ancient patterns: Reference passing, no type hints
   - Stable, widely used, will be maintained through H6

2. **src/V3/** - Modern implementation (Horde 7+ future)
   - Incomplete: BaseVariable ✅, BaseForm ❌, Actions ❌, Renderer ❌
   - Modern patterns: Type hints, namespaces, no references
   - Under development, breaking changes possible

**Important**: These implementations **cannot be mixed** in the same form. Choose one or the other.

---

## When to Use lib/ (Legacy)

### ✅ Use lib/ When:

1. **Maintaining existing applications**
   ```php
   // Existing Horde app code
   $form = new Horde_Form($vars, 'Edit Task');
   $form->addVariable('Name', 'name', 'text', true);
   ```

2. **Need Actions system**
   ```php
   // Actions not in V3 yet
   $var->setAction(Horde_Form_Action::factory('reload'));
   ```

3. **Need Renderer system**
   ```php
   // Renderer not in V3 yet
   $renderer = $form->getRenderer();
   $renderer->render();
   ```

4. **Mixed with other lib/-style code**
   ```php
   // Your app uses lib/ Horde_Form elsewhere
   // Keep consistency
   ```

5. **Need stability for production**
   - lib/ is complete, tested, stable
   - V3 is under development

### ❌ Don't Use lib/ For:

1. **New greenfield applications** (use V3 if possible)
2. **Code that will need PHP 9+ support** (references deprecated)
3. **Code requiring modern IDE support** (no type hints in lib/)

---

## When to Use src/V3/ (Modern)

### ✅ Use V3 When:

1. **Starting new applications from scratch**
   ```php
   // New app, no legacy code
   use Horde\Form\V3\BaseForm;
   $form = new BaseForm($vars, 'New Form');
   ```

2. **Full application conversions**
   - Converting entire app from lib/ to V3
   - All-or-nothing per form

3. **Want modern PHP patterns**
   ```php
   // Type hints, return types
   public function validate(): bool { }
   public function getInfo($vars = null): array { }
   ```

4. **Don't need Actions/Renderer yet**
   - Simple forms with validation only
   - Can wait for Actions/Renderer porting

5. **Want future-proof code**
   - V3 is the direction for Horde 7+
   - lib/ will be deprecated eventually

### ❌ Don't Use V3 For:

1. **Production apps** (V3 BaseForm not complete yet)
2. **Forms needing Actions** (not ported yet)
3. **Forms needing custom Renderer** (not ported yet)
4. **Mixed with lib/ code** (incompatible interfaces)

---

## Migration Path: lib/ → V3

### Can I Mix lib/ and V3?

**NO.** lib/ and V3 have incompatible interfaces and cannot be mixed in the same form.

**Incompatibilities:**

| Aspect | lib/ | V3 | Compatible? |
|--------|------|-----|-------------|
| Variable class | `Horde_Form_Variable` | `Horde\Form\V3\Variable` | ❌ No |
| Type classes | Separate `Horde_Form_Type_*` | Merged into `*Variable` | ❌ No |
| Validation signature | `isValid($var, $vars, $value, $message)` | `isValid($vars, $value): bool` | ❌ No |
| Error handling | `$message` by reference | `invalid()` method | ❌ No |
| getInfo() | Modifies `&$info` parameter | Returns array | ❌ No |

### Migration is Per-Form, All-or-Nothing

**Option 1: Keep lib/ (no changes needed)**
```php
// Existing code - works fine
$form = new Horde_Form($vars, 'Edit Task');
$form->addVariable('Name', 'name', 'text', true);
$form->addVariable('Email', 'email', 'email', true);
if ($form->validate()) {
    $form->getInfo($vars, $info);
}
```

**Option 2: Convert to V3 (full rewrite)**
```php
// V3 version - when BaseForm is ready
use Horde\Form\V3\BaseForm;
$form = new BaseForm($vars, 'Edit Task');
$form->addVariable('Name', 'name', 'text', true);
$form->addVariable('Email', 'email', 'email', true);
if ($form->validate()) {
    $info = $form->getInfo($vars);  // Returns array!
}
```

**You cannot do this:**
```php
// ❌ WRONG - mixing lib/ Form with V3 Variable
$form = new Horde_Form($vars);  // lib/
$var = new Horde\Form\V3\TextVariable('Name', 'name', true);  // V3
$form->addVariable($var);  // ERROR: Incompatible!
```

### Migration Steps (When V3 BaseForm is Ready)

1. **Identify forms to migrate**
   - Start with simple forms (no Actions, no custom Renderer)
   - Leave complex forms on lib/ for now

2. **Update class names**
   ```php
   // Before
   use Horde_Form;
   $form = new Horde_Form($vars, 'Title');

   // After
   use Horde\Form\V3\BaseForm;
   $form = new BaseForm($vars, 'Title');
   ```

3. **Update getInfo() calls**
   ```php
   // Before (lib/)
   $form->getInfo($vars, $info);
   // $info is modified by reference

   // After (V3)
   $info = $form->getInfo($vars);
   // Returns array
   ```

4. **Test thoroughly**
   - V3 validation is different internally
   - Error messages may differ
   - Behavior should match but verify

5. **Keep Actions/Renderer on lib/ for now**
   - Only migrate simple forms to V3
   - Complex forms stay on lib/ until V3 Actions/Renderer ready

---

## Architecture Comparison

### lib/ (Legacy) Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      Horde_Form                         │
│  - Constructor: __construct($vars, $title, $name)      │
│  - addVariable(...) → creates Horde_Form_Variable      │
│  - validate() → calls Variable::validate()             │
│  - getInfo() → extracts values                          │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│                 Horde_Form_Variable                     │
│  - Constructor: __construct($name, $varName, $type, ...)│
│  - type: Horde_Form_Type object (separate)             │
│  - validate() → calls $this->type->isValid()           │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│              Horde_Form_Type_text (etc)                 │
│  - init($regex, $size, $maxlength)                     │
│  - isValid($var, $vars, $value, $message)              │
│  - $message passed by REFERENCE (modified)             │
└─────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- **3 objects**: Form, Variable, Type (separate)
- **Reference passing**: `$this->_vars = &$vars`, `$message` by reference
- **No type hints**: Dynamic typing throughout
- **Ancient patterns**: Singleton, global namespace

### V3 (Modern) Architecture

```
┌─────────────────────────────────────────────────────────┐
│              Horde\Form\V3\BaseForm                     │
│  - Constructor: __construct($vars, string $title, ...)  │
│  - addVariable(...): Variable → creates *Variable      │
│  - validate(): bool → typed return                      │
│  - getInfo($vars = null): array → returns array        │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│           Horde\Form\V3\TextVariable                    │
│  - Extends: BaseVariable                                │
│  - NO separate Type object (merged!)                    │
│  - init(...$params) → variadic                          │
│  - validate($vars): bool → typed return                 │
│  - isValid($vars, $value): bool → internal             │
│  - invalid($message): bool → error handling             │
└─────────────────────────────────────────────────────────┘
```

**Key characteristics:**
- **2 objects**: Form, Variable (Type merged into Variable)
- **No reference parameters**: Clean signatures
- **Type hints everywhere**: `bool`, `array`, `string`
- **Modern patterns**: Namespaces, PSR-4

---

## Breaking Changes: lib/ → V3

### 1. Type/Variable Merge

**lib/ (separate):**
```php
$var = new Horde_Form_Variable('Name', 'name', $type, true);
$type = $var->getType();  // Returns Horde_Form_Type_text
$type->isValid($var, $vars, $value, $message);
```

**V3 (merged):**
```php
$var = new TextVariable('Name', 'name', true);
// No separate Type object!
$var->validate($vars);  // Variable IS the type
$var->getType();  // Returns $this (with deprecation warning)
```

**Impact**: Internal only - `addVariable()` API stays the same.

### 2. Validation Signature

**lib/:**
```php
// In Horde_Form_Type
public function isValid($var, $vars, $value, $message)
{
    // Modify $message by reference
    $this->message = 'Error';
    return false;
}
```

**V3:**
```php
// In *Variable
protected function isValid(Horde_Variables $vars, $value): bool
{
    // Use invalid() method
    return $this->invalid('Error message');
}
```

**Impact**: Custom type implementations need rewrite.

### 3. getInfo() Signature

**lib/:**
```php
$form->getInfo($vars, $info);
// $info modified by reference
echo $info['name'];
```

**V3:**
```php
$info = $form->getInfo($vars);
// Returns array
echo $info['name'];
```

**Impact**: All getInfo() calls need update.

### 4. Error Handling

**lib/:**
```php
$var->type->isValid($var, $vars, $value, $message);
// $message set by reference
echo $message;
```

**V3:**
```php
$var->validate($vars);
echo $var->getMessage();  // Getter instead
```

**Impact**: Error retrieval pattern changes.

### 5. Validation Method Parameter

**lib/:**
```php
// validate() takes $message parameter
$var->validate($vars, $message);
```

**V3:**
```php
// validate() no longer takes $message
$var->validate($vars);  // Deprecation warning if you pass $message
```

**Impact**: Remove $message parameter from all validate() calls.

---

## Gotchas: Odd lib/ Behavior in PHP 8.4+

### 1. Non-Static Singleton Called Statically

**Problem:**
```php
// In Horde_Form_Action
public function singleton($action)  // Non-static method
{
    static $instances = [];
    // ...
}

// Called statically
$action = Horde_Form_Action::singleton('reload');  // ERROR in PHP 8.4
```

**Error**: "Non-static method Horde_Form_Action::singleton() cannot be called statically"

**Workaround**: Use `factory()` instead:
```php
$action = Horde_Form_Action::factory('reload');  // Works
```

**V3 Fix**: Remove singleton pattern entirely.

### 2. Reference Passing Deprecated

**Problem:**
```php
// lib/ uses references everywhere
$this->_vars = &$vars;
$this->_hiddenVariables[] = &$var;
public function getInfo($vars, &$info) { }
```

**Issue**: Reference passing may be deprecated in PHP 9+

**Workaround**: None for lib/, avoid in new code

**V3 Fix**: Minimal reference usage, return values instead

### 3. No Type Hints = No IDE Support

**Problem:**
```php
// lib/ has no type hints
public function validate($vars) { }
public function getInfo($vars, $info) { }
```

**Issue**:
- No IDE autocomplete
- No static analysis
- Hard to refactor

**V3 Fix**: Full type hints everywhere
```php
public function validate($vars): bool { }
public function getInfo($vars = null): array { }
```

### 4. $message Reference Parameter

**Problem:**
```php
// Easy to forget to pass by reference
$type->isValid($var, $vars, $value, $message);  // $message not declared
// $message stays empty, no error shown!
```

**Workaround**: Always declare `$message = ''` before calling

**V3 Fix**: No $message parameter, use `invalid()` method

### 5. Polymorphic Signatures

**Problem:**
```php
// addVariable has 4-7 parameters depending on usage
public function addVariable($humanName, $varName, $type, $required,
                            $readonly = false, $description = null, $params = [])
```

**Issue**: Easy to pass wrong number/order of parameters

**V3 Fix**: Same signature but with type hints for safety

---

## API Compatibility Matrix

### Form API

| Method | lib/ Signature | V3 Signature | Compatible? |
|--------|----------------|--------------|-------------|
| Constructor | `__construct($vars, $title, $name)` | `__construct($vars, string $title, ?string $name)` | ✅ Yes (types added) |
| addVariable | `addVariable(...)` → `Horde_Form_Variable` | `addVariable(...): Variable` | ✅ Yes (return type) |
| validate | `validate()` → bool | `validate(): bool` | ✅ Yes (type hint) |
| getInfo | `getInfo($vars, &$info)` | `getInfo($vars = null): array` | ❌ **NO** (signature change) |
| setError | `setError($var, $message)` | `setError($var, string $message)` | ✅ Yes (type hint) |
| getError | `getError($var)` | `getError($var): ?string` | ✅ Yes (type hint) |

### Variable API

| Method | lib/ Signature | V3 Signature | Compatible? |
|--------|----------------|--------------|-------------|
| Constructor | `__construct($name, $varName, $type, ...)` | `__construct($name, $varName, $required, ...)` | ❌ **NO** (no $type param) |
| validate | `validate($vars, $message)` | `validate($vars): bool` | ❌ **NO** ($message removed) |
| getValue | `getValue($vars, $index)` | `getValue($vars, $index = null)` | ✅ Yes (default added) |
| getType | `getType()` → `Horde_Form_Type` | `getType()` → `$this` | ⚠️ Different (deprecation) |
| getMessage | N/A | `getMessage(): string` | ➕ New in V3 |

### Type API

| Method | lib/ Signature | V3 Signature | Compatible? |
|--------|----------------|--------------|-------------|
| init | `init(...$params)` | `init(...$params)` | ✅ Yes |
| isValid | `isValid($var, $vars, $value, $message)` | `isValid($vars, $value): bool` | ❌ **NO** (parameters) |
| getInfo | `getInfo($vars, $var, &$info)` | `getInfoV3($vars): mixed` | ❌ **NO** (signature) |
| about | `about()` → array | `about(): array` | ✅ Yes (type hint) |

**Legend:**
- ✅ Compatible (works with type hints added)
- ⚠️ Different (works but behavior differs)
- ❌ **NO** (breaking change, incompatible)
- ➕ New (V3 addition)

---

## Real-World Examples

### Example 1: Simple Form (Both APIs)

**lib/ version:**
```php
<?php
require_once 'Horde/Autoloader.php';

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, 'Contact Form');

$form->addVariable('Your Name', 'name', 'text', true);
$form->addVariable('Email', 'email', 'email', true);
$form->addVariable('Message', 'message', 'longtext', true);

if ($form->validate()) {
    $form->getInfo($vars, $info);
    // Process $info
    sendEmail($info['name'], $info['email'], $info['message']);
}

$renderer = $form->getRenderer();
$renderer->render();
```

**V3 version (when BaseForm ready):**
```php
<?php
use Horde\Form\V3\BaseForm;

$vars = Horde_Variables::getDefaultVariables();
$form = new BaseForm($vars, 'Contact Form');

$form->addVariable('Your Name', 'name', 'text', true);
$form->addVariable('Email', 'email', 'email', true);
$form->addVariable('Message', 'message', 'longtext', true);

if ($form->validate()) {
    $info = $form->getInfo($vars);  // Returns array!
    sendEmail($info['name'], $info['email'], $info['message']);
}

// Renderer not available in V3 yet
// Manual rendering or wait for V3 Renderer
```

**Key differences:**
1. Import statement: `use Horde\Form\V3\BaseForm`
2. getInfo() returns array: `$info = $form->getInfo($vars)`
3. No Renderer in V3 yet

### Example 2: Form with Actions (lib/ only)

```php
<?php
// V3 doesn't have Actions yet - use lib/

$form = new Horde_Form($vars, 'Dynamic Form');

// Category dropdown
$categoryVar = $form->addVariable(
    'Category',
    'category',
    'enum',
    true,
    false,
    null,
    [['tech' => 'Technology', 'science' => 'Science']]
);

// Reload form when category changes
$categoryVar->setAction(Horde_Form_Action::factory('reload'));

// Conditional field (shown based on category)
if ($vars->get('category') == 'tech') {
    $form->addVariable('Technology Type', 'tech_type', 'enum', true, false, null, [
        ['web' => 'Web', 'mobile' => 'Mobile']
    ]);
}

if ($form->validate()) {
    $form->getInfo($vars, $info);
    processForm($info);
}
```

**Cannot do in V3 yet** - Actions not ported.

### Example 3: Custom Variable Type

**lib/ custom type:**
```php
<?php
// lib/CustomApp/Form/Type/zipcode.php

class CustomApp_Form_Type_zipcode extends Horde_Form_Type
{
    public function init($country = 'US')
    {
        $this->_country = $country;
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value)) {
            $this->message = 'ZIP code is required';
            return false;
        }

        if ($this->_country == 'US' && !preg_match('/^\d{5}(-\d{4})?$/', $value)) {
            $this->message = 'Invalid US ZIP code';
            return false;
        }

        return true;
    }

    public function about()
    {
        return [
            'name' => 'ZIP Code',
            'params' => [
                'country' => ['label' => 'Country', 'type' => 'text']
            ]
        ];
    }
}

// Usage
$form->addVariable('ZIP Code', 'zipcode', 'customapp:zipcode', true, false, null, ['US']);
```

**V3 custom type (when BaseForm ready):**
```php
<?php
// src/V3/ZipcodeVariable.php
namespace CustomApp\Form\V3;

use Horde\Form\V3\BaseVariable;
use Horde_Variables;

class ZipcodeVariable extends BaseVariable
{
    public $_country;

    public function init(...$params)
    {
        $this->_country = $params[0] ?? 'US';
    }

    protected function isValid(Horde_Variables $vars, $value): bool
    {
        if ($this->isRequired() && empty($value)) {
            return $this->invalid('ZIP code is required');
        }

        if ($this->_country == 'US' && !preg_match('/^\d{5}(-\d{4})?$/', $value)) {
            return $this->invalid('Invalid US ZIP code');
        }

        return true;
    }

    public function about(): array
    {
        return [
            'name' => 'ZIP Code',
            'params' => [
                'country' => ['label' => 'Country', 'type' => 'text']
            ]
        ];
    }
}

// Usage
$form->addVariable('ZIP Code', 'zipcode', 'customapp:zipcode', true, false, null, ['US']);
```

**Key differences:**
1. Namespace: `CustomApp\Form\V3`
2. Extends: `BaseVariable` (not separate Type)
3. Signature: `isValid($vars, $value): bool` (typed)
4. Error: `$this->invalid('message')` (not $message reference)
5. Return type: `about(): array` (typed)

---

## FAQ

### Q: When will V3 be complete?

**A**: Unknown. BaseForm, Actions, and Renderer need implementation. Estimated 4-6 weeks of development work. No committed timeline.

### Q: Will lib/ be removed?

**A**: Not in Horde 6. lib/ will be maintained through H6 lifecycle. May be deprecated in Horde 7+ once V3 is complete and stable.

### Q: Can I start using V3 now?

**A**: Only for experimental/testing. BaseForm is not implemented yet. Wait for 3.0.0 stable release.

### Q: How do I test my app with both lib/ and V3?

**A**: You can't mix them in the same form. You'd need to maintain two versions of each form and test separately.

### Q: What if I have a custom Renderer?

**A**: Use lib/. V3 Renderer not started yet. Custom renderers will need to be rewritten for V3 when available.

### Q: Will my lib/ code break in PHP 9?

**A**: Maybe. Reference passing and non-static methods called statically may cause issues. Plan to migrate to V3 before PHP 9.

### Q: Where can I see V3 examples?

**A**: Test files in `test/v3/` show V3 usage patterns. Real apps will need to wait for BaseForm implementation.

### Q: How do I report V3 bugs?

**A**: GitHub issues at https://github.com/horde/Form/issues - mark with "V3" label.

### Q: Can I contribute to V3?

**A**: Yes! BaseForm implementation is straightforward. See `horde-development/horde-form-v3-completeness-analysis.md` for implementation guide.

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 3.0.0-beta3 | 2025-07-05 | V3 Variables complete, BaseForm stub |
| 3.0.0-beta2 | 2025-07-02 | V3 type initializer fixes |
| 3.0.0-beta1 | 2025-07-02 | Initial V3 namespace |
| 3.0.0-alpha8 | 2025-06-22 | V3 design start |
| 2.0.19 | 2019-01-06 | Last stable lib/ release |

---

## Summary

**Use lib/** for:
- ✅ Existing/maintenance work
- ✅ Production applications
- ✅ Forms with Actions
- ✅ Forms with custom Renderers
- ✅ Stability required

**Use V3** for:
- ✅ New greenfield apps (when BaseForm ready)
- ✅ Future-proof code
- ✅ Modern PHP patterns
- ⏳ Wait for 3.0.0 stable release

**Cannot mix** lib/ and V3 in same form - choose one or the other.

**Migration** will be all-or-nothing per form when V3 is complete.

---

## Additional Resources

- **V3 Completeness Analysis**: `horde-development/horde-form-v3-completeness-analysis.md`
- **V3 Test Coverage**: `horde-development/horde-form-v3-test-coverage.md`
- **V3 vs lib/ Comparison**: `horde-development/horde-form-v3-analysis.md`
- **GitHub Issues**: https://github.com/horde/Form/issues
- **Horde Documentation**: https://www.horde.org/libraries/Horde_Form

---

**Document Status**: Complete, ready for review
**Next Steps**: Implement BaseForm (issue #19), update this doc when complete
