<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * StringarrayVariable type for string list input fields returning an array.
 *
 * @property string $regex The regex pattern for validation
 * @property int $size The size of the input field
 * @property int|null $maxlength The maximum number of characters
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_stringarray PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class StringarrayVariable extends StringlistVariable
{
    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        return array_map('trim', explode(',', $vars->get($this->getVarName())));
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        $about = parent::about();
        $about['name'] = Horde_Form_Translation::t("String list returning an array");
        return $about;
    }
}
