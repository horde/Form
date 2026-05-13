<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_String;
use Horde_Form_Translation;

/**
 * CountedtextVariable type for text input with character counting.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 * @property int $rows The number of rows for the textarea
 * @property int $cols The number of columns for the textarea
 * @property array $helper Array of helper options
 * @property int $chars The maximum number of characters allowed

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_countedtext PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class CountedtextVariable extends LongtextVariable
{
    public $_chars;

    /**
     * Initialize a counted text field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: int $rows - Number of rows (default: 8, from parent)
     *                      - $params[1]: int $cols - Number of columns (default: 80, from parent)
     *                      - $params[2]: int $chars - Maximum number of characters (default: 1000)
      *
      * @api
     */
    public function init(...$params)
    {
        $rows = $params[0] ?? null;
        $cols = $params[1] ?? null;
        $chars = $params[2] ?? 1000;

        parent::init($rows, $cols);
        $this->_chars = $chars;
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        $length = Horde_String::length(trim($value));
        if ($this->isRequired() && $length <= 0) {
            return $this->invalid('This field is required.');
        }

        if ($length > $this->_chars) {
            $this->message = sprintf(Horde_Form_Translation::ngettext("There are too many characters in this field. You have entered %d character; ", "There are too many characters in this field. You have entered %d characters; ", $length), $length)
                . sprintf(Horde_Form_Translation::t("you must enter less than %d."), $this->_chars);
            return false;
        }

        return true;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Counted text"),
            'params' => [
                'rows'  => [
                    'label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int',
                ],
                'cols'  => [
                    'label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int',
                ],
                'chars' => [
                    'label' => Horde_Form_Translation::t("Number of characters"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
