<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * MultienumVariable type for selecting multiple values from a list.
 *
 * @property array $values A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
 * @property string|bool $prompt A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.
 * @property int $size The number of rows the multienum should display before scrolling

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_multienum PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class MultienumVariable extends EnumVariable
{
    public $_size = 5;

    /**
     * Initialize a multiple selection enum field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $values - A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
     *                      - $params[1]: int|null $size - The number of rows the multienum should display before scrolling (optional)
      *
      * @api
     */
    public function init(...$params)
    {
        $values = $params[0] ?? [];
        $size = $params[1] ?? null;

        if (!is_null($size)) {
            $this->_size = (int) $size;
        }

        parent::init($values);
    }

    /**
     * Validates multiple selection field value.
     *
     * Accepts arrays (multiple selections) or single values. Each selected
     * value must exist in the configured values list. Required fields must
     * have at least one selection. Recursively validates array values.
     *
     * @param Horde_Variables $vars  Form variables
     * @param mixed $value           Single value or array of values to validate
     *
     * @return bool  True if all values valid, false with error message if invalid
      *
      * @api
     */
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                if (!$this->isValid($vars, $val)) {
                    return false;
                }
            }
            return true;
        }

        if (empty($value) && ((string) (int) $value !== $value)) {
            if ($this->isRequired()) {
                return $this->invalid('This field is required.');
            }
            return true;
        }

        if (count($this->_values) == 0 || isset($this->_values[$value])) {
            return true;
        }

        return $this->invalid('Invalid data submitted.');
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Multiple selection"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray',
                ],
                'size'   => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
