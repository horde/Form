<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * CategoryVariable type for category selection with option to create new categories.
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_category PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class CategoryVariable extends BaseVariable
{
    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $info = $this->getValue($vars);
        if ($info == '*new*') {
            $info = [
                'new' => true,
                'value' => $vars->get('new_category')
            ];
        } else {
            $info = [
                'new' => false,
                'value' => $info
            ];
        }

        return $info;
    }

    public function isValid(Horde_Variables $vars, $value): bool
    {
        if (empty($value) && $this->isRequired()) {
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
        return [ 'name' => Horde_Form_Translation::t("Category") ];
    }
}
