<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class SorterVariable extends BaseVariable
{
    public $_instance;
    public $_values;
    public $_size;
    public $_header;

    /**
     *     function init($values, $size = 8, $header = '')
     */
    public function init(...$params)
    {
        $values = $params[0];
        $size = $params[1] ?? 8;
        $header = $params[2] ?? '';

        static $horde_sorter_instance = 0;

        /* Get the next progressive instance count for the horde
         * sorter so that multiple sorters can be used on one page. */
        $horde_sorter_instance++;
        $this->_instance = 'horde_sorter_' . $horde_sorter_instance;
        $this->_values = $values;
        $this->_size   = $size;
        $this->_header = $header;
    }

    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        return true;
    }

    public function getValues(...$params)
    {
        return $this->_values;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getHeader()
    {
        if (!empty($this->_header)) {
            return $this->_header;
        }
        return '';
    }

    public function getOptions($keys = null)
    {
        $html = '';
        if ($this->_header) {
            $html .= '<option value="">' . htmlspecialchars($this->_header) . '</option>';
        }

        if (empty($keys)) {
            $keys = array_keys($this->_values);
        } else {
            $keys = explode("\t", $keys['array']);
        }
        foreach ($keys as $sl_key) {
            $html .= '<option value="' . $sl_key . '">' . htmlspecialchars($this->_values[$sl_key]) . '</option>';
        }

        return $html;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $value = $vars->get($this->getVarName());
        return explode("\t", $value['array']);
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Sort order selection"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'
                ],
                'size'   => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'
                ],
                'header' => [
                    'label' => Horde_Form_Translation::t("Header"),
                    'type'  => 'text'
                ]
            ]
        ];
    }

}
