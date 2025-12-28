<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class InvalidVariable extends BaseVariable
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

    public function isValid(Horde_Variables|array $vars, $value): bool
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
