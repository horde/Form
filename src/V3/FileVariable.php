<?php

namespace Horde\Form\V3;

use Horde\Util\Variables;
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
    public function isValid(Horde_Variables|Variables $vars, $value): bool
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
        $name = $this->getVarName();
        try {
            $GLOBALS['browser']->wasFileUploaded($name);
            return [
                /**
                 * WARNING: Horde_Util::dispelMagicQuotes() removed in PSR-4 version
                 * Magic quotes are obsolete in PHP 8+. Remove this call.
                 */
                'name' => Horde_Util::dispelMagicQuotes($_FILES[$name]['name']),
                'type' => $_FILES[$name]['type'],
                'tmp_name' => $_FILES[$name]['tmp_name'],
                'file' => $_FILES[$name]['tmp_name'],
                'error' => $_FILES[$name]['error'],
                'size' => $_FILES[$name]['size'],
            ];
        } catch (Horde_Browser_Exception $e) {
        }

        return null;
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
