<?php

declare(strict_types=1);

/**
 * Copyright 2001-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

/**
 * Factory for creating Variable instances from type strings.
 *
 * Shared by BaseForm and FieldGroup so both can create variables
 * using the same type-string-to-class mapping.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */
trait VariableFactoryTrait
{
    /**
     * Create a Variable instance from a type string.
     *
     * The $type argument accepts three shapes, tried in order:
     *   1. A fully-qualified class name of a Variable subclass (preferred).
     *      Any string containing a namespace separator is treated as an FQCN
     *      and used as-is; the class must be autoloadable.
     *   2. 'app:typename' — resolved to Horde\{App}\Form\V3\{Type}Variable
     *      first (matches the Composer PSR-4 map every modern Horde package
     *      publishes), then to {App}\Form\V3\{Type}Variable as a BC fallback
     *      for packages that shipped variables under that root before this
     *      dispatch was fixed.
     *   3. bare 'typename' — resolved to Horde\Form\V3\{Type}Variable.
     *
     * When no candidate class is found, InvalidVariable is used so form
     * rendering can continue.
     *
     * @param string $humanName  Human-readable field label
     * @param string $varName  Internal variable name
     * @param string $type  Variable type string (FQCN, 'app:type', or bare type)
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
        if (str_contains($type, '\\')) {
            // FQCN shape.
            $candidates = [ltrim($type, '\\')];
        } elseif (str_contains($type, ':')) {
            // 'app:typename' shape — vendor-namespaced first, then the
            // historical unprefixed root for BC.
            [$app, $typeName] = explode(':', $type, 2);
            $app = ucfirst($app);
            $typeName = ucfirst($typeName);
            $candidates = [
                'Horde\\' . $app . '\\Form\\V3\\' . $typeName . 'Variable',
                $app . '\\Form\\V3\\' . $typeName . 'Variable',
            ];
        } else {
            // Bare Horde type.
            $candidates = ['Horde\\Form\\V3\\' . ucfirst($type) . 'Variable'];
        }

        $class = null;
        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                $class = $candidate;
                break;
            }
        }

        if ($class === null) {
            error_log(
                "Warning: Form type class for '$type' not found (tried: "
                . implode(', ', $candidates)
                . '); using InvalidVariable'
            );
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
}
