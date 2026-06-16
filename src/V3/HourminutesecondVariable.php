<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Date;
use Horde_Form_Translation;

/**
 * HourminutesecondVariable type for time selection fields.
 *
 * @property bool $show_seconds Include a form input for seconds

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_hourminutesecond PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class HourminutesecondVariable extends BaseVariable
{
    public $_show_seconds;

    /**
     * Initialize a time selection field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: bool $show_seconds - Include a form input for seconds (default: false)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_show_seconds = $params[0] ?? false;
    }

    public function isValid(Horde_Variables|Variables $vars, $time): bool
    {
        if (!is_array($time)) {
            if ($this->isRequired()) {
                return $this->invalid('Please enter a valid time.');
            }
            return true;
        }

        if (!$this->_show_seconds && count($time) && !isset($time['second'])) {
            $time['second'] = 0;
        }

        if (!$this->emptyTimeArray($time) && !$this->checktime($time['hour'], $time['minute'], $time['second'])) {
            return $this->invalid('Please enter a valid time.');
        }

        if ($this->emptyTimeArray($time) && $this->isRequired()) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

    /**
     * Validates time component values.
     *
     * Checks that hour (0-23), minute (0-60), and second (0-60) are within
     * valid ranges and not empty. All three components must be set.
     *
     * @param int|string $hour    Hour value (0-23)
     * @param int|string $minute  Minute value (0-60)
     * @param int|string $second  Second value (0-60)
     *
     * @return bool  True if all components are valid, false otherwise
      *
      * @api
     */
    public function checktime($hour, $minute, $second)
    {
        if (!isset($hour) || $hour == '' || ($hour < 0 || $hour > 23)) {
            return false;
        }

        if (!isset($minute) || $minute == '' || ($minute < 0 || $minute > 60)) {
            return false;
        }

        if (!isset($second) || $second === '' || ($second < 0 || $second > 60)) {
            return false;
        }

        return true;
    }

    /**
     * Return the time supplied as a Horde_Date object.
     *
     * @param string $time_in  Date in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS and
     *                         UNIX epoch).
     *
     * @return Horde_Date  The time object.
      *
      * @api
     */
    public function getTimeOb($time_in)
    {
        if (is_array($time_in)) {
            if (!$this->emptyTimeArray($time_in)) {
                $time_in = sprintf('1970-01-01 %02d:%02d:%02d', $time_in['hour'], $time_in['minute'], $this->_show_seconds ? $time_in['second'] : 0);
            }
        }

        return new Horde_Date($time_in);
    }

    /**
     * Return the time supplied split up into an array.
     *
     * @param string $time_in  Time in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS and
     *                         UNIX epoch).
     *
     * @return array  Array with three elements - hour, minute and seconds.
      *
      * @api
     */
    public function getTimeParts($time_in)
    {
        if (is_array($time_in)) {
            /* This is probably a failed isValid input so just return the
             * parts as they are. */
            return $time_in;
        } elseif (empty($time_in)) {
            /* This is just an empty field so return empty parts. */
            return ['hour' => '', 'minute' => '', 'second' => ''];
        }

        $time = $this->getTimeOb($time_in);
        return ['hour' => $time->hour,
            'minute' => $time->min,
            'second' => $time->sec];
    }

    /**
     * Checks if time array is empty.
     *
     * A time array is considered empty if hour and minute are not set or have
     * empty string values. If seconds are shown, second must also be empty.
     *
     * @param array|mixed $time  Time array with 'hour', 'minute', 'second' keys
     *
     * @return bool  True if time array is empty, false otherwise
      *
      * @api
     */
    public function emptyTimeArray($time)
    {
        return (is_array($time)
                && (!isset($time['hour']) || !strlen($time['hour']))
                && (!isset($time['minute']) || !strlen($time['minute']))
                && (!$this->_show_seconds || !strlen($time['second'])));
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Time selection"),
            'params' => [
                'show_seconds' => [
                    'label' => Horde_Form_Translation::t("Show seconds?"),
                    'type'  => 'boolean',
                ],
            ],
        ];
    }
}
