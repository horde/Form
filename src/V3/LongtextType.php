<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class LongtextType extends TextType
{
    public $_rows;
    public $_cols;
    public $_helper = [];
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
     *   Initialize a Longtext field type
     *
     *   @param $rows = $params[0] ?? 8;
     *   @param $cols = $params[1] ?? 80;
     *   @param $helper = $params[2] ?? array();
     */
    public function init(...$params)
    {
        $rows = $params[0] ?? 8;
        $cols = $params[1] ?? 80;
        $helper = $params[2] ?? [];

        if (!is_array($helper)) {
            $helper = [$helper];
        }

        $this->_rows = $rows;
        $this->_cols = $cols;
        $this->_helper = $helper;
    }

    public function getRows()
    {
        return $this->_rows;
    }

    public function getCols()
    {
        return $this->_cols;
    }

    public function hasHelper($option = '')
    {
        if (empty($option)) {
            /* No option specified, check if any helpers have been
             * activated. */
            return !empty($this->_helper);
        } elseif (empty($this->_helper)) {
            /* No helpers activated at all, return false. */
            return false;
        } else {
            /* Check if given helper has been activated. */
            return in_array($option, $this->_helper);
        }
    }


    /**
     * Return info about field type.
     */
    public function about():array
    {
        return [
            'name' => Horde_Form_Translation::t("Long text"),
            'params' => [
                'rows'   => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols'   => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int'],
                'helper' => ['label' => Horde_Form_Translation::t("Helpers"),
                    'type'  => 'stringarray']]];
    }

}