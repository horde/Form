<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class MatrixVariable extends BaseVariable
{
    public $_cols;
    public $_rows;
    public $_matrix;
    public $_new_input;

    /**
     * Initializes the variable.
     *
     * Example:
     * <code>
     * init(array('Column A', 'Column B'),
     *      array(1 => 'Row One', 2 => 'Row 2', 3 => 'Row 3'),
     *      array(array(true, true, false),
     *            array(true, false, true),
     *            array(fasle, true, false)),
     *      array('Row 4', 'Row 5'));
     * </code>
     * function init($cols, $rows = array(), $matrix = array(), $new_input = false)
     *
     * @param array $cols               A list of column headers.
     * @param array $rows               A hash with row IDs as the keys and row
     *                                  labels as the values.
     * @param array $matrix             A two dimensional hash with the field
     *                                  values.
     * @param bool|array $new_input  If true, a free text field to add a new
     *                                  row is displayed on the top, a select
     *                                  box if this parameter is a value.
     */
    public function init(...$params)
    {
        $this->_cols       = $params[0] ?? [];
        $this->_rows       = $params[1] ?? [];
        $this->_matrix     = $params[2] ?? [];
        $this->_new_input  = $params[3] ?? false;
    }

    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        return true;
    }

    public function getCols()
    {
        return $this->_cols;
    }
    public function getRows()
    {
        return $this->_rows;
    }
    public function getMatrix()
    {
        return $this->_matrix;
    }
    public function getNewInput()
    {
        return $this->_new_input;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $values = $vars->get($this->getVarName());
        if (!empty($values['n']['r']) && isset($values['n']['v'])) {
            $new_row = $values['n']['r'];
            $values['r'][$new_row] = $values['n']['v'];
            unset($values['n']);
        }

        return $values['r'] ?? [];
    }

    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Field matrix"),
            'params' => [
                'cols' => [
                    'label' => Horde_Form_Translation::t("Column titles"),
                    'type'  => 'stringarray'
                ],
                'rows' => [
                    'label' => Horde_Form_Translation::t("Row titles"),
                    'type'  => 'stringarray'
                ],
                'matrix' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'
                ],
                'new_input' => [
                    'label' => Horde_Form_Translation::t("New Input"),
                    'type'  => 'boolean'
                ]
            ]
        ];
    }

}
