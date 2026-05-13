<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * SmimeVariable type for S/MIME key input fields.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 * @property int $rows The number of rows for the textarea
 * @property int $cols The number of columns for the textarea
 * @property array $helper Array of helper options
 * @property string $temp A temporary directory

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_smime PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class SmimeVariable extends LongtextVariable
{
    /**
     * A temporary directory.
     *
     * @var string
     */
    public $_temp;

    /**
     * Initialize a S/MIME field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string|null $temp_dir - A temporary directory
     *                      - $params[1]: int $rows - Number of rows (default: 8, from parent)
     *                      - $params[2]: int $cols - Number of columns (default: 80, from parent)
      *
      * @api
     */
    public function init(...$params)
    {
        $temp_dir = $params[0] ?? null;
        $rows = $params[1] ?? null;
        $cols = $params[2] ?? null;

        $this->_temp = $temp_dir;
        parent::init($rows, $cols);
    }

    /**
     * Returns a parameter hash for the Horde_Crypt_smime constructor.
     *
     * @return array  A parameter hash.
     */
    public function getSMIMEParams()
    {
        return [ 'temp' => $this->_temp ];
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("S/MIME Key"),
            'params' => [
                'temp_dir' => [
                    'label' => Horde_Form_Translation::t("A temporary directory"),
                    'type'  => 'string',
                ],
                'rows'     => [
                    'label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int',
                ],
                'cols'     => [
                    'label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
