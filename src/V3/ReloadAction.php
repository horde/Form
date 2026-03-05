<?php
declare(strict_types=1);

/**
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

/**
 * ReloadAction reloads the form with the current (not the original) value
 * after the form element that the action is attached to is modified.
 *
 * Useful for cascading dropdowns where selecting a value in one field should
 * reload the form to populate dependent fields.
 *
 * Example: Reload form when country changes to update state/province list:
 * ```php
 * $action = new ReloadAction();
 * $countryVar->setAction($action);
 * ```
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2003-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class ReloadAction extends BaseAction
{
    /**
     * This action triggers on field change.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onchange'];

    /**
     * Get JavaScript code for reload behavior.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name
     * @return string  JavaScript code
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        // Clear formname to prevent submission detection, then submit
        // TODO: Modern loading indicator
        return 'if (this.value) { document.' . $form->getName() . '.formname.value=\'\'; document.' . $form->getName() . '.submit() }';
    }
}
