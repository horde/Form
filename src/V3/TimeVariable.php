<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * TimeVariable type for time input fields.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_time PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class TimeVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->isRequired() && empty($value) && ((string) (float) $value !== $value)) {
            return $this->invalid('This field is required.');
        }
        if (empty($value) || preg_match('/^[0-2]?[0-9]:[0-5][0-9]$/', $value)) {
            return true;
        }
        return $this->invalid('This field may only contain numbers and the colon.');
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Time") ];
    }
}
