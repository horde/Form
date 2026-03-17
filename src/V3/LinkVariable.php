<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * LinkVariable type for hyperlink display fields.
 *
 * @property array $values List of hashes containing link parameters
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_link PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class LinkVariable extends BaseVariable
{
    /**
     * List of hashes containing link parameters. Possible keys: 'url', 'text',
     * 'target', 'onclick', 'title', 'accesskey', 'class'.
     *
     * @var array
     */
    public $values;

    /**
     * Initialize a link field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: array|null $values - List of hashes containing link parameters. Possible keys: 'url', 'text', 'target', 'onclick', 'title', 'accesskey', 'class' (default: null)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->values = $params[0] ?? null;
    }

    public function isValid(Horde_Variables $vars, $value): bool
    {
        return true;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Link"),
            'params' => [
                'values' => [
                    'label' => Horde_Form_Translation::t("Values"),
                    'type' => 'array'
                ]
            ]
        ];
    }
}
