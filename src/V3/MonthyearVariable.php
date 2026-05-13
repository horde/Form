<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * MonthyearVariable type for month and year selection fields.
 *
 * @property int $start_year The first available year for input
 * @property int $end_year The last available year for input

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_monthyear PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class MonthyearVariable extends BaseVariable
{
    public $_start_year;
    public $_end_year;

    /**
     * Initialize a month/year field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: int|null $start_year - The first available year for input (default: 1920)
     *                      - $params[1]: int|null $end_year - The last available year for input (default: current year)
      *
      * @api
     */
    public function init(...$params)
    {
        $start_year = $params[0] ?? null;
        $end_year = $params[1] ?? null;

        if (empty($start_year)) {
            $start_year = 1920;
        }
        if (empty($end_year)) {
            $end_year = date('Y');
        }

        $this->_start_year = $start_year;
        $this->_end_year = $end_year;
    }

    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (!$this->isRequired()) {
            return true;
        }

        if (!$vars->get($this->getMonthVar()) || !$vars->get($this->getYearVar())) {
            return $this->invalid('Please enter a month and a year.');
        }

        return true;
    }

    public function getMonthVar()
    {
        return $this->getVarName() . '[month]';
    }

    public function getYearVar()
    {
        return $this->getVarName() . '[year]';
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Month and year"),
            'params' => [
                'start_year' => [
                    'label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int',
                ],
                'end_year'   => [
                    'label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
