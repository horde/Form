<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class DblookupVariable extends EnumVariable
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
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Database lookup"),
            'params' => [
                'db' => [
                    'label' => Horde_Form_Translation::t("DSN (see http://pear.php.net/manual/en/package.database.db.intro-dsn.php)"),
                    'type'  => 'text'
                ],
                'sql' => [
                    'label' => Horde_Form_Translation::t("SQL statement for value lookups"),
                    'type'  => 'text'
                ],
                'prompt' => [
                    'label' => Horde_Form_Translation::t("Prompt text"),
                    'type'  => 'text'
                ]
            ]
        ];
    }

}
