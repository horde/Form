<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Date;
use Horde_Form_Translation;

class HourminutesecondVariable extends BaseVariable
{
    public $_show_seconds;

    /**
     * Initialize a Set form type
     *
     * function init($show_seconds = false)
     */
    public function init(...$params)
    {
        $this->_show_seconds = $params[0] ?? false;
    }

    public function isValid(Horde_Variables|array $vars, $time): bool
    {
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

    public function emptyTimeArray($time)
    {
        return (is_array($time)
                && (!isset($time['hour']) || !strlen($time['hour']))
                && (!isset($time['minute']) || !strlen($time['minute']))
                && (!$this->_show_seconds || !strlen($time['second'])));
    }

    /**
     * Return info about field type.
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Time selection"),
            'params' => [
                'show_seconds' => [
                    'label' => Horde_Form_Translation::t("Show seconds?"),
                    'type'  => 'boolean'
                ]
            ]
        ];
    }

}
