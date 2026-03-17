<?php

/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Form
 */

/**
 * Horde_Form_Action_submit is a Horde_Form Action that submits the
 * form after the form element that the action is attached to is
 * modified.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Form
 
 *
 * @see Horde\Form\V3\SubmitAction PSR-4 equivalent in src/V3/SubmitAction.php
 */
class Horde_Form_Action_submit extends Horde_Form_Action
{
    public $_trigger = ['onchange'];

    public function getActionScript($form, $renderer, $varname)
    {
        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');
        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $page_output->addScriptFile('redbox.js', 'horde');
        return 'RedBox.loading(); document.' . $form->getName() . '.submit()';
    }

}
