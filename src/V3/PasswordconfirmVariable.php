<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * PasswordconfirmVariable type for password input with confirmation field.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_passwordconfirm PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class PasswordconfirmVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->isRequired() && empty($value['original'])) {
            return $this->invalid('This field is required.');
        }

        if ($value['original'] != $value['confirm']) {
            return $this->invalid('Passwords must match.');
        }

        return true;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $value = $vars->get($this->getVarName());
        return $value['original'];
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Password with confirmation") ];
    }
}
