<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * CaptchaVariable type for image CAPTCHA fields.
 *
 * @property string $text The CAPTCHA text
 * @property string $font The font to use

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_captcha PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class CaptchaVariable extends FigletVariable
{
    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Image CAPTCHA"),
            'params' => [
                'text' => [
                    'label' => Horde_Form_Translation::t("Text"),
                    'type'  => 'text',
                ],
                'font' => [
                    'label' => Horde_Form_Translation::t("Font"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
