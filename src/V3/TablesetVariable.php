<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * TablesetVariable type for table set input fields.
 *
 * @property array $values The values for the table set
 * @property array $header The headers for the table set
 */
class TablesetVariable extends BaseVariable
{
    public $_values;
    public $_header;

    /**
     * The initialisation function for the tableset variable type.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array $values - The values for the table set.
     *                      - $params[1]: array $header - The headers for the table set.
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_values = $params[0];
        $this->_header = $params[1];
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
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
        return $this->invalid('Invalid data submitted.');
    }

    public function getHeader()
    {
        return $this->_header;
    }

    public function getValues(...$params): ?array
    {
        return $this->_values;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Table Set"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringlist',
                ],
                'header' => [
                    'label' => Horde_Form_Translation::t("Headers"),
                    'type'  => 'stringlist',
                ],
            ],
        ];
    }
}
