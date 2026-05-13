<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;
use Horde_Nls;

/**
 * CountryVariable type for country selection dropdown.
 *
 * @property array $values A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
 * @property string|bool $prompt A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_country PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class CountryVariable extends EnumVariable
{
    /**
     * Initialize a country field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string|bool|null $prompt - Prompt text for selection
      *
      * @api
     */
    public function init(...$params)
    {
        $prompt = $params[0] ?? null;

        parent::init(Horde_Nls::getCountryISO(), $prompt);
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Country drop down list"),
            'params' => [
                'prompt' => [
                    'label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
