<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class AddresslinkType extends AddressType
{
    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return ['name' => Horde_Form_Translation::t("Address Link")];
    }

}