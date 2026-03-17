<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * EnumVariable type for selecting a single value out of a list.
 * For selecting multiple values, use Horde_Form_Type_multienum.
 *
 * @property array $values A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
 * @property string|bool $prompt A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_enum PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class EnumVariable extends BaseVariable
{
    public $_values;
    public $_prompt;

    /**
     * Initialize (kind of constructor)
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $values - A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
     *                      - $params[1]: string|bool $prompt - A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.
      *
      * @api
     */
    public function init(...$params)
    {
        $this->setValues($params[0] ?? []);
        $prompt = $params[1] ?? false;

        if ($prompt === true) {
            $this->_prompt = Horde_Form_Translation::t("-- select --");
        } else {
            $this->_prompt = $prompt;
        }
    }

    public function isValid(Horde_Variables $vars, $value): bool
    {
        if ($this->isRequired() && $value == '' && !isset($this->_values[$value])) {
            return $this->invalid('This field is required.');
        }
        if (count($this->_values) == 0 || isset($this->_values[$value]) ||
            ($this->_prompt && empty($value))) {
            return true;
        }
        return $this->invalid('Invalid data submitted.');
    }

    public function getValues(...$params): ?array
    {
        return $this->_values;
    }

    public function setValues($values)
    {
        $this->_values = $values;
    }

    public function getPrompt()
    {
        return $this->_prompt;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Drop down list"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values to select from"),
                    'type'  => 'stringarray'
                ],
                'prompt' => [
                    'label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text'
                ]
            ]
        ];
    }
}
