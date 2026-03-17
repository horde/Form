<?php
namespace Horde\Form\V3;

use Horde_Variables;
use Horde_Util;
use Horde_Form_Translation;
use Horde_Browser_Exception;

/**
 * FileVariable type for file upload fields.
 
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Type_file PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class FileVariable extends BaseVariable
{
    /**
     * Validates file upload field.
     *
     * Checks if a file was successfully uploaded using the browser's upload
     * detection. Required fields must have a file uploaded. Optional fields
     * pass validation even without a file.
     *
     * @param Horde_Variables $vars  Form variables
     * @param mixed $value           Field value (not used; checks $_FILES directly)
     *
     * @return bool  True if valid, false with error message set if required file missing
      *
      * @api
     */
    public function isValid(Horde_Variables $vars, $value): bool
    {
        if ($this->isRequired()) {
            try {
                $GLOBALS['browser']->wasFileUploaded($this->getVarName());
            } catch (Horde_Browser_Exception $e) {
                $this->message = $e->getMessage();
                return false;
            }
        }

        return true;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        $info = [];
        $name = $this->getVarName();
        try {
            $GLOBALS['browser']->wasFileUploaded($name);
            $info['name'] = Horde_Util::dispelMagicQuotes($_FILES[$name]['name']);
            $info['type'] = $_FILES[$name]['type'];
            $info['tmp_name'] = $_FILES[$name]['tmp_name'];
            $info['file'] = $_FILES[$name]['tmp_name'];
            $info['error'] = $_FILES[$name]['error'];
            $info['size'] = $_FILES[$name]['size'];
        } catch (Horde_Browser_Exception $e) {
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
        return [ 'name' => Horde_Form_Translation::t("File upload") ];
    }
}
