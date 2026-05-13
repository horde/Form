<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * CellphoneVariable type for mobile phone number input fields.
 *
 * @property int $size The size of the input field

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_cellphone PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class CellphoneVariable extends PhoneVariable
{
    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        $about = parent::about();
        $about['name'] = Horde_Form_Translation::t("Mobile phone number");
        return $about;
    }
}
