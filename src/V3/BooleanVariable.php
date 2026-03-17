<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_String;
use Horde_Form_Translation;

/**
 * BooleanVariable type for true or false values.
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_boolean PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class BooleanVariable extends BaseVariable
{
    /**
     * Validates boolean field value.
     *
     * Boolean fields always validate successfully as they can only be
     * checked or unchecked (on or off). No validation errors are possible.
     *
     * @param Horde_Variables $vars  Form variables
     * @param mixed $value           Field value to validate
     *
     * @return bool  Always returns true
      *
      * @api
     */
    public function isValid(Horde_Variables $vars, $value): bool
    {
        return true;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        return Horde_String::lower($vars->get($this->getVarName())) == 'on';
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("True or false") ];
    }
}
