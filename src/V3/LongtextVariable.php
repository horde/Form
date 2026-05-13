<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * LongtextVariable type for multi-line text input fields.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 * @property int $rows The number of rows for the textarea
 * @property int $cols The number of columns for the textarea
 * @property array $helper Array of helper options

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_longtext PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class LongtextVariable extends TextVariable
{
    public $_rows;
    public $_cols;
    public $_helper = [];

    /**
     * Initialize a Longtext field type
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: int $rows - Number of rows (default: 8)
     *                      - $params[1]: int $cols - Number of columns (default: 80)
     *                      - $params[2]: array $helper - Array of helper options (default: [])
      *
      * @api
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
        }

        if (empty($this->_helper)) {
            /* No helpers activated at all, return false. */
            return false;
        }

        /* Check if given helper has been activated. */
        return in_array($option, $this->_helper);
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Long text"),
            'params' => [
                'rows'   => [
                    'label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int',
                ],
                'cols'   => [
                    'label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int',
                ],
                'helper' => [
                    'label' => Horde_Form_Translation::t("Helpers"),
                    'type'  => 'stringarray',
                ],
            ],
        ];
    }
}
