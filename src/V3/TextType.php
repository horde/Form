<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class TextType extends BaseType
{
    public $_regex;
    public $_size;
    public $_maxlength;

    /**
     * The initialisation function for the text variable type.
     *
     * function init($regex = '', $size = 40, $maxlength = null)
     *
     * @access private
     *
     * @param string $regex       Any valid PHP PCRE pattern syntax that
     *                            needs to be matched for the field to be
     *                            considered valid. If left empty validity
     *                            will be checked only for required fields
     *                            whether they are empty or not.
     *                            If using this regex test it is advisable
     *                            to enter a description for this field to
     *                            warn the user what is expected, as the
     *                            generated error message is quite generic
     *                            and will not give any indication where
     *                            the regex failed.
     * @param int     $size       The size of the input field.
     * @param int     $maxlength  The max number of characters.
     */
    public function init(...$params)
    {
        $this->_regex     = $params[0] ?? '';
        $this->_size      = $params[1] ?? 40;
        $this->_maxlength = $params[2] ?? null;
    }

    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        $valid = true;

        if (!empty($this->_maxlength) && Horde_String::length($value) > $this->_maxlength) {
            $valid = false;
            $message = sprintf(Horde_Form_Translation::t("Value is over the maximum length of %d."), $this->_maxlength);
            $this->message = $message;
        } elseif ($var->isRequired() && empty($this->_regex)) {
            $valid = strlen(trim($value)) > 0;

            if (!$valid) {
                $message = Horde_Form_Translation::t("This field is required.");
                $this->message = $message;
            }
        } elseif (!empty($this->_regex)) {
            $valid = preg_match($this->_regex, $value);

            if (!$valid) {
                $message = Horde_Form_Translation::t("You must enter a valid value.");
                $this->message = $message;
            }
        }

        return $valid;

    }
    public function getSize()
    {
        return $this->_size;
    }

    public function getMaxLength()
    {
        return $this->_maxlength;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
         return [
            'name' => Horde_Form_Translation::t("Text"),
            'params' => [
                'regex'     => ['label' => Horde_Form_Translation::t("Regex"),
                    'type'  => 'text'],
                'size'      => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
                'maxlength' => ['label' => Horde_Form_Translation::t("Maximum length"),
                    'type'  => 'int']]];
   }

}