<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;

/**
 * CreditcardVariable type for credit card number input fields.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_creditcard PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class CreditcardVariable extends BaseVariable
{
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if (empty($value) && $this->isRequired()) {
            return $this->invalid('This field is required.');
        }

        if (!empty($value)) {
            /* getCardType() will also verify the checksum. */
            $type = self::getCardType($value);
            if ($type === false || $type == 'unknown') {
                return $this->invalid('This does not seem to be a valid card number.');
            }
        }

        return true;
    }

    public static function getChecksum(string $ccnum): int
    {
        $len = strlen($ccnum);
        $checksum = 0;
        $double = false;

        for ($i = $len - 1; $i >= 0; --$i) {
            $digit = (int) $ccnum[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $checksum += $digit;
            $double = !$double;
        }

        return $checksum;
    }

    public static function getCardType(string $ccnum)
    {
        // Remove spaces and other non-digits
        $ccnum = preg_replace('/\D/', '', $ccnum);
        $l = strlen($ccnum);

        // Screen checksum first
        if ($l === 0 || self::getChecksum($ccnum) % 10 !== 0) {
            return false;
        }

        // Check for Visa
        if (
            ($l === 13 || $l === 16)
            && $ccnum[0] === '4'
        ) {
            return 'visa';
        }

        // Check for MasterCard (51–55)
        if (
            $l === 16
            && $ccnum[0] === '5'
            && $ccnum[1] >= '1'
            && $ccnum[1] <= '5'
        ) {
            return 'mastercard';
        }

        // Check for Amex (34, 37)
        if (
            $l === 15
            && $ccnum[0] === '3'
            && ($ccnum[1] === '4' || $ccnum[1] === '7')
        ) {
            return 'amex';
        }

        // Check for Discover (6011)
        if (
            $l === 16
            && substr($ccnum, 0, 4) === '6011'
        ) {
            return 'discover';
        }

        // If we got this far, then no card matched
        return 'unknown';
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [ 'name' => Horde_Form_Translation::t("Credit card number") ];
    }
}
