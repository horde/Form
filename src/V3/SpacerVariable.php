<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * SpacerVariable type for visual spacing in forms.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_spacer PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class SpacerVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        return true;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Spacer"),
        ];
    }
}
