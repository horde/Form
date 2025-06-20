<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class IntlistType extends BaseType
{
    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        if (empty($value) && $var->isRequired()) {
            $message = Horde_Form_Translation::t("This field is required.");
            $this->message = $message;
            return false;
        }

        if (empty($value) || preg_match('/^[0-9 ,]+$/', $value)) {
            return true;
        }

        $message = Horde_Form_Translation::t("This field must be a comma or space separated list of integers");
        $this->message = $message;
        return false;

    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return ['name' => Horde_Form_Translation::t("Integer list")];
    }

}