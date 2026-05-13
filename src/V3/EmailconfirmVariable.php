<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * EmailconfirmVariable type for email input with confirmation field.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_emailConfirm PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class EmailconfirmVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->isRequired() && empty($value['original'])) {
            return $this->invalid('This field is required.');
        }

        if ($value['original'] != $value['confirm']) {
            return $this->invalid('Email addresses must match.');
        }

        $addr_ob = $GLOBALS['injector']->getInstance('Horde_Mail_Rfc822')->parseAddressList($value['original']);
        switch (count($addr_ob)) {
            case 0:
                return $this->invalid('You did not enter a valid email address.');

            case 1:
                return true;

            default:
                return $this->invalid('Only one email address allowed.');
        }
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Email with confirmation") ];
    }
}
