<?php

/**
 * Copyright 2001-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Form
 */

/**
 * Horde_Form_Type Class
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Form
 */
class Horde_Form_Type
{
    /**
     * Messages from isValid() method.
     */
    protected string $message = '';

    public function getMessage()
    {
        return $this->message;
    }

    public function getProperty($property)
    {
        $prop = '_' . $property;
        return $this->$prop ?? null;
    }

    public function __get($property)
    {
        return $this->getProperty($property);
    }

    public function setProperty($property, $value)
    {
        $prop = '_' . $property;
        $this->$prop = $value;
    }

    public function __set($property, $value)
    {
        return $this->setProperty($property, $value);
    }

    /**
     * Initialize (kind of constructor) - Parameter list may vary on overloading
     */
    public function init(...$params) {}

    public function onSubmit($var, $vars) {}

    public function isValid($var, $vars, $value, $message)
    {
        $message = '<strong>Error:</strong> Horde_Form_Type::isValid() called - should be overridden<br />';
        $this->message = $message;
        return false;
    }

    public function getTypeName()
    {
        return str_replace('horde_form_type_', '', Horde_String::lower(get_class($this)));
    }

    public function getValues(...$params)
    {
        return null;
    }

    public function getInfo($vars, $var, $info)
    {
        $info = $var->getValue($vars);
        return $info;
    }

    public function invalid(string $message): bool
    {
        $this->message = Horde_Form_Translation::t($message);
        return false;
    }

    public function about()
    {
        return ['name' => $this->getTypeName()];
    }

}

class Horde_Form_Type_spacer extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Spacer")];
    }

}

class Horde_Form_Type_header extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Header")];
    }

}

class Horde_Form_Type_description extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Description")];
    }

}

/**
 * Simply renders its raw value in both active and inactive rendering.
 */
class Horde_Form_Type_html extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("HTML")];
    }

}

class Horde_Form_Type_number extends Horde_Form_Type
{
    public $_fraction;

    public function init(...$params)
    {
        $this->_fraction = $params[0] ?? null;
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value) && ((string) (float) $value !== $value)) {
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

    public function getInfo($vars, $var, $info)
    {
        $value = $vars->get($var->getVarName());
        $linfo = Horde_Nls::getLocaleInfo();
        $value = str_replace($linfo['mon_thousands_sep'], '', $value);
        $info = str_replace($linfo['mon_decimal_point'], '.', $value);
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Number"),
            'params' => [
                'fraction'  => ['label' => Horde_Form_Translation::t("Fraction"),
                    'type'  => 'int']]];
    }

}

/**
 * A Form type for an input line validating to an integer
 */
class Horde_Form_Type_int extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value) && ((string) (int) $value !== $value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || preg_match('/^[0-9]+$/', $value)) {
            return true;
        }

        return $this->invalid('This field may only contain integers.');
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Integer")];
    }

}

class Horde_Form_Type_octal extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value) && ((string) (int) $value !== $value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || preg_match('/^[0-7]+$/', $value)) {
            return true;
        }

        return $this->invalid('This field may only contain octal values.');
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Octal")];
    }

}

class Horde_Form_Type_intlist extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if (empty($value) && $var->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || preg_match('/^[0-9 ,]+$/', $value)) {
            return true;
        }

        return $this->invalid('This field must be a comma or space separated list of integers');
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Integer list")];
    }

}

/**
 * A Text Box form type
 */
class Horde_Form_Type_text extends Horde_Form_Type
{
    public $_regex;
    public $_size;
    public $_maxlength;

    /**
     * The initialisation function for the text variable type.
     *
     * function init($regex = '', $size = 40, $maxlength = null)
     *
     * @access private
     *
     * @param string $regex       Any valid PHP PCRE pattern syntax that
     *                            needs to be matched for the field to be
     *                            considered valid. If left empty validity
     *                            will be checked only for required fields
     *                            whether they are empty or not.
     *                            If using this regex test it is advisable
     *                            to enter a description for this field to
     *                            warn the user what is expected, as the
     *                            generated error message is quite generic
     *                            and will not give any indication where
     *                            the regex failed.
     * @param int     $size       The size of the input field.
     * @param int     $maxlength  The max number of characters.
     */
    public function init(...$params)
    {
        $this->_regex     = $params[0] ?? '';
        $this->_size      = $params[1] ?? 40;
        $this->_maxlength = $params[2] ?? null;
    }

    public function isValid($var, $vars, $value, $message)
    {
        if (!empty($this->_maxlength) && Horde_String::length($value) > $this->_maxlength) {
            $this->message = sprintf(Horde_Form_Translation::t("Value is over the maximum length of %d."), $this->_maxlength);
            return false;
        }

        if ($var->isRequired() && empty($this->_regex)) {
            if (strlen(trim($value)) == 0) {
                return $this->invalid('This field is required.');
            }
        } elseif (!empty($this->_regex) && !preg_match($this->_regex, $value)) {
            return $this->invalid('You must enter a valid value.');
        }

        return true;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getMaxLength()
    {
        return $this->_maxlength;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Text"),
            'params' => [
                'regex'     => ['label' => Horde_Form_Translation::t("Regex"),
                    'type'  => 'text'],
                'size'      => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
                'maxlength' => ['label' => Horde_Form_Translation::t("Maximum length"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_stringlist extends Horde_Form_Type_text
{
    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("String list"),
            'params' => [
                'regex'     => ['label' => Horde_Form_Translation::t("Regex"),
                    'type'  => 'text'],
                'size'      => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
                'maxlength' => ['label' => Horde_Form_Translation::t("Maximum length"),
                    'type'  => 'int']],
        ];
    }

}

class Horde_Form_Type_stringarray extends Horde_Form_Type_stringlist
{
    public function getInfo($vars, $var, $info)
    {
        $info = array_map('trim', explode(',', $vars->get($var->getVarName())));
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("String list returning an array"),
            'params' => [
                'regex'     => ['label' => Horde_Form_Translation::t("Regex"),
                    'type'  => 'text'],
                'size'      => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
                'maxlength' => ['label' => Horde_Form_Translation::t("Maximum length"),
                    'type'  => 'int']],
        ];
    }

}

class Horde_Form_Type_phone extends Horde_Form_Type
{
    /**
     * The size of the input field.
     *
     * @var integer
     */
    public $_size;

    /**
     * @param integer $size  The size of the input field.
     */
    public function init(...$params)
    {
        $this->_size = $params[0] ?? 15;
    }

    public function isValid($var, $vars, $value, $message)
    {
        if (!strlen(trim($value))) {
            if ($var->isRequired()) {
                return $this->invalid('This field is required.');
            }
        } elseif (!preg_match('/^\+?[\d()\-\/.\s]*$/u', $value)) {
            return $this->invalid("You must enter a valid phone number, digits only with an optional '+' for the international dialing prefix.");
        }

        return true;
    }

    public function getSize()
    {
        return $this->_size;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Phone number"),
            'params' => [
                'size'      => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
            ],
        ];
    }

}

class Horde_Form_Type_cellphone extends Horde_Form_Type_phone
{
    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Mobile phone number")];
    }

}

class Horde_Form_Type_ipaddress extends Horde_Form_Type_text
{
    public function isValid($var, $vars, $value, $message)
    {
        if (strlen(trim($value)) > 0) {
            $ip = explode('.', $value);
            $valid = count($ip) == 4;
            if ($valid) {
                foreach ($ip as $part) {
                    if (!is_numeric($part) ||
                        $part > 255 ||
                        $part < 0) {
                        $valid = false;
                        break;
                    }
                }
            }

            if (!$valid) {
                return $this->invalid('Please enter a valid IP address.');
            }
        } elseif ($var->isRequired()) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("IP address")];
    }

}

class Horde_Form_Type_ip6address extends Horde_Form_Type_text
{
    public function isValid($var, $vars, $value, $message)
    {
        if (strlen(trim($value)) > 0) {
            $valid = @inet_pton($value);
            if ($valid === false) {
                return $this->invalid('Please enter a valid IP address.');
            }
        } elseif ($var->isRequired()) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("IPv6 address")];
    }

}

class Horde_Form_Type_longtext extends Horde_Form_Type_text
{
    public $_rows;
    public $_cols;
    public $_helper = [];

    /**
     *   Initialize a Longtext field type
     *
     *   @param $rows = $params[0] ?? 8;
     *   @param $cols = $params[1] ?? 80;
     *   @param $helper = $params[2] ?? array();
     */
    public function init(...$params)
    {
        $rows = $params[0] ?? 8;
        $cols = $params[1] ?? 80;
        $helper = $params[2] ?? [];

        if (!is_array($helper)) {
            $helper = [$helper];
        }

        $this->_rows = $rows;
        $this->_cols = $cols;
        $this->_helper = $helper;
    }

    public function getRows()
    {
        return $this->_rows;
    }

    public function getCols()
    {
        return $this->_cols;
    }

    public function hasHelper($option = '')
    {
        if (empty($option)) {
            /* No option specified, check if any helpers have been
             * activated. */
            return !empty($this->_helper);
        } elseif (empty($this->_helper)) {
            /* No helpers activated at all, return false. */
            return false;
        } else {
            /* Check if given helper has been activated. */
            return in_array($option, $this->_helper);
        }
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Long text"),
            'params' => [
                'rows'   => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols'   => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int'],
                'helper' => ['label' => Horde_Form_Translation::t("Helpers"),
                    'type'  => 'stringarray']]];
    }

}

class Horde_Form_Type_countedtext extends Horde_Form_Type_longtext
{
    public $_chars;

    /**
     * Init a longtext field
     *
     * function init($rows = null, $cols = null, $chars = 1000)
     */
    public function init(...$params)
    {
        $rows = $params[0] ?? null;
        $cols = $params[1] ?? null;
        $chars = $params[2] ?? 1000;
        parent::init($rows, $cols);
        $this->_chars = $chars;
    }

    public function isValid($var, $vars, $value, $message)
    {
        $length = Horde_String::length(trim($value));

        if ($var->isRequired() && $length <= 0) {
            return $this->invalid('This field is required.');
        }

        if ($length > $this->_chars) {
            $this->message = sprintf(Horde_Form_Translation::ngettext("There are too many characters in this field. You have entered %d character; ", "There are too many characters in this field. You have entered %d characters; ", $length), $length)
                . sprintf(Horde_Form_Translation::t("you must enter less than %d."), $this->_chars);
            return false;
        }

        return true;
    }

    public function getChars()
    {
        return $this->_chars;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Counted text"),
            'params' => [
                'rows'  => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols'  => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int'],
                'chars' => ['label' => Horde_Form_Translation::t("Number of characters"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_address extends Horde_Form_Type_longtext
{
    public function parse($address)
    {
        $info = [];
        $aus_state_regex = '(?:ACT|NSW|NT|QLD|SA|TAS|VIC|WA)';

        if (preg_match('/(?s)(.*?)(?-s)\r?\n(?:(.*?)\s+)?((?:A[BL]|B[ABDHLNRST]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[CHNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTWY]?|T[ADFNQRSW]|UB|W[ACDFNRSV]?|YO|ZE)\d(?:\d|[A-Z])? \d[A-Z]{2})/', $address, $addressParts)) {
            /* UK postcode detected. */
            $info = ['country' => 'uk', 'zip' => $addressParts[3]];
            if (!empty($addressParts[1])) {
                $info['street'] = $addressParts[1];
            }
            if (!empty($addressParts[2])) {
                $info['city'] = $addressParts[2];
            }
        } elseif (preg_match('/\b' . $aus_state_regex . '\b/', $address)) {
            /* Australian state detected. */
            /* Split out the address, line-by-line. */
            $addressLines = preg_split('/\r?\n/', $address);
            $info = ['country' => 'au'];
            for ($i = 0; $i < count($addressLines); $i++) {
                /* See if it's the street number & name. */
                if (preg_match('/(\d+\s*\/\s*)?(\d+|\d+[a-zA-Z])\s+([a-zA-Z ]*)/', $addressLines[$i], $lineParts)) {
                    $info['street'] = $addressLines[$i];
                    $info['streetNumber'] = $lineParts[2];
                    $info['streetName'] = $lineParts[3];
                }
                /* Look for "Suburb, State". */
                if (preg_match('/([a-zA-Z ]*),?\s+(' . $aus_state_regex . ')/', $addressLines[$i], $lineParts)) {
                    $info['city'] = $lineParts[1];
                    $info['state'] = $lineParts[2];
                }
                /* Look for "State <4 digit postcode>". */
                if (preg_match('/(' . $aus_state_regex . ')\s+(\d{4})/', $addressLines[$i], $lineParts)) {
                    $info['state'] = $lineParts[1];
                    $info['zip'] = $lineParts[2];
                }
            }
        } elseif (preg_match('/(?s)(.*?)(?-s)\r?\n(.*)\s*,\s*(\w+)\.?\s+(\d+|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d)/', $address, $addressParts)) {
            /* American/Canadian address style. */
            $info = ['country' => 'us'];
            if (!empty($addressParts[4]) &&
                preg_match('|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d|', $addressParts[4])) {
                $info['country'] = 'ca';
            }
            if (!empty($addressParts[1])) {
                $info['street'] = $addressParts[1];
            }
            if (!empty($addressParts[2])) {
                $info['city'] = $addressParts[2];
            }
            if (!empty($addressParts[3])) {
                $info['state'] = $addressParts[3];
            }
            if (!empty($addressParts[4])) {
                $info['zip'] = $addressParts[4];
            }
        } elseif (preg_match('/(?:(?s)(.*?)(?-s)(?:\r?\n|,\s*))?(?:([A-Z]{1,3})-)?(\d{4,5})\s+(.*)(?:\r?\n(.*))?/i', $address, $addressParts)) {
            /* European address style. */
            $info = [];
            if (!empty($addressParts[1])) {
                $info['street'] = $addressParts[1];
            }
            if (!empty($addressParts[2])) {
                include 'Horde/Nls/Carsigns.php';
                $country = array_search(Horde_String::upper($addressParts[2]), $carsigns);
                if ($country) {
                    $info['country'] = $country;
                }
            }
            if (!empty($addressParts[5])) {
                include 'Horde/Nls/Countries.php';
                $country = array_search($addressParts[5], $countries);
                if ($country) {
                    $info['country'] = Horde_String::lower($country);
                } elseif (!isset($info['street'])) {
                    $info['street'] = trim($addressParts[5]);
                } else {
                    $info['street'] .= "\n" . $addressParts[5];
                }
            }
            if (!empty($addressParts[3])) {
                $info['zip'] = $addressParts[3];
            }
            if (!empty($addressParts[4])) {
                $info['city'] = trim($addressParts[4]);
            }
        }

        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Address"),
            'params' => [
                'rows' => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols' => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_addresslink extends Horde_Form_Type_address
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Address Link")];
    }

}

class Horde_Form_Type_pgp extends Horde_Form_Type_longtext
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
     * Init a PGP field
     *
     * function init($gpg, $temp_dir = null, $rows = null, $cols = null)
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
        return ['program' => $this->_gpg, 'temp' => $this->_temp];
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("PGP Key"),
            'params' => [
                'gpg'      => ['label' => Horde_Form_Translation::t("Path to the GnuPG binary"),
                    'type'  => 'string'],
                'temp_dir' => ['label' => Horde_Form_Translation::t("A temporary directory"),
                    'type'  => 'string'],
                'rows'     => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols'     => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_smime extends Horde_Form_Type_longtext
{
    /**
     * A temporary directory.
     *
     * @var string
     */
    public $_temp;

    /**
     * Init a S/MIME field
     *
     * function init($temp_dir = null, $rows = null, $cols = null)
     */
    public function init(...$params)
    {
        $temp_dir = $params[0] ?? null;
        $rows = $params[1] ?? null;
        $cols = $params[2] ?? null;

        $this->_temp = $temp_dir;
        parent::init($rows, $cols);
    }

    /**
     * Returns a parameter hash for the Horde_Crypt_smime constructor.
     *
     * @return array  A parameter hash.
     */
    public function getSMIMEParams()
    {
        return ['temp' => $this->_temp];
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("S/MIME Key"),
            'params' => [
                'temp_dir' => ['label' => Horde_Form_Translation::t("A temporary directory"),
                    'type'  => 'string'],
                'rows'     => ['label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int'],
                'cols'     => ['label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_country extends Horde_Form_Type_enum
{
    /**
     * Init a Country field
     *
     * function init($prompt = null)
     */
    public function init(...$params)
    {
        $prompt = $params[0] ?? null;
        parent::init(Horde_Nls::getCountryISO(), $prompt);
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Country drop down list"),
            'params' => [
                'prompt' => ['label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text']]];
    }

}

class Horde_Form_Type_file extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired()) {
            try {
                $GLOBALS['browser']->wasFileUploaded($var->getVarName());
            } catch (Horde_Browser_Exception $e) {
                $message = $e->getMessage();
                $this->message = $message;
                return false;
            }
        }

        return true;
    }

    public function getInfo($vars, $var, $info)
    {
        $name = $var->getVarName();
        try {
            $GLOBALS['browser']->wasFileUploaded($name);
            $info['name'] = Horde_Util::dispelMagicQuotes($_FILES[$name]['name']);
            $info['type'] = $_FILES[$name]['type'];
            $info['tmp_name'] = $_FILES[$name]['tmp_name'];
            $info['file'] = $_FILES[$name]['tmp_name'];
            $info['error'] = $_FILES[$name]['error'];
            $info['size'] = $_FILES[$name]['size'];
        } catch (Horde_Browser_Exception $e) {
        }
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("File upload")];
    }

}

class Horde_Form_Type_image extends Horde_Form_Type
{
    /**
     * Has a file been uploaded on this form submit?
     *
     * @var boolean
     */
    public $_uploaded = null;

    /**
     * Show the upload button?
     *
     * @var boolean
     */
    public $_show_upload = true;

    /**
     * Show the option to upload also original non-modified image?
     *
     * @var boolean
     */
    public $_show_keeporig = false;

    /**
     * Limit the file size?
     *
     * @var integer
     */
    public $_max_filesize = null;

    /**
     * Hash containing the previously uploaded image info.
     *
     * @var array
     */
    public $_img;

    /**
     * A random id that identifies the image information in the session data.
     *
     * @var string
     */
    public $_random;

    /**
     * Init a Country field
     *
     *     function init($show_upload = true, $show_keeporig = false, $max_filesize = null)
     */
    public function init(...$params)
    {
        $this->_show_upload   = $params[0] ?? true;
        $this->_show_keeporig = $params[1] ?? false;
        $this->_max_filesize  = $params[2] ?? null;
    }

    /**
     *     function onSubmit($var, $vars)
     */
    public function onSubmit($var, $vars)
    {
        /* Are we removing an image? */
        if ($vars->get('remove_' . $var->getVarName())) {
            $GLOBALS['session']->remove('horde', 'form/' . $this->getRandomId());
            $this->_img = null;
            return;
        }

        /* Get the upload. */
        $this->getImage($vars, $var);

        /* If this was done through the upload button override the submitted
         * value of the form. */
        if ($vars->get('do_' . $var->getVarName())) {
            $var->form->setSubmitted(false);
            if ($this->_uploaded instanceof Horde_Browser_Exception) {
                $this->_img = ['hash' => $this->getRandomId(),
                    'error' => $this->_uploaded->getMessage()];
            }
        }
    }

    /**
     * @param Horde_Form_Variable $var  The Form field object to check
     * @param Horde_Variables $vars     The form state to check this field for
     * @param array $value              The field value array - should contain a key ['hash'] which holds the key for the image on temp storage
     * @param something  $message       Not clear what this field does
     */

    public function isValid($var, $vars, $value, $message)
    {
        if ($vars->get('remove_' . $var->getVarName())) {
            return true;
        }

        /* Get the upload. */
        $this->getImage($vars, $var);
        $field = $vars->get($var->getVarName());

        /* The upload generated a PEAR Error. */
        if ($this->_uploaded instanceof Horde_Browser_Exception) {
            /* Not required and no image upload attempted. */
            if (!$var->isRequired() && empty($field['hash']) &&
                $this->_uploaded->getCode() == UPLOAD_ERR_NO_FILE) {
                return true;
            }

            if (($this->_uploaded->getCode() == UPLOAD_ERR_NO_FILE) &&
                empty($field['hash'])) {
                /* Nothing uploaded and no older upload. */
                return $this->invalid('This field is required.');
            }

            if (!empty($field['hash'])) {
                if ($this->_img && isset($this->_img['error'])) {
                    $message = $this->_img['error'];
                    $this->message = $message;
                    return false;
                }
                /* Nothing uploaded but older upload present. */
                return true;
            }
            /* Some other error message. */
            $message = $this->_uploaded->getMessage();
            $this->message = $message;
            return false;
        }
        if (empty($this->_img['img']['size'])) {
            return $this->invalid('The image file size could not be determined or it was 0 bytes. The upload may have been interrupted.');
        }
        if ($this->_max_filesize && $this->_img['img']['size'] > $this->_max_filesize) {
            $this->message = sprintf(Horde_Form_Translation::t("The image file was larger than the maximum allowed size (%d bytes)."), $this->_max_filesize);
            return false;
        }

        return true;
    }

    public function getInfo($vars, $var, $info)
    {
        /* Get the upload. */
        $this->getImage($vars, $var);

        /* Get image params stored in the hidden field. */
        $value = $var->getValue($vars);

        /* Check if we have image data */
        if (!isset($this->_img) || !isset($this->_img['img'])) {
            $info = '';
            return $info;
        }

        $info = $this->_img['img'];
        if (empty($info['file'])) {
            unset($info['file']);
            return $info;
        }

        if ($this->_show_keeporig) {
            $info['keep_orig'] = !empty($value['keep_orig']);
        }

        /* Set the uploaded value (either true or Horde_Browser_Exception). */
        $info['uploaded'] = &$this->_uploaded;

        /* If a modified file exists move it over the original. */
        if ($this->_show_keeporig && $info['keep_orig']) {
            /* Requested the saving of original file also. */
            $info['orig_file'] = Horde::getTempDir() . '/' . $info['file'];
            $info['file'] = Horde::getTempDir() . '/mod_' . $info['file'];
            /* Check if a modified file actually exists. */
            if (!file_exists($info['file'])) {
                $info['file'] = $info['orig_file'];
                unset($info['orig_file']);
            }
        } else {
            /* Saving of original not required. */
            $mod_file = Horde::getTempDir() . '/mod_' . $info['file'];
            $info['file'] = Horde::getTempDir() . '/' . $info['file'];

            if (file_exists($mod_file)) {
                /* Unlink first (has to be done on Windows machines?) */
                unlink($info['file']);
                rename($mod_file, $info['file']);
            }
        }
        return $info;
    }

    /**
     * Gets the upload and sets up the upload data array. Either
     * fetches an upload done with this submit or retrieves stored
     * upload info.
     * @param Horde_Variables $vars     The form state to check this field for
     * @param Horde_Form_Variable $var  The Form field object to check
     *
     */
    public function _getUpload($vars, $var)
    {
        global $session;

        /* Don't bother with this function if already called and set
         * up vars. */
        if (!empty($this->_img)) {
            return true;
        }

        /* Check if file has been uploaded. */
        $varname = $var->getVarName();

        try {
            $GLOBALS['browser']->wasFileUploaded($varname . '[new]');
            $this->_uploaded = true;

            /* A file has been uploaded on this submit. Save to temp dir for
             * preview work. */
            $this->_img['img']['type'] = $this->getUploadedFileType($varname . '[new]');

            /* Get the other parts of the upload. */
            Horde_Array::getArrayParts($varname . '[new]', $base, $keys);

            /* Get the temporary file name. */
            $keys_path = array_merge([$base, 'tmp_name'], $keys);
            $this->_img['img']['file'] = Horde_Array::getElement($_FILES, $keys_path);

            /* Get the actual file name. */
            $keys_path = array_merge([$base, 'name'], $keys);
            $this->_img['img']['name'] = Horde_Array::getElement($_FILES, $keys_path);

            /* Get the file size. */
            $keys_path = array_merge([$base, 'size'], $keys);
            $this->_img['img']['size'] = Horde_Array::getElement($_FILES, $keys_path);

            /* Get any existing values for the image upload field. */
            $upload = $vars->get($var->getVarName());
            if (!empty($upload['hash'])) {
                $upload['img'] = $session->get('horde', 'form/' . $upload['hash']);
                $session->remove('horde', 'form/' . $upload['hash']);
                if (!empty($upload['img']['file'])) {
                    $tmp_file = Horde::getTempDir() . '/' . basename($upload['img']['file']);
                } else {
                    $tmp_file = Horde::getTempFile('Horde', false);
                }
            } else {
                $tmp_file = Horde::getTempFile('Horde', false);
            }

            /* Move the browser created temp file to the new temp file. */
            move_uploaded_file($this->_img['img']['file'], $tmp_file);
            $this->_img['img']['file'] = basename($tmp_file);
        } catch (Horde_Browser_Exception $e) {
            $this->_uploaded = $e;

            /* File has not been uploaded. */
            $upload = $vars->get($var->getVarName());

            /* File is explicitly removed */
            if ($vars->get('remove_' . $var->getVarName())) {
                $this->_img = null;
                $session->remove('horde', 'form/' . $upload['hash']);
                return;
            }

            if ($this->_uploaded->getCode() == 4 &&
                !empty($upload['hash']) &&
                $session->exists('horde', 'form/' . $upload['hash'])) {
                $this->_img['img'] = $session->get('horde', 'form/' . $upload['hash']);
                $session->remove('horde', 'form/' . $upload['hash']);
                if (isset($this->_img['error'])) {
                    $this->_uploaded = PEAR::raiseError($this->_img['error']);
                }
            }
        }
        if (isset($this->_img['img'])) {
            $session->set('horde', 'form/' . $this->getRandomId(), $this->_img['img']);
        }
    }

    public function getUploadedFileType($field)
    {
        /* Get any index on the field name. */
        $index = Horde_Array::getArrayParts($field, $base, $keys);

        if ($index) {
            /* Index present, fetch the mime type var to check. */
            $keys_path = array_merge([$base, 'type'], $keys);
            $type = Horde_Array::getElement($_FILES, $keys_path);
            $keys_path = array_merge([$base, 'tmp_name'], $keys);
            $tmp_name = Horde_Array::getElement($_FILES, $keys_path);
        } else {
            /* No index, simple set up of vars to check. */
            $type = $_FILES[$field]['type'];
            $tmp_name = $_FILES[$field]['tmp_name'];
        }

        if (empty($type) || ($type == 'application/octet-stream')) {
            /* Type wasn't set on upload, try analising the upload. */
            if (!($type = Horde_Mime_Magic::analyzeFile($tmp_name, $GLOBALS['conf']['mime']['magic_db'] ?? null))) {
                if ($index) {
                    /* Get the name value. */
                    $keys_path = array_merge([$base, 'name'], $keys);
                    $name = Horde_Array::getElement($_FILES, $keys_path);

                    /* Work out the type from the file name. */
                    $type = Horde_Mime_Magic::filenameToMime($name);

                    /* Set the type. */
                    $keys_path = array_merge([$base, 'type'], $keys);
                    Horde_Array::getElement($_FILES, $keys_path, $type);
                } else {
                    /* Work out the type from the file name. */
                    $type = Horde_Mime_Magic::filenameToMime($_FILES[$field]['name']);

                    /* Set the type. */
                    $_FILES[$field]['type'] = Horde_Mime_Magic::filenameToMime($_FILES[$field]['name']);
                }
            }
        }

        return $type;
    }

    /**
     * Returns the current image information.
     *
     * @param Horde_Variables $vars     The form state to check this field for
     * @param Horde_Form_Variable $var  The Form field object to check
     * @return array  The current image hash.
     */
    public function getImage($vars, $var)
    {
        $this->_getUpload($vars, $var);
        if (!isset($this->_img)) {
            $image = $vars->get($var->getVarName());
            if ($image) {
                $image = $this->loadImageData($image);
                if (isset($image['img'])) {
                    $this->_img = $image;
                    $GLOBALS['session']->set('horde', 'form/' . $this->getRandomId(), $this->_img['img']);
                }
            }
        }
        return $this->_img;
    }

    /**
     * Loads any existing image data into the image field. Requires that the
     * array $image passed to it contains the structure:
     *   $image['load']['file'] - the filename of the image;
     *   $image['load']['data'] - the raw image data.
     *
     * @param array $image  The image array.
     */
    public function loadImageData($image)
    {
        /* No existing image data to load. */
        if (!isset($image['load'])) {
            return;
        }

        /* Save the data to the temp dir. */
        $tmp_file = Horde::getTempDir() . '/' . $image['load']['file'];
        if ($fd = fopen($tmp_file, 'w')) {
            fwrite($fd, $image['load']['data']);
            fclose($fd);
        }

        $image['img'] = ['file' => $image['load']['file']];
        unset($image['load']);
        return $image;
    }

    public function getRandomId()
    {
        if (!isset($this->_random)) {
            $this->_random = uniqid(mt_rand());
        }
        return $this->_random;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Image upload"),
            'params' => [
                'show_upload'   => ['label' => Horde_Form_Translation::t("Show upload?"),
                    'type'  => 'boolean'],
                'show_keeporig' => ['label' => Horde_Form_Translation::t("Show option to keep original?"),
                    'type'  => 'boolean'],
                'max_filesize'  => ['label' => Horde_Form_Translation::t("Maximum file size in bytes"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_boolean extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    public function getInfo($vars, $var, $info)
    {
        $info = Horde_String::lower($vars->get($var->getVarName())) == 'on';
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("True or false")];
    }

}

class Horde_Form_Type_link extends Horde_Form_Type
{
    /**
     * List of hashes containing link parameters. Possible keys: 'url', 'text',
     * 'target', 'onclick', 'title', 'accesskey', 'class'.
     *
     * @var array
     */
    public $values;

    /**
     * Init a Link field
     *
     * function init($values)
     */
    public function init(...$params)
    {
        $this->values = $params[0] ?? null;
    }

    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Link"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type' => 'array']
/*                'url' => [
                    'label' => Horde_Form_Translation::t("Link URL"),
                    'type' => 'text'],
                'text' => [
                    'label' => Horde_Form_Translation::t("Link text"),
                    'type' => 'text'],
                'target' => [
                    'label' => Horde_Form_Translation::t("Link target"),
                    'type' => 'text'],
                'onclick' => [
                    'label' => Horde_Form_Translation::t("Onclick event"),
                    'type' => 'text'],
                'title' => [
                    'label' => Horde_Form_Translation::t("Link title attribute"),
                    'type' => 'text'],
                'accesskey' => [
                    'label' => Horde_Form_Translation::t("Link access key"),
                    'type' => 'text'],
                'class' => [
                    'label' => Horde_Form_Translation::t("Link CSS class"),
                    'type' => 'text'],
*/
            ]
        ];
    }

}

class Horde_Form_Type_email extends Horde_Form_Type
{
    /**
     * Allow multiple addresses?
     *
     * @var boolean
     */
    public $_allow_multi = false;

    /**
     * Protect address from spammers?
     *
     * @var boolean
     */
    public $_strip_domain = false;

    /**
     * Link the email address to the compose page when displaying?
     *
     * @var boolean
     */
    public $_link_compose = false;

    /**
     * Whether to check the domain's SMTP server whether the address exists.
     *
     * @var boolean
     */
    public $_check_smtp = false;

    /**
     * The name to use when linking to the compose page
     *
     * @var boolean
     */
    public $_link_name;

    /**
     * A string containing valid delimiters (default is just comma).
     *
     * @var string
     */
    public $_delimiters = ',';

    /**
     * The size of the input field.
     *
     * @var integer
     */
    public $_size;

    /**
     * Init an "email" field
     *
     * @param bool $allow_multi   Allow multiple addresses?
     * @param bool $strip_domain  Protect address from spammers?
     * @param bool $link_compose  Link the email address to the compose page
     *                               when displaying?
     * @param string $link_name      The name to use when linking to the
     *                               compose page.
     * @param string $delimiters     Character to split multiple addresses with.
     * @param int $size          The size of the input field.
     *    function init($allow_multi = false, $strip_domain = false,
     *             $link_compose = false, $link_name = null,
     *             $delimiters = ',', $size = null)
     * {
     */
    public function init(...$params)
    {
        $this->_allow_multi = $params[0] ?? false;
        $this->_strip_domain = $params[1] ?? false;
        $this->_link_compose = $params[2] ?? false;
        $this->_link_name = $params[3] ?? null;
        $this->_delimiters = $params[4] ?? ',';
        $this->_size = $params[5] ?? null;
    }

    /**
     */
    public function isValid($var, $vars, $value, $message)
    {
        // Split into individual addresses.
        $emails = $this->splitEmailAddresses($value);

        // Check for too many.
        if (!$this->_allow_multi && count($emails) > 1) {
            return $this->invalid('Only one email address is allowed.');
        }

        // Check for all valid and at least one non-empty.
        $nonEmpty = 0;
        foreach ($emails as $email) {
            if (!strlen($email)) {
                continue;
            }
            if (!$this->validateEmailAddress($email)) {
                $this->message = sprintf(Horde_Form_Translation::t('"%s" is not a valid email address.'), htmlspecialchars($email));
                return false;
            }
            ++$nonEmpty;
        }

        if (!$nonEmpty && $var->isRequired()) {
            if ($this->_allow_multi) {
                return $this->invalid('You must enter at least one email address.');
            }
            return $this->invalid('You must enter an email address.');
        }

        return true;
    }

    /**
     * Explodes an RFC 2822 string, ignoring a delimiter if preceded
     * by a "\" character, or if the delimiter is inside single or
     * double quotes.
     *
     * @param string $string     The RFC 822 string.
     *
     * @return array  The exploded string in an array.
     */
    public function splitEmailAddresses($string)
    {
        // Trim off any trailing delimiters
        $string = trim($string, $this->_delimiters . ' ');

        $quotes = ['"', "'"];
        $emails = [];
        $pos = 0;
        $in_quote = null;
        $in_group = false;
        $prev = null;

        if (!strlen($string)) {
            return [];
        }

        $char = $string[0];
        if (in_array($char, $quotes)) {
            $in_quote = $char;
        } elseif ($char == ':') {
            $in_group = true;
        } elseif (strpos($this->_delimiters, $char) !== false) {
            $emails[] = '';
            $pos = 1;
        }

        for ($i = 1, $iMax = strlen($string); $i < $iMax; ++$i) {
            $char = $string[$i];
            if (in_array($char, $quotes)) {
                if ($prev !== '\\') {
                    if ($in_quote === $char) {
                        $in_quote = null;
                    } elseif (is_null($in_quote)) {
                        $in_quote = $char;
                    }
                }
            } elseif ($in_group) {
                if ($char == ';') {
                    $emails[] = substr($string, $pos, $i - $pos + 1);
                    $pos = $i + 1;
                    $in_group = false;
                }
            } elseif ($char == ':') {
                $in_group = true;
            } elseif (strpos($this->_delimiters, $char) !== false &&
                      $prev !== '\\' &&
                      is_null($in_quote)) {
                $emails[] = substr($string, $pos, $i - $pos);
                $pos = $i + 1;
            }
            $prev = $char;
        }

        if ($pos != $i) {
            /* The string ended without a delimiter. */
            $emails[] = substr($string, $pos, $i - $pos);
        }

        return $emails;
    }

    /**
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    public function validateEmailAddress($email)
    {
        $result = $this->_isRfc3696ValidEmailAddress($email);
        if ($result && $this->_check_smtp) {
            $result = $this->validateEmailAddressSmtp($email);
        }

        return $result;
    }

    /**
     * Attempt partial delivery of mail to an address to validate it.
     *
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    public function validateEmailAddressSmtp($email)
    {
        [, $maildomain] = explode('@', $email, 2);

        // Try to get the real mailserver from MX records.
        if (function_exists('getmxrr') &&
            @getmxrr($maildomain, $mxhosts, $mxpriorities)) {
            // MX record found.
            array_multisort($mxpriorities, $mxhosts);
            $mailhost = $mxhosts[0];
        } else {
            // No MX record found, try the root domain as the mail
            // server.
            $mailhost = $maildomain;
        }

        $fp = @fsockopen($mailhost, 25, $errno, $errstr, 5);
        if (!$fp) {
            return false;
        }

        // Read initial response.
        fgets($fp, 4096);

        // HELO
        fputs($fp, "HELO $mailhost\r\n");
        fgets($fp, 4096);

        // MAIL FROM
        fputs($fp, "MAIL FROM: <root@example.com>\r\n");
        fgets($fp, 4096);

        // RCPT TO - gets the result we want.
        fputs($fp, "RCPT TO: <$email>\r\n");
        $result = trim(fgets($fp, 4096));

        // QUIT
        fputs($fp, "QUIT\r\n");
        fgets($fp, 4096);
        fclose($fp);

        return substr($result, 0, 1) == '2';
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function allowMulti()
    {
        return $this->_allow_multi;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Email"),
            'params' => [
                'allow_multi' => [
                    'label' => Horde_Form_Translation::t("Allow multiple addresses?"),
                    'type'  => 'boolean'],
                'strip_domain' => [
                    'label' => Horde_Form_Translation::t("Protect address from spammers?"),
                    'type' => 'boolean'],
                'link_compose' => [
                    'label' => Horde_Form_Translation::t("Link the email address to the compose page when displaying?"),
                    'type' => 'boolean'],
                'link_name' => [
                    'label' => Horde_Form_Translation::t("The name to use when linking to the compose page"),
                    'type' => 'text'],
                'delimiters' => [
                    'label' => Horde_Form_Translation::t("Character to split multiple addresses with"),
                    'type' => 'text'],
                'size' => [
                    'label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
            ],
        ];
    }

    /**
     * RFC3696 Email Parser
     *
     * By Cal Henderson <cal@iamcal.com>
     *
     * This code is dual licensed:
     * CC Attribution-ShareAlike 2.5 - http://creativecommons.org/licenses/by-sa/2.5/
     * GPLv3 - http://www.gnu.org/copyleft/gpl.html
     */
    protected function _isRfc3696ValidEmailAddress($email)
    {
        ####################################################################################
        #
        # NO-WS-CTL       =       %d1-8 /         ; US-ASCII control characters
        #                         %d11 /          ;  that do not include the
        #                         %d12 /          ;  carriage return, line feed,
        #                         %d14-31 /       ;  and white space characters
        #                         %d127
        # ALPHA          =  %x41-5A / %x61-7A   ; A-Z / a-z
        # DIGIT          =  %x30-39

        $no_ws_ctl  = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
        $alpha      = "[\\x41-\\x5a\\x61-\\x7a]";
        $digit      = "[\\x30-\\x39]";
        $cr     = "\\x0d";
        $lf     = "\\x0a";
        $crlf       = "(?:$cr$lf)";


        ####################################################################################
        #
        # obs-char        =       %d0-9 / %d11 /          ; %d0-127 except CR and
        #                         %d12 / %d14-127         ;  LF
        # obs-text        =       *LF *CR *(obs-char *LF *CR)
        # text            =       %d1-9 /         ; Characters excluding CR and LF
        #                         %d11 /
        #                         %d12 /
        #                         %d14-127 /
        #                         obs-text
        # obs-qp          =       "\" (%d0-127)
        # quoted-pair     =       ("\" text) / obs-qp

        $obs_char   = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
        $obs_text   = "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
        $text       = "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";

        #
        # there's an issue with the definition of 'text', since 'obs_text' can
        # be blank and that allows qp's with no character after the slash. we're
        # treating that as bad, so this just checks we have at least one
        # (non-CRLF) character
        #

        $text       = "(?:$lf*$cr*$obs_char$lf*$cr*)";
        $obs_qp     = "(?:\\x5c[\\x00-\\x7f])";
        $quoted_pair    = "(?:\\x5c$text|$obs_qp)";


        ####################################################################################
        #
        # obs-FWS         =       1*WSP *(CRLF 1*WSP)
        # FWS             =       ([*WSP CRLF] 1*WSP) /   ; Folding white space
        #                         obs-FWS
        # ctext           =       NO-WS-CTL /     ; Non white space controls
        #                         %d33-39 /       ; The rest of the US-ASCII
        #                         %d42-91 /       ;  characters not including "(",
        #                         %d93-126        ;  ")", or "\"
        # ccontent        =       ctext / quoted-pair / comment
        # comment         =       "(" *([FWS] ccontent) [FWS] ")"
        # CFWS            =       *([FWS] comment) (([FWS] comment) / FWS)

        #
        # note: we translate ccontent only partially to avoid an infinite loop
        # instead, we'll recursively strip *nested* comments before processing
        # the input. that will leave 'plain old comments' to be matched during
        # the main parse.
        #

        $wsp        = "[\\x20\\x09]";
        $obs_fws    = "(?:$wsp+(?:$crlf$wsp+)*)";
        $fws        = "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
        $ctext      = "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
        $ccontent   = "(?:$ctext|$quoted_pair)";
        $comment    = "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
        $cfws       = "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";


        #
        # these are the rules for removing *nested* comments. we'll just detect
        # outer comment and replace it with an empty comment, and recurse until
        # we stop.
        #

        $outer_ccontent_dull    = "(?:$fws?$ctext|$quoted_pair)";
        $outer_ccontent_nest    = "(?:$fws?$comment)";
        $outer_comment      = "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";


        ####################################################################################
        #
        # atext           =       ALPHA / DIGIT / ; Any character except controls,
        #                         "!" / "#" /     ;  SP, and specials.
        #                         "$" / "%" /     ;  Used for atoms
        #                         "&" / "'" /
        #                         "*" / "+" /
        #                         "-" / "/" /
        #                         "=" / "?" /
        #                         "^" / "_" /
        #                         "`" / "{" /
        #                         "|" / "}" /
        #                         "~"
        # atom            =       [CFWS] 1*atext [CFWS]

        $atext      = "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
        $atom       = "(?:$cfws?(?:$atext)+$cfws?)";


        ####################################################################################
        #
        # qtext           =       NO-WS-CTL /     ; Non white space controls
        #                         %d33 /          ; The rest of the US-ASCII
        #                         %d35-91 /       ;  characters not including "\"
        #                         %d93-126        ;  or the quote character
        # qcontent        =       qtext / quoted-pair
        # quoted-string   =       [CFWS]
        #                         DQUOTE *([FWS] qcontent) [FWS] DQUOTE
        #                         [CFWS]
        # word            =       atom / quoted-string

        $qtext      = "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
        $qcontent   = "(?:$qtext|$quoted_pair)";
        $quoted_string  = "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";

        #
        # changed the '*' to a '+' to require that quoted strings are not empty
        #

        $quoted_string  = "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
        $word       = "(?:$atom|$quoted_string)";


        ####################################################################################
        #
        # obs-local-part  =       word *("." word)
        # obs-domain      =       atom *("." atom)

        $obs_local_part = "(?:$word(?:\\x2e$word)*)";
        $obs_domain = "(?:$atom(?:\\x2e$atom)*)";


        ####################################################################################
        #
        # dot-atom-text   =       1*atext *("." 1*atext)
        # dot-atom        =       [CFWS] dot-atom-text [CFWS]

        $dot_atom_text  = "(?:$atext+(?:\\x2e$atext+)*)";
        $dot_atom   = "(?:$cfws?$dot_atom_text$cfws?)";


        ####################################################################################
        #
        # domain-literal  =       [CFWS] "[" *([FWS] dcontent) [FWS] "]" [CFWS]
        # dcontent        =       dtext / quoted-pair
        # dtext           =       NO-WS-CTL /     ; Non white space controls
        #
        #                         %d33-90 /       ; The rest of the US-ASCII
        #                         %d94-126        ;  characters not including "[",
        #                                         ;  "]", or "\"

        $dtext      = "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
        $dcontent   = "(?:$dtext|$quoted_pair)";
        $domain_literal = "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";


        ####################################################################################
        #
        # local-part      =       dot-atom / quoted-string / obs-local-part
        # domain          =       dot-atom / domain-literal / obs-domain
        # addr-spec       =       local-part "@" domain

        $local_part = "(($dot_atom)|($quoted_string)|($obs_local_part))";
        $domain     = "(($dot_atom)|($domain_literal)|($obs_domain))";
        $addr_spec  = "$local_part\\x40$domain";



        #
        # see http://www.dominicsayers.com/isemail/ for details, but this should probably be 254
        #

        if (strlen($email) > 256) {
            return 0;
        }


        #
        # we need to strip nested comments first - we replace them with a simple comment
        #

        $email = $this->_rfc3696StripComments($outer_comment, $email, "(x)");


        #
        # now match what's left
        #

        if (!preg_match("!^$addr_spec$!", $email, $m)) {

            return 0;
        }

        $bits = [
            'local'         => $m[1] ?? '',
            'local-atom'        => $m[2] ?? '',
            'local-quoted'      => $m[3] ?? '',
            'local-obs'     => $m[4] ?? '',
            'domain'        => $m[5] ?? '',
            'domain-atom'       => $m[6] ?? '',
            'domain-literal'    => $m[7] ?? '',
            'domain-obs'        => $m[8] ?? '',
        ];


        #
        # we need to now strip comments from $bits[local] and $bits[domain],
        # since we know they're i the right place and we want them out of the
        # way for checking IPs, label sizes, etc
        #

        $bits['local']  = $this->_rfc3696StripComments($comment, $bits['local']);
        $bits['domain'] = $this->_rfc3696StripComments($comment, $bits['domain']);


        #
        # length limits on segments
        #

        if (strlen($bits['local']) > 64) {
            return 0;
        }
        if (strlen($bits['domain']) > 255) {
            return 0;
        }


        #
        # restrictions on domain-literals from RFC2821 section 4.1.3
        #

        if (strlen($bits['domain-literal'])) {

            $Snum           = "(\d{1,3})";
            $IPv4_address_literal   = "$Snum\.$Snum\.$Snum\.$Snum";

            $IPv6_hex       = "(?:[0-9a-fA-F]{1,4})";

            $IPv6_full      = "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";

            $IPv6_comp_part     = "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
            $IPv6_comp      = "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";

            $IPv6v4_full        = "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";

            $IPv6v4_comp_part   = "$IPv6_hex(?:\:$IPv6_hex){0,3}";
            $IPv6v4_comp        = "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";


            #
            # IPv4 is simple
            #

            if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)) {
                if (intval($m[1]) > 255) {
                    return 0;
                }
                if (intval($m[2]) > 255) {
                    return 0;
                }
                if (intval($m[3]) > 255) {
                    return 0;
                }
                if (intval($m[4]) > 255) {
                    return 0;
                }
            } else {
                #
                # this should be IPv6 - a bunch of tests are needed here :)
                #

                while (1) {

                    if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])) {
                        break;
                    }

                    if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)) {
                        [$a, $b] = explode('::', $m[1]);
                        $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                        $groups = explode(':', $folded);
                        if (count($groups) > 6) {
                            return 0;
                        }
                        break;
                    }

                    if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)) {
                        if (intval($m[1]) > 255) {
                            return 0;
                        }
                        if (intval($m[2]) > 255) {
                            return 0;
                        }
                        if (intval($m[3]) > 255) {
                            return 0;
                        }
                        if (intval($m[4]) > 255) {
                            return 0;
                        }
                        break;
                    }

                    if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)) {
                        [$a, $b] = explode('::', $m[1]);
                        $b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
                        $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                        $groups = explode(':', $folded);
                        if (count($groups) > 4) {
                            return 0;
                        }
                        break;
                    }

                    return 0;
                }
            }
        } else {
            #
            # the domain is either dot-atom or obs-domain - either way, it's
            # made up of simple labels and we split on dots
            #

            $labels = explode('.', $bits['domain']);


            #
            # this is allowed by both dot-atom and obs-domain, but is un-routeable on the
            # public internet, so we'll fail it (e.g. user@localhost)
            #

            if (count($labels) == 1) {
                return 0;
            }


            #
            # checks on each label
            #

            foreach ($labels as $label) {
                if (strlen($label) > 63) {
                    return 0;
                }
                if (substr($label, 0, 1) == '-') {
                    return 0;
                }
                if (substr($label, -1) == '-') {
                    return 0;
                }
            }


            #
            # last label can't be all numeric
            #

            if (preg_match('!^[0-9]+$!', array_pop($labels))) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * RFC3696 Email Parser
     *
     * By Cal Henderson <cal@iamcal.com>
     *
     * This code is dual licensed:
     * CC Attribution-ShareAlike 2.5 - http://creativecommons.org/licenses/by-sa/2.5/
     * GPLv3 - http://www.gnu.org/copyleft/gpl.html
     *
     * $Revision: 5039 $
     */
    protected function _rfc3696StripComments($comment, $email, $replace = '')
    {
        while (1) {
            $new = preg_replace("!$comment!", $replace, $email);
            if (strlen($new) == strlen($email)) {
                return $email;
            }
            $email = $new;
        }
    }
}

class Horde_Form_Type_matrix extends Horde_Form_Type
{
    public $_cols;
    public $_rows;
    public $_matrix;
    public $_new_input;

    /**
     * Initializes the variable.
     *
     * Example:
     * <code>
     * init(array('Column A', 'Column B'),
     *      array(1 => 'Row One', 2 => 'Row 2', 3 => 'Row 3'),
     *      array(array(true, true, false),
     *            array(true, false, true),
     *            array(fasle, true, false)),
     *      array('Row 4', 'Row 5'));
     * </code>
     * function init($cols, $rows = array(), $matrix = array(), $new_input = false)
     *
     * @param array $cols               A list of column headers.
     * @param array $rows               A hash with row IDs as the keys and row
     *                                  labels as the values.
     * @param array $matrix             A two dimensional hash with the field
     *                                  values.
     * @param bool|array $new_input  If true, a free text field to add a new
     *                                  row is displayed on the top, a select
     *                                  box if this parameter is a value.
     */
    public function init(...$params)
    {
        $this->_cols       = $params[0] ?? [];
        $this->_rows       = $params[1] ?? [];
        $this->_matrix     = $params[2] ?? [];
        $this->_new_input  = $params[3] ?? false;
    }

    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    public function getCols()
    {
        return $this->_cols;
    }
    public function getRows()
    {
        return $this->_rows;
    }
    public function getMatrix()
    {
        return $this->_matrix;
    }
    public function getNewInput()
    {
        return $this->_new_input;
    }

    public function getInfo($vars, $var, $info)
    {
        $values = $vars->get($var->getVarName());
        if (!empty($values['n']['r']) && isset($values['n']['v'])) {
            $new_row = $values['n']['r'];
            $values['r'][$new_row] = $values['n']['v'];
            unset($values['n']);
        }

        return $values['r'] ?? [];
    }

    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Field matrix"),
            'params' => [
                'cols' => [
                    'label' => Horde_Form_Translation::t("Column titles"),
                    'type'  => 'stringarray'
                ],
                'rows' => [
                    'label' => Horde_Form_Translation::t("Row titles"),
                    'type'  => 'stringarray'
                ],
                'matrix' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'
                ],
                'new_input' => [
                    'label' => Horde_Form_Translation::t("New Input"),
                    'type'  => 'boolean'
                ]
            ]
        ];
    }

}

class Horde_Form_Type_emailConfirm extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value['original'])) {
            return $this->invalid('This field is required.');
        }

        if ($value['original'] != $value['confirm']) {
            return $this->invalid('Email addresses must match.');
        }

        $addr_ob = $GLOBALS['injector']->getInstance('Horde_Mail_Rfc822')->parseAddressList($value['original']);

        switch (count($addr_ob)) {
            case 0:
                return $this->invalid('You did not enter a valid email address.');

            case 1:
                return true;

            default:
                return $this->invalid('Only one email address allowed.');
        }
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Email with confirmation")];
    }

}

class Horde_Form_Type_password extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && strlen(trim($value)) == 0) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Password")];
    }

}

class Horde_Form_Type_passwordconfirm extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value['original'])) {
            return $this->invalid('This field is required.');
        }

        if ($value['original'] != $value['confirm']) {
            return $this->invalid('Passwords must match.');
        }

        return true;
    }

    public function getInfo($vars, $var, $info)
    {
        $value = $vars->get($var->getVarName());
        $info = $value['original'];
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Password with confirmation")];
    }

}
/**
 * Horde_Form_Type for selecting a single value out of a list
 * For selecting multiple values, use Horde_Form_Type_multienum
 */
class Horde_Form_Type_enum extends Horde_Form_Type
{
    public $_values;
    public $_prompt;
    /**
     * Initialize (kind of constructor)
     *
     * function init($values, $prompt = null)
     *
     * @param array $values            A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
     * @param string|bool  $prompt  A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.
     */
    public function init(...$params)
    {
        $this->setValues($params[0] ?? []);
        $prompt = $params[1] ?? false;

        if ($prompt === true) {
            $this->_prompt = Horde_Form_Translation::t("-- select --");
        } else {
            $this->_prompt = $prompt;
        }
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && $value == '' && !isset($this->_values[$value])) {
            return $this->invalid('This field is required.');
        }

        if (count($this->_values) == 0 || isset($this->_values[$value]) ||
            ($this->_prompt && empty($value))) {
            return true;
        }

        return $this->invalid('Invalid data submitted.');
    }

    public function getValues(...$params)
    {
        return $this->_values;
    }

    public function setValues($values)
    {
        $this->_values = $values;
    }

    public function getPrompt()
    {
        return $this->_prompt;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Drop down list"),
            'params' => [
                'values' => ['label' => Horde_Form_Translation::t("Values to select from"),
                    'type'  => 'stringarray'],
                'prompt' => ['label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text']]];
    }

}

class Horde_Form_Type_mlenum extends Horde_Form_Type
{
    public $_values;
    public $_prompts;

    /**
     * Initialize an mlenum field
     *
     * function init($values, $prompts = null)
     */
    public function init(...$params)
    {
        $this->_values = &$params[0];
        $prompts = $params[1] ?? null;

        if ($prompts === true) {
            $this->_prompts = [Horde_Form_Translation::t("-- select --"), Horde_Form_Translation::t("-- select --")];
        } elseif (!is_array($prompts)) {
            $this->_prompts = [$prompts, $prompts];
        } else {
            $this->_prompts = $prompts;
        }
    }

    /**
     *     function onSubmit($var, $vars)
     */
    public function onSubmit($var, $vars)
    {
        $varname = $var->getVarName();
        $value = $vars->get($varname);

        if ($value['1'] != $value['old']) {
            $var->form->setSubmitted(false);
        }
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && (empty($value['1']) || empty($value['2']))) {
            return $this->invalid('This field is required.');
        }

        if (!count($this->_values) || isset($this->_values[$value['1']]) ||
            (!empty($this->_prompts) && empty($value['1']))) {
            return true;
        }

        return $this->invalid('Invalid data submitted.');
    }

    public function getValues(...$params)
    {
        return $this->_values;
    }

    public function getPrompts()
    {
        return $this->_prompts;
    }

    public function getInfo($vars, $var, $info)
    {
        $info = $vars->get($var->getVarName());
        return $info['2'];
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Multi-level drop down lists"),
            'params' => [
                'values' => ['label' => Horde_Form_Translation::t("Values to select from"),
                    'type'  => 'stringarray'],
                'prompts' => ['label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text']]];
    }

}


/**
 * A Horde_Form_Type_multienum for a multiselect box
 * @see Horde_Form_Type_enum
 */
class Horde_Form_Type_multienum extends Horde_Form_Type_enum
{
    public $size = 5;

    /**
     * Initialize (kind of constructor)
     *
     * function init($values, $size = null)
     *
     * @param array $values  A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
     * @param int $size  The number of rows the multienum should display before scrolling
     */
    public function init(...$params)
    {
        $values = $params[0] ?? [];
        $size = $params[1] ?? null;

        if (!is_null($size)) {
            $this->size = (int) $size;
        }

        parent::init($values);
    }

    public function isValid($var, $vars, $value, $message)
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                if (!$this->isValid($var, $vars, $val, $message)) {
                    return false;
                }
            }
            return true;
        }

        if (empty($value) && ((string) (int) $value !== $value)) {
            if ($var->isRequired()) {
                return $this->invalid('This field is required.');
            }
            return true;
        }

        if (count($this->_values) == 0 || isset($this->_values[$value])) {
            return true;
        }

        return $this->invalid('Invalid data submitted.');
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Multiple selection"),
            'params' => [
                'values' => ['label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'],
                'size'   => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int']],
        ];
    }

}

class Horde_Form_Type_keyval_multienum extends Horde_Form_Type_multienum
{
    public function getInfo($vars, $var, $info)
    {
        $value = $vars->get($var->getVarName());
        $info = [];
        foreach ($value as $key) {
            $info[$key] = $this->_values[$key];
        }
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        $about = parent::about();
        $about['name'] = Horde_Form_Translation::t("Multiple selection, preserving keys");
    }

}

class Horde_Form_Type_radio extends Horde_Form_Type_enum
{
    /* Entirely implemented by Horde_Form_Type_enum; just a different
     * view. */

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Radio selection"),
            'params' => [
                'values' => ['label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray']]];
    }

}

class Horde_Form_Type_set extends Horde_Form_Type
{
    public $_values;
    public $_checkAll = false;

    /**
     * Initialize a Set form type
     *
     * function init($values, $checkAll = false)
     */
    public function init(...$params)
    {
        $this->_values = $params[0];
        $this->_checkAll = $params[1] ?? false;
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ((!is_null($this->_values) && count($this->_values) == 0) || is_null($value) || count($value) == 0) {
            return true;
        }

        foreach ($value as $item) {
            if (!isset($this->_values[$item])) {
                return $this->invalid('Invalid data submitted.');
            }
        }

        return true;
    }

    public function getValues(...$params)
    {
        return $this->_values;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Set"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'
                ],
                'checkAll' => [
                    'label' => Horde_Form_Translation::t("Check all"),
                    'type'  => 'boolean'
                ]
            ]
        ];
    }

}

class Horde_Form_Type_date extends Horde_Form_Type
{
    public $_format;

    /**
     * Initialize a Set form type
     *
     * function init($format = '%a %d %B')
     */
    public function init(...$params)
    {
        $this->_format = $params[0] ?? '%a %d %B';
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && strlen(trim($value)) == 0) {
            $this->message = sprintf(Horde_Form_Translation::t("%s is required"), $var->getHumanName());
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
    public function getAgo($date)
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
        } elseif ($ago == -1) {
            return Horde_Form_Translation::t(" (yesterday)");
        } elseif ($ago == 0) {
            return Horde_Form_Translation::t(" (today)");
        } elseif ($ago == 1) {
            return Horde_Form_Translation::t(" (tomorrow)");
        } else {
            return sprintf(Horde_Form_Translation::t(" (in %s days)"), $ago);
        }
    }

    public function getFormattedTime($timestamp, $format = null, $showago = true)
    {
        if (empty($format)) {
            $format = $this->_format;
        }
        if (!empty($timestamp)) {
            return strftime($format, $timestamp) . ($showago ? Horde_Form_Type_date::getAgo($timestamp) : '');
        } else {
            return '';
        }
    }

    /**
     * Return info about field type.
     */
    public function about()
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

class Horde_Form_Type_time extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value) && ((string) (float) $value !== $value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || preg_match('/^[0-2]?[0-9]:[0-5][0-9]$/', $value)) {
            return true;
        }

        return $this->invalid('This field may only contain numbers and the colon.');
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Time")];
    }

}

class Horde_Form_Type_hourminutesecond extends Horde_Form_Type
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

    public function isValid($var, $vars, $value, $message)
    {
        $time = $vars->get($var->getVarName());
        if (!$this->_show_seconds && count($time) && !isset($time['second'])) {
            $time['second'] = 0;
        }

        if (!$this->emptyTimeArray($time) && !$this->checktime($time['hour'], $time['minute'], $time['second'])) {
            return $this->invalid('Please enter a valid time.');
        }

        if ($this->emptyTimeArray($time) && $var->isRequired()) {
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
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Time selection"),
            'params' => [
                'show_seconds' => ['label' => Horde_Form_Translation::t("Show seconds?"),
                    'type'  => 'boolean']]];
    }

}

class Horde_Form_Type_monthyear extends Horde_Form_Type
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

    public function isValid($var, $vars, $value, $message)
    {
        if (!$var->isRequired()) {
            return true;
        }

        if (!$vars->get($this->getMonthVar($var)) ||
            !$vars->get($this->getYearVar($var))) {
            return $this->invalid('Please enter a month and a year.');
        }

        return true;
    }

    public function getMonthVar($var)
    {
        return $var->getVarName() . '[month]';
    }

    public function getYearVar($var)
    {
        return $var->getVarName() . '[year]';
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Month and year"),
            'params' => [
                'start_year' => ['label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int'],
                'end_year'   => ['label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int']]];
    }

}

class Horde_Form_Type_monthdayyear extends Horde_Form_Type
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

    public function isValid($var, $vars, $value, $message)
    {
        $date = $vars->get($var->getVarName());
        $empty = $this->emptyDateArray($date);

        if ($empty == 1 && $var->isRequired()) {
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
    public function getInfo($vars, $var, $info)
    {
        $info = $this->_validateAndFormat($var->getValue($vars), $var);
        return $info;
    }

    /**
     * Validate/format a date submission.
     */
    public function _validateAndFormat($value, $var)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        if ($this->emptyDateArray($value) == 1) {
            $value = $var->getDefault();
        }

        // If any component is empty consider it a bad date and return null
        if ($this->emptyDateArray($value) != 0) {
            return null;
        } else {
            $date = $this->getDateOb($value);
            if (!strlen($this->_format_in)) {
                return $date->timestamp();
            } else {
                return $date->strftime($this->_format_in);
            }
        }
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Date selection"),
            'params' => [
                'start_year' => ['label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int'],
                'end_year'   => ['label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int'],
                'picker'     => ['label' => Horde_Form_Translation::t("Show picker?"),
                    'type'  => 'boolean'],
                'format_in'  => ['label' => Horde_Form_Translation::t("Storage format"),
                    'type'  => 'text'],
                'format_out' => ['label' => Horde_Form_Translation::t("Display format"),
                    'type'  => 'text']]];
    }

}

class Horde_Form_Type_datetime extends Horde_Form_Type
{
    public $_mdy;
    public $_hms;
    public $_show_seconds;

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * function init($start_year = '', $end_year = '', $picker = true,
     * $format_in = null, $format_out = '%x', $show_seconds = false)
     *
     * @param int  $start_year  The first available year for input.
     * @param int  $end_year    The last available year for input.
     * @param bool $picker      Do we show the DHTML calendar?
     * @param int  $format_in   The format to use when sending the date
     *                             for storage. Defaults to Unix epoch.
     *                             Similar to the strftime() function.
     * @param int  $format_out  The format to use when displaying the
     *                             date. Similar to the strftime() function.
     * @param bool $show_seconds Include a form input for seconds.
     */
    public function init(...$params)
    {
        $start_year = $params[0] ?? '';
        $end_year = $params[1] ?? '';
        $picker = $params[2] ?? true;
        $format_in = $params[3] ?? null;
        $format_out = $params[4] ?? '%x';
        $show_seconds = $params[5] ?? false;

        $this->_mdy = new Horde_Form_Type_monthdayyear();
        $this->_mdy->init($start_year, $end_year, $picker, $format_in, $format_out);

        $this->_hms = new Horde_Form_Type_hourminutesecond();
        $this->_hms->init($show_seconds);
        $this->_show_seconds = $show_seconds;
    }

    public function isValid($var, $vars, $value, $message)
    {
        $date = $vars->get($var->getVarName());
        if (!$this->_show_seconds && !isset($date['second'])) {
            $date['second'] = '';
        }
        $mdy_empty = $this->emptyDateArray($date);
        $hms_empty = $this->emptyTimeArray($date);

        /* Require all fields if one field is not empty */
        if ($var->isRequired() || $mdy_empty != 1 || !$hms_empty) {
            $old_required = $var->required;
            $var->required = true;

            $mdy_valid = $this->_mdy->isValid($var, $vars, $value, $message);
            $hms_valid = $this->_hms->isValid($var, $vars, $value, $message);
            $var->required = $old_required;

            if (!$mdy_valid) {
                return $this->invalid('You must choose a date.');
            }

            if (!$hms_valid) {
                return $this->invalid('You must choose a time.');
            }
        }

        return true;
    }

    public function getInfo($vars, $var, $info)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        $value = $var->getValue($vars);
        if ($this->emptyDateArray($value) == 1 || $this->emptyTimeArray($value)) {
            return $this->_getInfo($var->getDefault(), $info);
        }

        return $this->_getInfo($value, $info);
    }

    public function _getInfo($value, $info)
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
        } else {
            return $this->_mdy->getProperty($property);
        }
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

    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Date and time selection"),
            'params' => [
                'start_year' => ['label' => Horde_Form_Translation::t("Start year"),
                    'type'  => 'int'],
                'end_year'   => ['label' => Horde_Form_Translation::t("End year"),
                    'type'  => 'int'],
                'picker'     => ['label' => Horde_Form_Translation::t("Show picker?"),
                    'type'  => 'boolean'],
                'format_in'  => ['label' => Horde_Form_Translation::t("Storage format"),
                    'type'  => 'text'],
                'format_out' => ['label' => Horde_Form_Translation::t("Display format"),
                    'type'  => 'text'],
                'show_seconds' => ['label' => Horde_Form_Translation::t("Show seconds?"),
                    'type'  => 'boolean']]];
    }

}

class Horde_Form_Type_colorpicker extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || preg_match('/^#([0-9a-z]){6}$/i', $value)) {
            return true;
        }

        return $this->invalid("This field must contain a color code in the RGB Hex format, for example '#1234af'.");
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Colour selection")];
    }

}

class Horde_Form_Type_sound extends Horde_Form_Type
{
    public $_sounds = [];

    public function init(...$params)
    {
        $this->_sounds = array_keys(Horde_Themes::soundList());
    }

    public function getSounds()
    {
        return $this->_sounds;
    }

    public function isValid($var, $vars, $value, $message)
    {
        if ($var->isRequired() && empty($value)) {
            return $this->invalid('This field is required.');
        }

        if (empty($value) || in_array($value, $this->_sounds)) {
            return true;
        }

        return $this->invalid('Please choose a sound.');
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Sound selection")];
    }

}

class Horde_Form_Type_sorter extends Horde_Form_Type
{
    public $_instance;
    public $_values;
    public $_size;
    public $_header;

    /**
     *     function init($values, $size = 8, $header = '')
     */
    public function init(...$params)
    {
        $values = $params[0];
        $size = $params[1] ?? 8;
        $header = $params[2] ?? '';

        static $horde_sorter_instance = 0;

        /* Get the next progressive instance count for the horde
         * sorter so that multiple sorters can be used on one page. */
        $horde_sorter_instance++;
        $this->_instance = 'horde_sorter_' . $horde_sorter_instance;
        $this->_values = $values;
        $this->_size   = $size;
        $this->_header = $header;
    }

    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    public function getValues(...$params)
    {
        return $this->_values;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getHeader()
    {
        if (!empty($this->_header)) {
            return $this->_header;
        }
        return '';
    }

    public function getOptions($keys = null)
    {
        $html = '';
        if ($this->_header) {
            $html .= '<option value="">' . htmlspecialchars($this->_header) . '</option>';
        }

        if (empty($keys)) {
            $keys = array_keys($this->_values);
        } else {
            $keys = explode("\t", $keys['array']);
        }
        foreach ($keys as $sl_key) {
            $html .= '<option value="' . $sl_key . '">' . htmlspecialchars($this->_values[$sl_key]) . '</option>';
        }

        return $html;
    }

    public function getInfo($vars, $var, $info)
    {
        $value = $vars->get($var->getVarName());
        $info = explode("\t", $value['array']);
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Sort order selection"),
            'params' => [
                'values' => ['label' => Horde_Form_Translation::t("Values"),
                    'type'  => 'stringarray'],
                'size'   => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
                'header' => ['label' => Horde_Form_Translation::t("Header"),
                    'type'  => 'text']]];
    }

}

class Horde_Form_Type_selectfiles extends Horde_Form_Type
{
    /**
     * The text to use in the link.
     *
     * @var string
     */
    public $_link_text;

    /**
     * The style to use for the link.
     *
     * @var string
     */
    public $_link_style;

    /**
     *  Create the link with an icon instead of text?
     *
     * @var boolean
     */
    public $_icon;

    /**
     * Contains gollem selectfile selectionID
     *
     * @var string
     */
    public $_selectid;

    /**
     * Initialize a file selection type
     *
     * function init($selectid, $link_text = null, $link_style = '',
     *      $icon = false)
     */
    public function init(...$params)
    {
        $this->_selectid = $params[0];
        $link_text = $params[1] ?? null;
        $link_style = $params[2] ?? '';
        $icon = $params[3] ?? false;

        if (is_null($link_text)) {
            $link_text = Horde_Form_Translation::t("Select Files");
        }
        $this->_link_text = $link_text;
        $this->_link_style = $link_style;
        $this->_icon = $icon;
    }

    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    public function getInfo($var, $vars, $info)
    {
        $value = $vars->getValue($var);
        $info = $GLOBALS['registry']->call('files/selectlistResults', [$value]);
        return $info;
    }

    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("File selection"),
            'params' => [
                'selectid'   => ['label' => Horde_Form_Translation::t("Id"),
                    'type' => 'text'],
                'link_text'  => ['label' => Horde_Form_Translation::t("Link text"),
                    'type' => 'text'],
                'link_style' => ['label' => Horde_Form_Translation::t("Link style"),
                    'type' => 'text'],
                'icon'       => ['label' => Horde_Form_Translation::t("Show icon?"),
                    'type' => 'boolean']]];
    }

}

class Horde_Form_Type_assign extends Horde_Form_Type
{
    public $_leftValues;
    public $_rightValues;
    public $_leftHeader;
    public $_rightHeader;
    public $_size;
    public $_width;

    /**
     * Initialize an assignment field
     *
     * function init($leftValues, $rightValues, $leftHeader = '',
     *     $rightHeader = '', $size = 8, $width = '200px')
     */
    public function init(...$params)
    {
        $this->_leftValues = $params[0];
        $this->_rightValues = $params[1];
        $this->_leftHeader = $params[2] ?? '';
        $this->_rightHeader = $params[3] ?? '';
        $this->_size = $params[4] ?? 8;
        $this->_width = $params[5] ?? '200px';
    }

    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     *     function getValues($side)
     */
    public function getValues(...$params)
    {
        return empty($params[0]) ? $this->_rightValues : $this->_leftValues;
    }

    public function setValues($side, $values)
    {
        if ($side) {
            $this->_rightValues = $values;
        } else {
            $this->_leftValues = $values;
        }
    }

    public function getHeader($side)
    {
        return $side ? $this->_rightHeader : $this->_leftHeader;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getWidth()
    {
        return $this->_width;
    }

    public function getOptions($side, $formname, $varname)
    {
        $html = '';
        $headers = false;
        if ($side) {
            $values = $this->_rightValues;
            if (!empty($this->_rightHeader)) {
                $values = ['' => $this->_rightHeader] + $values;
                $headers = true;
            }
        } else {
            $values = $this->_leftValues;
            if (!empty($this->_leftHeader)) {
                $values = ['' => $this->_leftHeader] + $values;
                $headers = true;
            }
        }

        foreach ($values as $key => $val) {
            $html .= '<option value="' . htmlspecialchars($key) . '"';
            if ($headers) {
                $headers = false;
            } else {
                $html .= ' ondblclick="Horde_Form_Assign.move(\'' . $formname . '\', \'' . $varname . '\', ' . (int) $side . ');"';
            }
            $html .= '>' . htmlspecialchars($val) . '</option>';
        }

        return $html;
    }

    public function getInfo($vars, $var, $info)
    {
        $value = $vars->get($var->getVarName() . '__values');
        if (strpos($value, "\t\t") === false) {
            $left = $value;
            $right = '';
        } else {
            [$left, $right] = explode("\t\t", $value);
        }
        if (empty($left)) {
            $info['left'] = [];
        } else {
            $info['left'] = explode("\t", $left);
        }
        if (empty($right)) {
            $info['right'] = [];
        } else {
            $info['right'] = explode("\t", $right);
        }
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Assignment columns"),
            'params' => [
                'leftValues'  => ['label' => Horde_Form_Translation::t("Left values"),
                    'type'  => 'stringarray'],
                'rightValues' => ['label' => Horde_Form_Translation::t("Right values"),
                    'type'  => 'stringarray'],
                'leftHeader'  => ['label' => Horde_Form_Translation::t("Left header"),
                    'type'  => 'text'],
                'rightHeader' => ['label' => Horde_Form_Translation::t("Right header"),
                    'type'  => 'text'],
                'size'        => ['label' => Horde_Form_Translation::t("Size"),
                    'type'  => 'int'],
                'width'       => ['label' => Horde_Form_Translation::t("Width in CSS units"),
                    'type'  => 'text']]];
    }

}

class Horde_Form_Type_creditcard extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        if (empty($value) && $var->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if (!empty($value)) {
            /* getCardType() will also verify the checksum. */
            $type = $this->getCardType($value);
            if ($type === false || $type == 'unknown') {
                return $this->invalid('This does not seem to be a valid card number.');
            }
        }

        return true;
    }

    public function getChecksum($ccnum)
    {
        $len = strlen($ccnum);
        if (!is_long($len / 2)) {
            $weight = 2;
            $digit = $ccnum[0];
        } elseif (is_long($len / 2)) {
            $weight = 1;
            $digit = $ccnum[0] * 2;
        }
        if ($digit > 9) {
            $digit = $digit - 9;
        }
        $i = 1;
        $checksum = $digit;
        while ($i < $len) {
            if ($ccnum[$i] != ' ') {
                $digit = $ccnum[$i] * $weight;
                $weight = ($weight == 1) ? 2 : 1;
                if ($digit > 9) {
                    $digit = $digit - 9;
                }
                $checksum += $digit;
            }
            $i++;
        }

        return $checksum;
    }

    public function getCardType($ccnum)
    {
        $sum = $this->getChecksum($ccnum);
        $l = strlen($ccnum);

        // Screen checksum.
        if (($sum % 10) != 0) {
            return false;
        }

        // Check for Visa.
        if ((($l == 16) || ($l == 13)) &&
            ($ccnum[0] == 4)) {
            return 'visa';
        }

        // Check for MasterCard.
        if (($l == 16) &&
            ($ccnum[0] == 5) &&
            ($ccnum[1] >= 1) &&
            ($ccnum[1] <= 5)) {
            return 'mastercard';
        }

        // Check for Amex.
        if (($l == 15) &&
            ($ccnum[0] == 3) &&
            (($ccnum[1] == 4) || ($ccnum[1] == 7))) {
            return 'amex';
        }

        // Check for Discover (Novus).
        if (strlen($ccnum) == 16 &&
            substr($ccnum, 0, 4) == '6011') {
            return 'discover';
        }

        // If we got this far, then no card matched.
        return 'unknown';
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Credit card number")];
    }

}

class Horde_Form_Type_obrowser extends Horde_Form_Type
{
    public function isValid($var, $vars, $value, $message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Relationship browser")];
    }

}

class Horde_Form_Type_dblookup extends Horde_Form_Type_enum
{
    /**
     * Initialize an dblookup field
     *
     * @param Horde_Db_Adapter $db
     * @param string $sql
     * @param string|null $prompt
     *
     * function init($db, $sql, $prompt = null)
     */
    public function init(...$params)
    {
        $db = $params[0];
        $sql = $params[1];
        $prompt = $params[2] ?? null;

        $values = [];
        try {
            $col = $db->selectValues($sql);
            $values = array_combine($col, $col);
        } catch (Horde_Db_Exception $e) {
        }
        parent::init($values, $prompt);
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Database lookup"),
            'params' => [
                'db' => ['label' => Horde_Form_Translation::t("DSN (see http://pear.php.net/manual/en/package.database.db.intro-dsn.php)"),
                    'type'  => 'text'],
                'sql' => ['label' => Horde_Form_Translation::t("SQL statement for value lookups"),
                    'type'  => 'text'],
                'prompt' => ['label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text']],
        ];
    }

}

class Horde_Form_Type_figlet extends Horde_Form_Type
{
    public $_text;
    public $_font;

    /**
     * Initialize a Figlet form type
     *
     * function init($text, $font)
     */
    public function init(...$params)
    {
        $this->_text = $params[0];
        $this->_font = $params[1];
    }

    public function isValid($var, $vars, $value, $message)
    {
        if (empty($value) && $var->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if (Horde_String::lower($value) != Horde_String::lower($this->_text)) {
            return $this->invalid('The text you entered did not match the text on the screen.');
        }

        return true;
    }

    public function getFont()
    {
        return $this->_font;
    }

    public function getText()
    {
        return $this->_text;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Figlet CAPTCHA"),
            'params' => [
                'text' => ['label' => Horde_Form_Translation::t("Text"),
                    'type'  => 'text'],
                'font' => ['label' => Horde_Form_Translation::t("Figlet font"),
                    'type'  => 'text']],
        ];
    }

}

class Horde_Form_Type_captcha extends Horde_Form_Type_figlet
{
    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Image CAPTCHA"),
            'params' => [
                'text' => ['label' => Horde_Form_Translation::t("Text"),
                    'type'  => 'text'],
                'font' => ['label' => Horde_Form_Translation::t("Font"),
                    'type'  => 'text']],
        ];
    }

}

class Horde_Form_Type_category extends Horde_Form_Type
{
    public function getInfo($vars, $var, $info)
    {
        $info = $var->getValue($vars);
        if ($info == '*new*') {
            $info = ['new' => true,
                'value' => $vars->get('new_category')];
        } else {
            $info = ['new' => false,
                'value' => $info];
        }
        return $info;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return ['name' => Horde_Form_Translation::t("Category")];
    }

    public function isValid($var, $vars, $value, $message)
    {
        if (empty($value) && $var->isRequired()) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

}

class Horde_Form_Type_invalid extends Horde_Form_Type
{
    /**
     * Initialize an Invalid Message form type
     *
     * function init($message)
     */
    public function init(...$params)
    {
        $this->message = $params[0] ?? '';
    }

    public function isValid($var, $vars, $value, $message)
    {
        return false;
    }

    /**
     * Return info about field type.
     */
    public function about()
    {
        return [
            'name' => Horde_Form_Translation::t("Invalid"),
            'params' => [
                'message' => [
                    'label' => Horde_Form_Translation::t("Text"),
                    'type'  => 'text'
                ]
            ]
        ];
    }

}
