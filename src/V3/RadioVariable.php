<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * RadioVariable type for radio button selection.
 *
 * @property array $values A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
 * @property string|bool $prompt A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_radio PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class RadioVariable extends EnumVariable
{
    /* Entirely implemented by Horde_Form_Type_enum; just a different
     * view. */

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Radio selection"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
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
