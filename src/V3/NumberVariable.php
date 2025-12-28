<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class NumberVariable extends BaseVariable
{
    public $_fraction;

    public function init(...$params)
    {
        $this->_fraction = $params[0] ?? null;
    }

    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        if ($this->isRequired() && empty($value) && ((string) (float) $value !== $value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value)) {
            return true;
        }

        /* If matched, then this is a correct numeric value. */
        if (preg_match($this->_getValidationPattern(), $value)) {
            return true;
        }

        return $this->invalid('This field must be a valid number.');
    }

    public function _getValidationPattern()
    {
        static $pattern = '';
        if (!empty($pattern)) {
            return $pattern;
        }

        /* Get current locale information. */
        $linfo = Horde_Nls::getLocaleInfo();

        /* Build the pattern. */
        $pattern = '(-)?';

        /* Only check thousands separators if locale has any. */
        if (!empty($linfo['mon_thousands_sep'])) {
            /* Regex to check for correct thousands separators (if any). */
            $pattern .= '((\d+)|((\d{0,3}?)([' . $linfo['mon_thousands_sep'] . ']\d{3})*?))';
        } else {
            /* No locale thousands separator, check for only digits. */
            $pattern .= '(\d+)';
        }
        /* If no decimal point specified default to dot. */
        if (empty($linfo['mon_decimal_point'])) {
            $linfo['mon_decimal_point'] = '.';
        }
        /* Regex to check for correct decimals (if any). */
        if (empty($this->_fraction)) {
            $fraction = '*';
        } else {
            $fraction = '{0,' . $this->_fraction . '}';
        }
        $pattern .= '([' . $linfo['mon_decimal_point'] . '](\d' . $fraction . '))?';

        /* Put together the whole regex pattern. */
        $pattern = '/^' . $pattern . '$/';

        return $pattern;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $value = $vars->get($this->getVarName());
        $linfo = Horde_Nls::getLocaleInfo();
        $value = str_replace($linfo['mon_thousands_sep'], '', $value);
        return str_replace($linfo['mon_decimal_point'], '.', $value);
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Number"),
            'params' => [
                'fraction'  => [
                    'label' => Horde_Form_Translation::t("Fraction"),
                    'type'  => 'int'
                ]
            ]
        ];
    }
}
