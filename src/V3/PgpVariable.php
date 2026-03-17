<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * PgpVariable type for PGP key input fields.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 * @property int $rows The number of rows for the textarea
 * @property int $cols The number of columns for the textarea
 * @property array $helper Array of helper options
 * @property string $gpg Path to the GnuPG binary
 * @property string $temp A temporary directory
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_pgp PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class PgpVariable extends LongtextVariable
{
    /**
     * Path to the GnuPG binary.
     *
     * @var string
     */
    public $_gpg;

    /**
     * A temporary directory.
     *
     * @var string
     */
    public $_temp;

    /**
     * Initialize a PGP field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string|null $gpg - Path to the GnuPG binary
     *                      - $params[1]: string|null $temp_dir - A temporary directory
     *                      - $params[2]: int $rows - Number of rows (default: 8, from parent)
     *                      - $params[3]: int $cols - Number of columns (default: 80, from parent)
      *
      * @api
     */
    public function init(...$params)
    {
        $gpg = $params[0] ?? null;
        $temp_dir = $params[1] ?? null;
        $rows = $params[2] ?? null;
        $cols = $params[3] ?? null;

        $this->_gpg = $gpg;
        $this->_temp = $temp_dir;
        parent::init($rows, $cols);
    }

    /**
     * Returns a parameter hash for the Horde_Crypt_pgp constructor.
     *
     * @return array  A parameter hash.
     */
    public function getPGPParams()
    {
        return [ 'program' => $this->_gpg, 'temp' => $this->_temp ];
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("PGP Key"),
            'params' => [
                'gpg'      => [
                    'label' => Horde_Form_Translation::t("Path to the GnuPG binary"),
                    'type'  => 'string'
                ],
                'temp_dir' => [
                    'label' => Horde_Form_Translation::t("A temporary directory"),
                    'type'  => 'string'
                ],
                'rows'     => [
                    'label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'
                ],
                'cols'     => [
                    'label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int'
                ]
            ]
        ];
    }
}
