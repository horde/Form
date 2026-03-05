<?php
declare(strict_types=1);

/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Matt Kynaston <matt@kynx.org>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

/**
 * SumFieldsAction sets the target field to the sum of one or more other
 * numeric fields.
 *
 * The params array should contain the names of the fields which will be summed.
 *
 * Example: Calculate total from price and tax fields:
 * ```php
 * $action = new SumFieldsAction(['price', 'tax']);
 * $totalVar->setAction($action);
 * ```
 *
 * The 'total' field will be automatically updated as 'price' or 'tax' changes.
 *
 * @author    Matt Kynaston <matt@kynx.org>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class SumfieldsAction extends BaseAction
{
    /**
     * This action triggers on page load.
     *
     * @var array<string>
     */
    protected ?array $trigger = ['onload'];

    /**
     * Get JavaScript code for sum fields behavior.
     *
     * @param \Horde\Form\Form $form  The form instance
     * @param mixed $renderer  The form renderer
     * @param string $varname  Variable name (the target sum field)
     * @return string  JavaScript code
     */
    public function getActionScript(\Horde\Form\Form $form, $renderer, string $varname): string
    {
        // TODO: Inject Horde_PageOutput or use modern asset management
        // For now, assume form_helpers.js is loaded
        // $GLOBALS['injector']->getInstance('Horde_PageOutput')->addScriptFile('form_helpers.js', 'horde');

        $formName = $form->getName();
        $fields = "'" . implode("','", $this->params) . "'";
        $js = [];

        // Disable the sum field (it's calculated, not user-editable)
        $js[] = sprintf(
            "document.forms['%s'].elements['%s'].disabled = true;",
            $formName,
            $varname
        );

        // Add onchange listener to each source field
        foreach ($this->params as $field) {
            $js[] = sprintf(
                "addEvent(document.forms['%1\$s'].elements['%2\$s'], \"onchange\", \"sumFields(document.forms['%1\$s'], '%3\$s', %4\$s);\");",
                $formName,
                $field,
                $varname,
                $fields
            );
        }

        return implode("\n", $js);
    }
}
