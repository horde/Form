<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_String;
use Horde_Form_Translation;

/**
 * FigletVariable type for Figlet CAPTCHA fields.
 *
 * @property string $text The CAPTCHA text
 * @property string $font The Figlet font to use

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_figlet PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class FigletVariable extends BaseVariable
{
    public $_text;
    public $_font;

    /**
     * Initialize a Figlet form type.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string $text - The CAPTCHA text
     *                      - $params[1]: string $font - The Figlet font to use
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_text = $params[0];
        $this->_font = $params[1];
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (empty($value) && $this->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if (Horde_String::lower($value) != Horde_String::lower($this->_text)) {
            return $this->invalid('The text you entered did not match the text on the screen.');
        }

        return true;
    }

    public function getFont()
    {
        return $this->_font;
    }

    public function getText()
    {
        return $this->_text;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Figlet CAPTCHA"),
            'params' => [
                'text' => [
                    'label' => Horde_Form_Translation::t("Text"),
                    'type'  => 'text',
                ],
                'font' => [
                    'label' => Horde_Form_Translation::t("Figlet font"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
