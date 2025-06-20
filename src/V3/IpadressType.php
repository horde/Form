<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class IpadressType extends TextType
{
    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        $valid = true;

        if (strlen(trim($value)) > 0) {
            $ip = explode('.', $value);
            $valid = (count($ip) == 4);
            if ($valid) {
                foreach ($ip as $part) {
                    if (!is_numeric($part) ||
                        $part > 255 ||
                        $part < 0) {
                        $valid = false;
                        break;
                    }
                }
            }

            if (!$valid) {
                $message = Horde_Form_Translation::t("Please enter a valid IP address.");
            }
        } elseif ($var->isRequired()) {
            $valid = false;
            $message = Horde_Form_Translation::t("This field is required.");
            $this->message = $message;
        }

        return $valid;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return ['name' => Horde_Form_Translation::t("IP address")];
    }

}