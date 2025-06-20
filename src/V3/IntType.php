<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class IntType extends BaseType
{
    public function isValid($var, Horde_Variables|array $vars, $value): bool
    {
        if ($var->isRequired() && empty($value) && ((string) (int) $value !== $value)) {
            $this->message = Horde_Form_Translation::t("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-9]+$/', $value)) {
            return true;
        }

        $this->message = Horde_Form_Translation::t("This field may only contain integers.");
        return false;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return ['name' => Horde_Form_Translation::t("Integer")];
    }
}
