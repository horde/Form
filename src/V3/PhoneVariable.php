<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * PhoneVariable type for phone number input fields.
 *
 * @property int $size The size of the input field

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_phone PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class PhoneVariable extends BaseVariable
{
    /**
     * The size of the input field.
     *
     * @var integer
     */
    public $_size;

    /**
     * Initialize a phone number field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: int $size - The size of the input field (default: 15)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_size = $params[0] ?? 15;
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (!strlen(trim((string) $value))) {
            if ($this->isRequired()) {
                return $this->invalid('This field is required.');
            }
        } elseif (!preg_match('/^\+?[\d()\-\/.\s]*$/u', $value)) {
            return $this->invalid("You must enter a valid phone number, digits only with an optional '+' for the international dialing prefix.");
        }

        return true;
    }

    public function getSize()
    {
        return $this->_size;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Phone number"),
            'params' => [
                'size' => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
