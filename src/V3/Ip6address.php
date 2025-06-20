<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class Ip6adressType extends TextType
{
    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        $valid = true;

        if (strlen(trim($value)) > 0) {
            $valid = @inet_pton($value);

            if ($valid === false) {
                $message = Horde_Form_Translation::t("Please enter a valid IP address.");
                $this->message = $message;

            }
        } elseif ($var->isRequired()) {
            $valid = false;
            $message = Horde_Form_Translation::t("This field is required.");
            $this->message = $message;
        }
        // Looks like a bug. Shouldn't we return $valid here?
        return true;
     }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return ['name' => Horde_Form_Translation::t("IPv6 address")];
    }

}