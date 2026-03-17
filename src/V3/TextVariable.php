<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_String;
use Horde_Form_Translation;

/**
 * TextVariable type for text input fields.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_text PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class TextVariable extends BaseVariable
{
    public $_regex;
    public $_size;
    public $_maxlength;

    /**
     * The initialisation function for the text variable type.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string $regex - Any valid PHP PCRE pattern syntax that needs to be matched for the field to be considered valid. If left empty validity will be checked only for required fields whether they are empty or not. If using this regex test it is advisable to enter a description for this field to warn the user what is expected, as the generated error message is quite generic and will not give any indication where the regex failed.
     *                      - $params[1]: int $size - The size of the input field.
     *                      - $params[2]: int|null $maxlength - The max number of characters.
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_regex     = $params[0] ?? '';
        $this->_size      = $params[1] ?? 40;
        $this->_maxlength = $params[2] ?? null;
    }

    public function isValid(Horde_Variables $vars, $value): bool
    {
        if (!empty($this->_maxlength) && Horde_String::length($value) > $this->_maxlength) {
            $this->message = sprintf(Horde_Form_Translation::t("Value is over the maximum length of %d."), $this->_maxlength);
            return false;
        }

        if ($this->isRequired() && empty($this->_regex)) {
            if (strlen(trim((string)$value)) == 0) {
                return $this->invalid('This field is required.');
            }
        } elseif (!empty($this->_regex) && !preg_match($this->_regex, $value)) {
            return $this->invalid('You must enter a valid value.');
        }

        return true;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getMaxLength()
    {
        return $this->_maxlength;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Text"),
            'params' => [
                'regex'     => [
                    'label' => Horde_Form_Translation::t("Regex"),
                    'type'  => 'text'
                ],
                'size'      => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'
                ],
                'maxlength' => [
                    'label' => Horde_Form_Translation::t("Maximum length"),
                    'type'  => 'int'
                ]
            ]
        ];
    }
}
