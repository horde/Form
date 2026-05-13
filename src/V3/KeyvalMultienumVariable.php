<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * KeyvalMultienumVariable type for multiple selection, preserving keys.
 *
 * @property array $values A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
 * @property string|bool $prompt A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.
 * @property int $size The number of rows the multienum should display before scrolling

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_keyval_multienum PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class KeyvalMultienumVariable extends MultienumVariable
{
    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $value = $vars->get($this->getVarName());
        $info = [];
        foreach ($value as $key) {
            $info[$key] = $this->_values[$key];
        }
        return $info;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        $about = parent::about();
        $about['name'] = Horde_Form_Translation::t("Multiple selection, preserving keys");
        return $about;
    }
}
