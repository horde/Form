<?php
namespace Horde\Form\V3;
use Horde_Form_Translation;
class HtmlType extends BaseType
{
    public $_values;
    public $_header;

    /**
     *     function init($values, $header)
     */
    public function init(...$params)
    {
        $this->_values = $params[0];
        $this->_header = $params[1];
    }

    public function isValid($var, Horde_Variables|array $vars, $value): bool
    {
        if (count($this->_values) == 0 || count($value) == 0) {
            return true;
        }
        foreach ($value as $item) {
            if (!isset($this->_values[$item])) {
                $error = true;
                break;
            }
        }
        if (!isset($error)) {
            return true;
        }

        $message = Horde_Form_Translation::t("Invalid data submitted.");
        $this->message = $message;
        return false;
    }

    public function getHeader()
    {
        return $this->_header;
    }

    public function getValues()
    {
        return $this->_values;
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Table Set"),
            'params' => [
                'values' => ['label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringlist'],
                'header' => ['label' => Horde_Form_Translation::t("Headers"),
                    'type'  => 'stringlist']],
        ];
    }
}
