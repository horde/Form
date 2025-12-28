<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class MonthyearVariable extends BaseVariable
{
    public $_start_year;
    public $_end_year;

    /**
     * Initialize a Month/Year form type
     *
     * function init($start_year = null, $end_year = null)
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

    public function isValid(Horde_Variables|array $vars, $value): bool
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

    public function getYearVar($var)
    {
        return $this->getVarName() . '[year]';
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Month and year"),
            'params' => [
                'start_year' => [
                    'label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int'
                ],
                'end_year'   => [
                    'label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int'
                ]
            ]
        ];
    }

}
