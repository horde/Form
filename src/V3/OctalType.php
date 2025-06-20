<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class OctalType extends BaseType
{
    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        if ($var->isRequired() && empty($value) && ((string) (int) $value !== $value)) {
            $this->message = Horde_Form_Translation::t("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-7]+$/', $value)) {
            return true;
        }

        $this->message = Horde_Form_Translation::t("This field may only contain octal values.");
        return false;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return ['name' => Horde_Form_Translation::t("Octal")];
    }

}