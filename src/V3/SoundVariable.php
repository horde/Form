<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;
use Horde_Themes;

/**
 * SoundVariable type for sound selection fields.
 *
 * @property array $sounds Available sounds for selection

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_sound PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class SoundVariable extends BaseVariable
{
    public $_sounds = [];

    /**
     * Initialize a sound selection field.
     *
     * @param array $params Variable arguments (none used, sounds are loaded from theme)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_sounds = array_keys(Horde_Themes::soundList());
    }

    public function getSounds()
    {
        return $this->_sounds;
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->isRequired() && empty($value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || in_array($value, $this->_sounds)) {
            return true;
        }

        return $this->invalid('Please choose a sound.');
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Sound selection") ];
    }
}
