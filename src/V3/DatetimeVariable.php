<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * DatetimeVariable type for date and time selection fields.
 *
 * @property int $start_year The first available year for input
 * @property int $end_year The last available year for input
 * @property bool $picker Do we show the DHTML calendar
 * @property string|null $format_in The format to use when sending the date for storage
 * @property string $format_out The format to use when displaying the date
 * @property bool $show_seconds Include a form input for seconds
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_datetime PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class DatetimeVariable extends BaseVariable
{
    public $_mdy;
    public $_hms;

    /**
     * Initialize a date and time field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: int $start_year - The first available year for input (default: '')
     *                      - $params[1]: int $end_year - The last available year for input (default: '')
     *                      - $params[2]: bool $picker - Do we show the DHTML calendar (default: true)
     *                      - $params[3]: string|null $format_in - The format to use when sending the date for storage. Defaults to Unix epoch. Similar to the strftime() function. (default: null)
     *                      - $params[4]: string $format_out - The format to use when displaying the date. Similar to the strftime() function. (default: '%x')
     *                      - $params[5]: bool $show_seconds - Include a form input for seconds (default: false)
      *
      * @api
     */
    public function init(...$params)
    {
        $start_year = $params[0] ?? '';
        $end_year = $params[1] ?? '';
        $picker = $params[2] ?? true;
        $format_in = $params[3] ?? null;
        $format_out = $params[4] ?? '%x';
        $show_seconds = $params[5] ?? false;

        $this->_mdy = new MonthdayyearVariable('', '', true);
        $this->_mdy->init($start_year, $end_year, $picker, $format_in, $format_out);
        $this->_hms = new HourminutesecondVariable('', '', true);
        $this->_hms->init($show_seconds);
    }

    public function isValid(Horde_Variables|array $vars, $date): bool
    {
        /* Require all fields if one field is not empty */
        if ($this->isRequired() || $this->emptyDateArray($date) != 1 || !$this->emptyTimeArray($date)) {
            $mdy_valid = $this->_mdy->isValid($vars, $date);
            $hms_valid = $this->_hms->isValid($vars, $date);
            if (!$mdy_valid) {
                return $this->invalid('You must choose a date.');
            }
            if (!$hms_valid) {
                return $this->invalid('You must choose a time.');
            }
        }

        return true;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        $value = $this->getValue($vars);
        if ($this->emptyDateArray($value) == 1 || $this->emptyTimeArray($value)) {
            return $this->_getInfo($this->getDefault());
        }

        return $this->_getInfo($value);
    }

    private function _getInfo($value)
    {
        // If any component is empty consider it a bad date and return null
        if ($this->emptyDateArray($value) != 0 || $this->emptyTimeArray($value)) {
            return null;
        }

        $date = $this->getDateOb($value);
        $time = $this->getTimeOb($value);
        $date->hour = $time->hour;
        $date->min = $time->min;
        $date->sec = $time->sec;

        if ($this->getProperty('format_in') === null) {
            $info = $date->timestamp();
        } else {
            $info = $date->strftime($this->getProperty('format_in'));
        }

        return $info;
    }

    public function getProperty($property)
    {
        if ($property == 'show_seconds') {
            return $this->_hms->getProperty($property);
        }
        return $this->_mdy->getProperty($property);
    }

    public function setProperty($property, $value)
    {
        if ($property == 'show_seconds') {
            $this->_hms->setProperty($property, $value);
        } else {
            $this->_mdy->setProperty($property, $value);
        }
    }

    public function checktime($hour, $minute, $second)
    {
        return $this->_hms->checktime($hour, $minute, $second);
    }

    public function getTimeOb($time_in)
    {
        return $this->_hms->getTimeOb($time_in);
    }

    public function getTimeParts($time_in)
    {
        return $this->_hms->getTimeParts($time_in);
    }

    public function emptyTimeArray($time)
    {
        return $this->_hms->emptyTimeArray($time);
    }

    public function emptyDateArray($date)
    {
        return $this->_mdy->emptyDateArray($date);
    }

    public function getDateParts($date_in)
    {
        return $this->_mdy->getDateParts($date_in);
    }

    public function getDateOb($date_in)
    {
        return $this->_mdy->getDateOb($date_in);
    }

    public function formatDate($date)
    {
        if ($this->_mdy->emptyDateArray($date)) {
            return '';
        }

        return $this->_mdy->formatDate($date);
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Date and time selection"),
            'params' => [
                'start_year' => [
                    'label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int'
                ],
                'end_year' => [
                    'label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int'
                ],
                'picker' => [
                    'label' => Horde_Form_Translation::t("Show picker?"),
                    'type'  => 'boolean'
                ],
                'format_in' => [
                    'label' => Horde_Form_Translation::t("Storage format"),
                    'type'  => 'text'
                ],
                'format_out' => [
                    'label' => Horde_Form_Translation::t("Display format"),
                    'type'  => 'text'
                ],
                'show_seconds' => [
                    'label' => Horde_Form_Translation::t("Show seconds?"),
                    'type'  => 'boolean'
                ]
            ]
        ];
    }
}
