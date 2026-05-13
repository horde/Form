<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * IntlistVariable type for comma or space separated integer list fields.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_intlist PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class IntlistVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (empty($value) && $this->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || preg_match('/^[0-9 ,]+$/', $value)) {
            return true;
        }

        return $this->invalid('This field must be a comma or space separated list of integers');
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Integer list") ];
    }
}
