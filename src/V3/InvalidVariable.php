<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * InvalidVariable type for fields that should always be invalid with a custom message.
 *
 * @property string $message The error message to display

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_invalid PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class InvalidVariable extends BaseVariable
{
    /**
     * Initialize an invalid message field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string $message - The error message to display (default: '')
      *
      * @api
     */
    public function init(...$params)
    {
        $this->message = $params[0] ?? '';
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        return false;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Invalid"),
            'params' => [
                'message' => [
                    'label' => Horde_Form_Translation::t("Text"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
