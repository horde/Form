<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Date;
use Horde_Form_Translation;

class MonthdayyearVariable extends BaseVariable
{
    public $_start_year;
    public $_end_year;
    public $_picker;
    public $_format_in = null;
    public $_format_out = '%x';

    /**
     * Return the date supplied as a Horde_Date object.
     *
     *     function init($start_year = '', $end_year = '', $picker = true,
     *             $format_in = null, $format_out = '%x')
     *
     * @param int  $start_year  The first available year for input.
     * @param int  $end_year    The last available year for input.
     * @param bool $picker      Do we show the DHTML calendar?
     * @param int  $format_in   The format to use when sending the date
     *                             for storage. Defaults to Unix epoch.
     *                             Similar to the strftime() function.
     * @param int $format_out  The format to use when displaying the
     *                             date. Similar to the strftime() function.
     */
    public function init(...$params)
    {
        $start_year = $params[0] ?? '';
        $end_year = $params[1] ?? '';
        $picker = $params[2] ?? true;
        $format_in = $params[3] ?? null;
        $format_out = $params[4] ?? '%x';

        if (empty($start_year)) {
            $start_year = date('Y');
        }
        if (empty($end_year)) {
            $end_year = date('Y') + 10;
        }

        $this->_start_year = $start_year;
        $this->_end_year = $end_year;
        $this->_picker = $picker;
        $this->_format_in = $format_in;
        $this->_format_out = $format_out;
    }

    public function isValid(Horde_Variables|array $vars, $date): bool
    {
        $empty = $this->emptyDateArray($date);

        if ($empty == 1 && $this->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if ($empty == 0 && !checkdate(
            $date['month'],
            $date['day'],
            $date['year']
        )) {
            return $this->invalid('Please enter a valid date, check the number of days in the month.');
        }

        if ($empty == -1) {
            return $this->invalid('Select all date components.');
        }

        return true;
    }

    /**
     * Determine if the provided date value is completely empty, partially empty
     * or non-empty.
     *
     * @param mixed $date  String or date part array representation of date.
     *
     * @return integer  0 for non-empty, 1 for completely empty or -1 for
     *                  partially empty.
     */
    public function emptyDateArray($date)
    {
        if (!is_array($date)) {
            return (int) empty($date);
        }
        $empty = 0;
        /* Check each date array component. */
        foreach (['day', 'month', 'year'] as $key) {
            if (empty($date[$key])) {
                $empty++;
            }
        }

        /* Check state of empty. */
        if ($empty == 0) {
            /* If no empty parts return 0. */
            return 0;
        } elseif ($empty == 3) {
            /* If all empty parts return 1. */
            return 1;
        } else {
            /* If some empty parts return -1. */
            return -1;
        }
    }

    /**
     * Return the date supplied split up into an array.
     *
     * @param string $date_in  Date in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS
     *                         and UNIX epoch) plus the fourth YYYY-MM-DD.
     *
     * @return array  Array with three elements - year, month and day.
     */
    public function getDateParts($date_in)
    {
        if (is_array($date_in)) {
            /* This is probably a failed isValid input so just return
             * the parts as they are. */
            return $date_in;
        } elseif (empty($date_in)) {
            /* This is just an empty field so return empty parts. */
            return ['year' => '', 'month' => '', 'day' => ''];
        }

        $date = $this->getDateOb($date_in);
        return ['year' => $date->year,
            'month' => $date->month,
            'day' => $date->mday];
    }

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param string $date_in  Date in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS
     *                         and UNIX epoch) plus the fourth YYYY-MM-DD.
     *
     * @return Horde_Date  The date object.
     */
    public function getDateOb($date_in)
    {
        if (is_array($date_in)) {
            /* If passed an array change it to the ISO format. */
            if ($this->emptyDateArray($date_in) == 0) {
                $date_in = sprintf(
                    '%04d-%02d-%02d 00:00:00',
                    $date_in['year'],
                    $date_in['month'],
                    $date_in['day']
                );
            }
        } elseif (preg_match('/^\d{4}-?\d{2}-?\d{2}$/', $date_in)) {
            /* Fix the date if it is the shortened ISO. */
            $date_in = $date_in . ' 00:00:00';
        }

        return new Horde_Date($date_in);
    }

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param string $date  Either an already set up Horde_Date object or a
     *                      string date in one of the three formats supported
     *                      by Horde_Form and Horde_Date (ISO format
     *                      YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS and
     *                      UNIX epoch) plus the fourth YYYY-MM-DD.
     *
     * @return string  The date formatted according to the $format_out
     *                 parameter when setting up the monthdayyear field.
     */
    public function formatDate($date)
    {
        if (!($date instanceof Horde_Date)) {
            $date = $this->getDateOb($date);
        }

        return $date->strftime($this->_format_out);
    }

    /**
     * Insert the date input through the form into $info array, in the format
     * specified by the $format_in parameter when setting up monthdayyear
     * field.
     */
    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        return $this->_validateAndFormat($this->getValue($vars));
    }

    /**
     * Validate/format a date submission.
     */
    public function _validateAndFormat($value)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        if ($this->emptyDateArray($value) == 1) {
            $value = $this->getDefault();
        }

        // If any component is empty consider it a bad date and return null
        if ($this->emptyDateArray($value) != 0) {
            return null;
        }

        $date = $this->getDateOb($value);
        if (!strlen($this->_format_in)) {
            return $date->timestamp();
        }

        return $date->strftime($this->_format_in);
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Date selection"),
            'params' => [
                'start_year' => [
                    'label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int'
                ],
                'end_year'   => [
                    'label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int'
                ],
                'picker'     => [
                    'label' => Horde_Form_Translation::t("Show picker?"),
                    'type'  => 'boolean'
                ],
                'format_in'  => [
                    'label' => Horde_Form_Translation::t("Storage format"),
                    'type'  => 'text'
                ],
                'format_out' => [
                    'label' => Horde_Form_Translation::t("Display format"),
                    'type'  => 'text'
                ]
            ]
        ];
    }

}
