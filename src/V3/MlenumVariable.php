<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * MlenumVariable type for multi-level dropdown list fields.
 *
 * @property array $values Values to select from
 * @property array $prompts Prompt texts for the dropdowns

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_mlenum PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class MlenumVariable extends BaseVariable
{
    public $_values;
    public $_prompts;

    /**
     * Initialize a multi-level enum field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $values - Values to select from (passed by reference)
     *                      - $params[1]: bool|string|array|null $prompts - Prompt text. If true, uses default "-- select --" for both levels. If string, uses same prompt for both levels. If array, uses different prompts for each level. (default: null)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_values = &$params[0];
        $prompts = $params[1] ?? null;

        if ($prompts === true) {
            $this->_prompts = [ Horde_Form_Translation::t("-- select --"), Horde_Form_Translation::t("-- select --") ];
        } elseif (!is_array($prompts)) {
            $this->_prompts = [ $prompts, $prompts ];
        } else {
            $this->_prompts = $prompts;
        }
    }

    public function onSubmit($vars)
    {
        $varname = $this->getVarName();
        $value = $vars->get($varname);
        if ($value['1'] != $value['old']) {
            $this->form->setSubmitted(false);
        }
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->isRequired() && (empty($value['1']) || empty($value['2']))) {
            return $this->invalid('This field is required.');
        }

        if (!count($this->_values) || isset($this->_values[$value['1']])
            || (!empty($this->_prompts) && empty($value['1']))) {
            return true;
        }

        return $this->invalid('Invalid data submitted.');
    }

    public function getValues(...$params): ?array
    {
        return $this->_values;
    }

    public function getPrompts()
    {
        return $this->_prompts;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $info = $vars->get($this->getVarName());
        return $info['2'];
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Multi-level drop down lists"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values to select from"),
                    'type'  => 'stringarray',
                ],
                'prompts' => [
                    'label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
