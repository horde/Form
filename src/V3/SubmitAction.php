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

/**
 * SubmitAction submits the form after the form element that the action is
 * attached to is modified.
 *
 * Example: Auto-submit form when dropdown changes:
 * ```php
 * $action = new SubmitAction();
 * $selectVar->setAction($action);
 * ```
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Action_submit PSR-0 legacy equivalent in lib/Horde/Form/Action/submit.php
 */
class SubmitAction extends BaseAction
{
    /**
     * This action triggers on field change.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onchange'];

    /**
     * Get JavaScript code for submit behavior.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name
     * @return string  JavaScript code
      *
      * @api
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        // TODO: Modern loading indicator instead of RedBox
        // For now, simple submit
        return 'document.' . $form->getName() . '.submit()';
    }
}
