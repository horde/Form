<?php
declare(strict_types=1);
namespace Horde\Form;

use Horde\Form\V3\Variable;

/**
 * Modern interface for a form instance.
 *
 * This interface defines the minimum contract that all Form implementations
 * must fulfill. Implementations may provide additional methods beyond this
 * minimal contract.
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */
interface Form
{
    /**
     * Validate all form variables.
     *
     * @param mixed $vars  Optional variables to validate against.
     *                     If null, uses form's own variables.
     * @return bool  True if validation passed, false otherwise.
     */
    public function validate($vars = null): bool;

    /**
     * Extract form data into array.
     *
     * @param mixed $vars  Optional variables to extract from.
     *                     If null, uses form's own variables.
     * @return array  Associative array of field name => value.
     */
    public function getInfo($vars = null): array;

    /**
     * Add a variable to the form.
     *
     * @param string $humanName    Human-readable field label
     * @param string $varName      Internal variable name
     * @param string $type         Variable type (e.g., 'text', 'email')
     * @param bool $required       Whether field is required
     * @param bool $readonly       Whether field is read-only
     * @param string|null $description  Field description/help text
     * @param array $params        Type-specific parameters
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
    ): Variable;

    /**
     * Get form name.
     *
     * @return string  The form's name identifier
     */
    public function getName(): string;

    /**
     * Get form title.
     *
     * @return string  The form's display title
     */
    public function getTitle(): string;

    /**
     * Get form variables.
     *
     * @return mixed  The form's variable data
     */
    public function getVars();
}