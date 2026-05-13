<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * PasswordVariable type for password input fields.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_password PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class PasswordVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->isRequired() && strlen(trim((string) $value)) == 0) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Password") ];
    }
}
