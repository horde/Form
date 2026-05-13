<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * SetVariable type for checkbox set fields.
 *
 * @property array $values Values available for selection
 * @property bool $checkAll Show "check all" option

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_set PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class SetVariable extends BaseVariable
{
    public $_values;
    public $_checkAll = false;

    /**
     * Initialize a set field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $values - Values available for selection
     *                      - $params[1]: bool $checkAll - Show "check all" option (default: false)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_values = $params[0];
        $this->_checkAll = $params[1] ?? false;
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
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

    public function getValues(...$params): ?array
    {
        return $this->_values;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Set"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray',
                ],
                'checkAll' => [
                    'label' => Horde_Form_Translation::t("Check all"),
                    'type'  => 'boolean',
                ],
            ],
        ];
    }
}
