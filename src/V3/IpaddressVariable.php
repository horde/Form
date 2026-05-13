<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * IpaddressVariable type for IPv4 address input fields.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_ipaddress PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class IpaddressVariable extends TextVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (strlen(trim((string) $value)) > 0) {
            $ip = explode('.', $value);
            $valid = (count($ip) == 4);
            if ($valid) {
                foreach ($ip as $part) {
                    if (!is_numeric($part)
                        || $part > 255
                        || $part < 0) {
                        $valid = false;
                        break;
                    }
                }
            }

            if (!$valid) {
                return $this->invalid('Please enter a valid IP address.');
            }
        } elseif ($this->isRequired()) {
            return $this->invalid('This field is required.');
        }

        return true;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("IP address") ];
    }
}
