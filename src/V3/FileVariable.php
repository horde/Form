<?php

declare(strict_types=1);

namespace Horde\Form\V3;

use Horde;
use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;
use Horde_Browser_Exception;
use Psr\Http\Message\UploadedFileInterface;

/**
 * FileVariable type for file upload fields.
 *
 * ## PSR-7 upload path (recommended for new code)
 *
 * When a PSR-7 ServerRequestInterface is passed to BaseForm, uploaded
 * files are extracted via getUploadedFiles() and injected into this
 * variable automatically before validate() and getInfo() are called.
 *
 * In this mode, no $_FILES access or $GLOBALS['browser'] dependency is
 * needed. The variable works entirely from the UploadedFileInterface:
 *
 * ```php
 * $request = $serverRequestFactory->createServerRequest('POST', '/upload');
 * $request = $request->withUploadedFiles(['document' => $uploadedFile]);
 * $request = $request->withParsedBody(['title' => 'Report']);
 *
 * $form = new BaseForm($request, 'Upload');
 * $form->addVariable('Doc', 'document', 'file', true);
 *
 * if ($form->validate()) {
 *     $info = $form->getInfo();
 *     // $info['document']['name']     — client filename
 *     // $info['document']['type']     — client media type
 *     // $info['document']['size']     — file size in bytes
 *     // $info['document']['tmp_name'] — temp file path (stream written to disk)
 *     // $info['document']['file']     — same as tmp_name
 *     // $info['document']['error']    — UPLOAD_ERR_OK
 *     // $info['document']['uploaded_file'] — original UploadedFileInterface
 * }
 * ```
 *
 * The file content is written to a temp file so that consumers can use
 * rename() to move it. Note: move_uploaded_file() will NOT work on this
 * temp file because PHP did not create it via its upload mechanism.
 * Use rename() or the UploadedFileInterface::moveTo() method instead.
 *
 * ## Legacy path (backward compatibility)
 *
 * When no UploadedFileInterface is injected (e.g., form data passed as
 * array or Horde_Variables), the variable falls back to reading $_FILES
 * and using $GLOBALS['browser']->wasFileUploaded() for validation.
 * This path is unchanged from previous behavior.
 *
 * @see Horde_Form_Type_file PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class FileVariable extends BaseVariable implements FileUploadAware
{
    private ?UploadedFileInterface $uploadedFile = null;

    public function setUploadedFile(?UploadedFileInterface $file): void
    {
        $this->uploadedFile = $file;
    }

    /**
     * Validates file upload field.
     *
     * @param Horde_Variables|Variables $vars  Form variables
     * @param mixed $value  Field value
     * @return bool
     *
     * @api
     */
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($this->uploadedFile !== null) {
            $error = $this->uploadedFile->getError();
            if ($error === UPLOAD_ERR_NO_FILE) {
                if ($this->isRequired()) {
                    return $this->invalid(Horde_Form_Translation::t("This field is required."));
                }
                return true;
            }
            if ($error !== UPLOAD_ERR_OK) {
                return $this->invalid(self::uploadErrorMessage($error));
            }
            return true;
        }

        // Legacy fallback: $_FILES via Horde_Browser
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

    /**
     * @api
     */
    protected function getInfoV3($vars)
    {
        if ($this->uploadedFile !== null) {
            if ($this->uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return null;
            }
            $tmpFile = Horde::getTempFile('form_upload', false);
            $stream = $this->uploadedFile->getStream();
            $stream->rewind();
            $dest = fopen($tmpFile, 'wb');
            while (!$stream->eof()) {
                fwrite($dest, $stream->read(8192));
            }
            fclose($dest);
            return [
                'name' => $this->uploadedFile->getClientFilename(),
                'type' => $this->uploadedFile->getClientMediaType(),
                'tmp_name' => $tmpFile,
                'file' => $tmpFile,
                'size' => $this->uploadedFile->getSize(),
                'error' => UPLOAD_ERR_OK,
                'uploaded_file' => $this->uploadedFile,
            ];
        }

        // Legacy fallback: $_FILES via Horde_Browser
        $name = $this->getVarName();
        try {
            $GLOBALS['browser']->wasFileUploaded($name);
            return [
                'name' => $_FILES[$name]['name'],
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
        return ['name' => Horde_Form_Translation::t("File upload")];
    }

    public static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => Horde_Form_Translation::t("The file was larger than the maximum allowed size."),
            UPLOAD_ERR_PARTIAL => Horde_Form_Translation::t("The file was only partially uploaded."),
            UPLOAD_ERR_NO_FILE => Horde_Form_Translation::t("No file was uploaded."),
            UPLOAD_ERR_NO_TMP_DIR => Horde_Form_Translation::t("Server temporary directory missing."),
            UPLOAD_ERR_CANT_WRITE => Horde_Form_Translation::t("Failed to write file to disk."),
            UPLOAD_ERR_EXTENSION => Horde_Form_Translation::t("A PHP extension stopped the upload."),
            default => Horde_Form_Translation::t("Unknown upload error."),
        };
    }
}
