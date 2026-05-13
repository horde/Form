<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;
use Horde_Db_Exception;

/**
 * DblookupVariable type for selecting a value from a database lookup.
 *
 * @property array $values A hash map where the key is the internal 'value' to process and the value is the caption presented to the user
 * @property string|bool $prompt A null value text to prompt user selecting a value. Use a default if boolean true, else use the supplied string. No prompt on false.

 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_dblookup PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class DblookupVariable extends EnumVariable
{
    /**
     * Initialize a database lookup field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: Horde_Db_Adapter $db - Database adapter instance
     *                      - $params[1]: string $sql - SQL statement for value lookups
     *                      - $params[2]: string|null $prompt - Prompt text (optional)
      *
      * @api
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
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Database lookup"),
            'params' => [
                'db' => [
                    'label' => Horde_Form_Translation::t("DSN (see http://pear.php.net/manual/en/package.database.db.intro-dsn.php)"),
                    'type'  => 'text',
                ],
                'sql' => [
                    'label' => Horde_Form_Translation::t("SQL statement for value lookups"),
                    'type'  => 'text',
                ],
                'prompt' => [
                    'label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text',
                ],
            ],
        ];
    }
}
