<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class PhoneType extends BaseType
{
    /**
     * The size of the input field.
     *
     * @var integer
     */
    public $_size;

    /**
     * @param integer $size  The size of the input field.
     */
    public function init(...$params)
    {
        $this->_size = $params[0] ?? 15;
    }

    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        if (!strlen(trim($value))) {
            if ($var->isRequired()) {
                $message = Horde_Form_Translation::t("This field is required.");
                $this->message = $message;
                return false;
            }
        } elseif (!preg_match('/^\+?[\d()\-\/.\s]*$/u', $value)) {
            $message = Horde_Form_Translation::t("You must enter a valid phone number, digits only with an optional '+' for the international dialing prefix.");
            $this->message = $message;
            return false;
        }

        return true;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getChars()
    {
        return $this->_chars;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return [
            'name' => Horde_Form_Translation::t("Phone number"),
            'params' => [
                'size'      => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
            ],
        ];
    }
}