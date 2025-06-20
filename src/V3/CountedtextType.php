<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class CountedtextType extends LongtextType
{
    public $_chars;

    /**
     * Init a longtext field
     *
     * function init($rows = null, $cols = null, $chars = 1000)
     */
    public function init(...$params)
    {
        $rows = $params[0] ?? null;
        $cols = $params[1] ?? null;
        $chars = $params[2] ?? 1000;
        parent::init($rows, $cols);
        $this->_chars = $chars;
    }

    public function isValid($var, Horde_Variables|array $vars, $value)
    {
        $valid = true;

        $length = Horde_String::length(trim($value));

        if ($var->isRequired() && $length <= 0) {
            $valid = false;
            $message = Horde_Form_Translation::t("This field is required.");

        } elseif ($length > $this->_chars) {
            $valid = false;
            $message = sprintf(Horde_Form_Translation::ngettext("There are too many characters in this field. You have entered %d character; ", "There are too many characters in this field. You have entered %d characters; ", $length), $length)
                . sprintf(Horde_Form_Translation::t("you must enter less than %d."), $this->_chars);
        }

        $this->message = (string)$message;
        return $valid;
    }

    /**
     * Return info about field type.
     */
    public function about():array
    {
        return [
            'name' => Horde_Form_Translation::t("Counted text"),
            'params' => [
                'rows'  => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols'  => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int'],
                'chars' => ['label' => Horde_Form_Translation::t("Number of characters"),
                    'type'  => 'int']]];
    }

}