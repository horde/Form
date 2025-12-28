<?php
namespace Horde\Form\V3;
use Horde_Variables;
use Horde_Form_Translation;

class SpacerVariable extends BaseVariable
{
    public function isValid(Horde_Variables|array $vars, $value): bool
    {
        return true;
    }

    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Spacer")
        ];
    }

}
