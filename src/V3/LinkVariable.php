<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

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
     * Init a Link field
     *
     * function init($values)
     */
    public function init(...$params)
    {
        $this->values = $params[0] ?? null;
    }

    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        return true;
    }

    /**
     * Return info about field type.
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
