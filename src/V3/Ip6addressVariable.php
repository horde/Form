<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * Ip6addressVariable type for IPv6 address input fields.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_ip6address PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class Ip6addressVariable extends TextVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (strlen(trim((string) $value)) > 0) {
            $valid = @inet_pton($value);
            if ($valid === false) {
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
        return [ 'name' => Horde_Form_Translation::t("IPv6 address") ];
    }
}
