<?php

declare(strict_types=1);

namespace Horde\Form\V3;

use Horde\Nls\Nls;
use Horde\Util\Variables;
use Horde_Form_Translation;
use Horde_String;
use Horde_Variables;

/**
 * AddressVariable type for address input fields with parsing capabilities.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 * @property int $rows The number of rows for the textarea
 * @property int $cols The number of columns for the textarea
 * @property array $helper Array of helper options

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_address PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class AddressVariable extends LongtextVariable
{
    private readonly Nls $nls;

    public function __construct(
        $humanName,
        $varName,
        $required,
        $readonly = false,
        $description = null,
        ?Nls $nls = null,
    ) {
        parent::__construct($humanName, $varName, $required, $readonly, $description);
        $this->nls = $nls ?? new Nls();
    }
    public function parse($address)
    {
        $info = [];
        $aus_state_regex = '(?:ACT|NSW|NT|QLD|SA|TAS|VIC|WA)';

        if (preg_match('/(?s)(.*?)(?-s)\r?\n(?:(.*?)\s+)?((?:A[BL]|B[ABDHLNRST]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[CHNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTWY]?|T[ADFNQRSW]|UB|W[ACDFNRSV]?|YO|ZE)\d(?:\d|[A-Z])? \d[A-Z]{2})/', $address, $addressParts)) {
            /* UK postcode detected. */
            $info = [ 'country' => 'uk', 'zip' => $addressParts[3] ];
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
            $info = [ 'country' => 'au' ];
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
            $info = [ 'country' => 'us' ];
            if (!empty($addressParts[4])
                && preg_match('|[a-zA-Z]\d[a-zA-Z]\s?\d[a-zA-Z]\d|', $addressParts[4])) {
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
                $carsigns = $this->nls->carsigns()->all();
                $country = array_search(Horde_String::upper($addressParts[2]), $carsigns);
                if ($country) {
                    $info['country'] = $country;
                }
            }
            if (!empty($addressParts[5])) {
                $countries = $this->nls->countries()->all();
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
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Address"),
            'params' => [
                'rows' => [
                    'label' => Horde_Form_Translation::t("Number of rows"),
                    'type'  => 'int',
                ],
                'cols' => [
                    'label' => Horde_Form_Translation::t("Number of columns"),
                    'type'  => 'int',
                ],
                'helper' => [
                    'label' => Horde_Form_Translation::t("Helpers"),
                    'type'  => 'stringarray',
                ],
            ],
        ];
    }
}
