<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Date;
use Horde_Form_Translation;
use Horde_Date_Exception;

/**
 * DateVariable type for date input fields.
 *
 * @property string $format The date format string
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_date PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class DateVariable extends BaseVariable
{
    public $_format;

    /**
     * Initialize a date field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: string $format - The date format string (default: '%a %d %B')
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_format = $params[0] ?? '%a %d %B';
    }

    protected function isValid(Horde_Variables|array $vars, $value): bool
    {
        if ($this->isRequired() && strlen(trim((string)$value)) == 0) {
            $this->message = sprintf(Horde_Form_Translation::t("%s is required"), $this->getHumanName());
            return false;
        }

        return true;
    }

    /**
     * @static
     *
     * @param mixed $date  The date to calculate the difference from. Can be
     *                     either a timestamp integer value, or an array
     *                     with date parts: 'day', 'month', 'year'.
     *
     * @return string
     */
    public static function getAgo($date)
    {
        if ($date === null) {
            return '';
        }

        try {
            $today = new Horde_Date(time());
            $date = new Horde_Date($date);
            $ago = $date->toDays() - $today->toDays();
        } catch (Horde_Date_Exception $e) {
            return '';
        }

        if ($ago < -1) {
            return sprintf(Horde_Form_Translation::t(" (%s days ago)"), abs($ago));
        }

        if ($ago == -1) {
            return Horde_Form_Translation::t(" (yesterday)");
        }

        if ($ago == 0) {
            return Horde_Form_Translation::t(" (today)");
        }

        if ($ago == 1) {
            return Horde_Form_Translation::t(" (tomorrow)");
        }

        return sprintf(Horde_Form_Translation::t(" (in %s days)"), $ago);
    }

    public function getFormattedTime($timestamp, $format = null, $showago = true)
    {
        if (empty($timestamp)) {
            return '';
        }

        if (empty($format)) {
            $format = $this->_format;
        }

        return strftime($format, $timestamp) . ($showago ? $this::getAgo($timestamp) : '');
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Date"),
            'params' => [
                'format' => [
                    'label' => Horde_Form_Translation::t("Format"),
                    'type'  => 'string'
                ]
            ]
        ];
    }
}
