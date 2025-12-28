<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class MlenumVariable extends BaseVariable
{
    public $_values;
    public $_prompts;

    /**
     * Initialize an mlenum field
     *
     * function init($values, $prompts = null)
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

    /**
     *     function onSubmit($vars)
     */
    public function onSubmit($vars)
    {
        $varname = $this->getVarName();
        $value = $vars->get($varname);

        if ($value['1'] != $value['old']) {
            $this->form->setSubmitted(false);
        }
    }

    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        if ($this->isRequired() && (empty($value['1']) || empty($value['2']))) {
            return $this->invalid('This field is required.');
        }

        if (!count($this->_values) || isset($this->_values[$value['1']]) ||
            (!empty($this->_prompts) && empty($value['1']))) {
            return true;
        }

        return $this->invalid('Invalid data submitted.');
    }

    public function getValues(...$params)
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
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Multi-level drop down lists"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values to select from"),
                    'type'  => 'stringarray'
                ],
                'prompts' => [
                    'label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text'
                ]
            ]
        ];
    }

}
