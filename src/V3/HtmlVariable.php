<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Form_Translation;

/**
 * HtmlVariable type for HTML content display fields.
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_html PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class HtmlVariable extends BaseVariable
{
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
        return [ 'name' => Horde_Form_Translation::t("HTML") ];
    }
}
