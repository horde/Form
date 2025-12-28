<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class SetVariable extends BaseVariable
{
    public $_values;
    public $_checkAll = false;

    /**
     * Initialize a Set form type
     *
     * function init($values, $checkAll = false)
     */
    public function init(...$params)
    {
        $this->_values = $params[0];
        $this->_checkAll = $params[1] ?? false;
    }

    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        if ((!is_null($this->_values) && count($this->_values) == 0) || is_null($value) || count($value) == 0) {
            return true;
        }

        foreach ($value as $item) {
            if (!isset($this->_values[$item])) {
                return $this->invalid('Invalid data submitted.');
            }
        }

        return true;

    }

    public function getValues(...$params)
    {
        return $this->_values;
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Set"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'
                ],
                'checkAll' => [
                    'label' => Horde_Form_Translation::t("Check all"),
                    'type'  => 'boolean'
                ]
            ]
        ];
    }

}
