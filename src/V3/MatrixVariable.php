<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * MatrixVariable type for field matrix input.
 *
 * @property array $cols A list of column headers
 * @property array $rows A hash with row IDs as the keys and row labels as the values
 * @property array $matrix A two dimensional hash with the field values
 * @property bool|array $new_input If true, a free text field to add a new row is displayed on the top, a select box if this parameter is a value
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_matrix PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class MatrixVariable extends BaseVariable
{
    public $_cols;
    public $_rows;
    public $_matrix;
    public $_new_input;

    /**
     * Initialize a field matrix.
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
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $cols - A list of column headers (default: [])
     *                      - $params[1]: array $rows - A hash with row IDs as the keys and row labels as the values (default: [])
     *                      - $params[2]: array $matrix - A two dimensional hash with the field values (default: [])
     *                      - $params[3]: bool|array $new_input - If true, a free text field to add a new row is displayed on the top, a select box if this parameter is a value (default: false)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_cols       = $params[0] ?? [];
        $this->_rows       = $params[1] ?? [];
        $this->_matrix     = $params[2] ?? [];
        $this->_new_input  = $params[3] ?? false;
    }

    public function isValid(Horde_Variables $vars, $value): bool
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

    /**
     * Return info about field type.
      *
      * @api
     */
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
