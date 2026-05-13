<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * AssignVariable type for assignment columns with left/right values.
 *
 * @property array $leftValues Left column values
 * @property array $rightValues Right column values
 * @property string $leftHeader Left column header
 * @property string $rightHeader Right column header
 * @property int $size Number of visible rows
 * @property string $width Width in CSS units

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_assign PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class AssignVariable extends BaseVariable
{
    public $_leftValues;
    public $_rightValues;
    public $_leftHeader;
    public $_rightHeader;
    public $_size;
    public $_width;

    /**
     * Initialize an assignment field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $leftValues - Left column values
     *                      - $params[1]: array $rightValues - Right column values
     *                      - $params[2]: string $leftHeader - Left column header (default: '')
     *                      - $params[3]: string $rightHeader - Right column header (default: '')
     *                      - $params[4]: int $size - Number of visible rows (default: 8)
     *                      - $params[5]: string $width - Width in CSS units (default: '200px')
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_leftValues = $params[0];
        $this->_rightValues = $params[1];
        $this->_leftHeader = $params[2] ?? '';
        $this->_rightHeader = $params[3] ?? '';
        $this->_size = $params[4] ?? 8;
        $this->_width = $params[5] ?? '200px';
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        return true;
    }

    /**
     * Get values for the specified side.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: mixed $side - Which side to get values for (empty/0 = right, truthy = left)
      *
      * @api
     */
    public function getValues(...$params): ?array
    {
        return empty($params[0]) ? $this->_rightValues : $this->_leftValues;
    }

    public function setValues($side, $values)
    {
        if ($side) {
            $this->_rightValues = $values;
        } else {
            $this->_leftValues = $values;
        }
    }

    public function getHeader($side)
    {
        return $side ? $this->_rightHeader : $this->_leftHeader;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getWidth()
    {
        return $this->_width;
    }

    public function getOptions($side, $formname, $varname)
    {
        $html = '';
        $headers = false;
        if ($side) {
            $values = $this->_rightValues;
            if (!empty($this->_rightHeader)) {
                $values = ['' => $this->_rightHeader] + $values;
                $headers = true;
            }
        } else {
            $values = $this->_leftValues;
            if (!empty($this->_leftHeader)) {
                $values = ['' => $this->_leftHeader] + $values;
                $headers = true;
            }
        }

        foreach ($values as $key => $val) {
            $html .= '<option value="' . htmlspecialchars($key) . '"';
            if ($headers) {
                $headers = false;
            } else {
                $html .= ' ondblclick="Horde_Form_Assign.move(\'' . $formname . '\', \'' . $varname . '\', ' . (int) $side . ');"';
            }
            $html .= '>' . htmlspecialchars($val) . '</option>';
        }

        return $html;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $info = [];
        $value = $vars->get($this->getVarName() . '__values');
        if (strpos($value, "\t\t") === false) {
            $left = $value;
            $right = '';
        } else {
            [$left, $right] = explode("\t\t", $value);
        }

        if (empty($left)) {
            $info['left'] = [];
        } else {
            $info['left'] = explode("\t", $left);
        }

        if (empty($right)) {
            $info['right'] = [];
        } else {
            $info['right'] = explode("\t", $right);
        }

        return $info;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Assignment columns"),
            'params' => [
                'leftValues' => [
                    'label' => Horde_Form_Translation::t("Left values"),
                    'type'  => 'stringarray',
                ],
                'rightValues' => [
                    'label' => Horde_Form_Translation::t("Right values"),
                    'type'  => 'stringarray',
                ],
                'leftHeader' => [
                    'label' => Horde_Form_Translation::t("Left header"),
                    'type'  => 'text',
                ],
                'rightHeader' => [
                    'label' => Horde_Form_Translation::t("Right header"),
                    'type'  => 'text',
                ],
                'size' => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int',
                ],
                'width' => [
                    'label' => Horde_Form_Translation::t("Width in CSS units"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
