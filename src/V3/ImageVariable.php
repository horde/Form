<?php

declare(strict_types=1);

namespace Horde\Form\V3;

use Horde\Util\ArrayUtils;
use Horde;
use Horde\Util\Variables;
use Horde_Variables;
use Horde_Form_Translation;
use Horde_Browser_Exception;
use Horde_Mime_Magic;
use PEAR;
use Psr\Http\Message\UploadedFileInterface;

/**
 * ImageVariable type for image upload fields.
 *
 * ## PSR-7 upload path (recommended for new code)
 *
 * When a PSR-7 ServerRequestInterface is passed to BaseForm, the uploaded
 * file is injected via setUploadedFile() before validate() and getInfo().
 * The variable writes the stream to a temp file and populates the same
 * image metadata array as the legacy path (type, name, size, file).
 *
 * For ImageVariable, BaseForm resolves the upload at key "varname[new]"
 * in the uploaded files tree, matching the HTML field structure used by
 * the image upload widget.
 *
 * ## Legacy path (backward compatibility)
 *
 * When no UploadedFileInterface is injected, falls back to reading
 * $_FILES via $GLOBALS['browser']->wasFileUploaded().
 *
 * ## Session-based image persistence
 *
 * Both paths use $GLOBALS['session'] to persist image data between
 * requests (for preview/modify workflows). The session dependency is
 * orthogonal to the upload source — it handles previously uploaded
 * images, not the current upload.
 *
 * @property bool $show_upload Show the upload button
 * @property bool $show_keeporig Show the option to upload also original non-modified image
 * @property int|null $max_filesize Limit the file size
 *
 * @see Horde_Form_Type_image PSR-0 legacy equivalent in lib/Horde/Form/Type.php
 */
class ImageVariable extends BaseVariable implements FileUploadAware
{
    private ?UploadedFileInterface $uploadedFile = null;

    public function setUploadedFile(?UploadedFileInterface $file): void
    {
        $this->uploadedFile = $file;
    }
    /**
     * Has a file been uploaded on this form submit?
     *
     * @var boolean
     */
    public $_uploaded = null;

    /**
     * Show the upload button?
     *
     * @var boolean
     */
    public $_show_upload = true;

    /**
     * Show the option to upload also original non-modified image?
     *
     * @var boolean
     */
    public $_show_keeporig = false;

    /**
     * Limit the file size?
     *
     * @var integer
     */
    public $_max_filesize = null;

    /**
     * Hash containing the previously uploaded image info.
     *
     * @var array|null
     */
    public ?array $_img = null;

    /**
     * A random id that identifies the image information in the session data.
     *
     * @var string
     */
    public $_random;

    /**
     * Initialize an image upload field.
     *
     * @param array $params Variable arguments:
     *                      - $params[0]: bool $show_upload - Show the upload button (default: true)
     *                      - $params[1]: bool $show_keeporig - Show the option to upload also original non-modified image (default: false)
     *                      - $params[2]: int|null $max_filesize - Limit the file size in bytes (default: null)
      *
      * @api
     */
    public function init(...$params)
    {
        $this->_show_upload   = $params[0] ?? true;
        $this->_show_keeporig = $params[1] ?? false;
        $this->_max_filesize  = $params[2] ?? null;
    }

    /**
      *
      * @api
     */
    public function onSubmit($vars)
    {
        /* Are we removing an image? */
        if ($vars->get('remove_' . $this->getVarName())) {
            $GLOBALS['session']->remove('horde', 'form/' . $this->getRandomId());
            $this->_img = null;
            return;
        }

        /* Get the upload. */
        $this->getImage($vars);

        /* If this was done through the upload button override the submitted
         * value of the form. */
        if ($vars->get('do_' . $this->getVarName())) {
            $this->form->setSubmitted(false);
            if ($this->_uploaded instanceof Horde_Browser_Exception) {
                $this->_img = [
                    'hash' => $this->getRandomId(),
                    'error' => $this->_uploaded->getMessage(),
                ];
            }
        }
    }

    /**
     * @param Horde_Form_Variable $var  The Form field object to check
     * @param Horde_Variables $vars     The form state to check this field for
     * @param array $value              The field value array - should contain a key ['hash'] which holds the key for the image on temp storage
      *
      * @api
     */
    public function isValid(Horde_Variables|Variables $vars, $value): bool
    {
        if ($vars->get('remove_' . $this->getVarName())) {
            return true;
        }

        /* Get the upload. */
        $this->getImage($vars);
        $field = $vars->get($this->getVarName());

        /* The upload generated a PEAR Error. */
        if ($this->_uploaded instanceof Horde_Browser_Exception) {
            /* Not required and no image upload attempted. */
            if (!$this->isRequired() && empty($field['hash'])
                && $this->_uploaded->getCode() == UPLOAD_ERR_NO_FILE) {
                return true;
            }

            if (($this->_uploaded->getCode() == UPLOAD_ERR_NO_FILE)
                && empty($field['hash'])) {
                /* Nothing uploaded and no older upload. */
                return $this->invalid('This field is required.');
            }

            if (!empty($field['hash'])) {
                if ($this->_img && isset($this->_img['error'])) {
                    $this->message = $this->_img['error'];
                    return false;
                }
                /* Nothing uploaded but older upload present. */
                return true;
            }

            /* Some other error message. */
            $this->message = $this->_uploaded->getMessage();
            return false;
        }

        if (empty($this->_img['img']['size'])) {
            return $this->invalid('The image file size could not be determined or it was 0 bytes. The upload may have been interrupted.');
        }

        if ($this->_max_filesize && $this->_img['img']['size'] > $this->_max_filesize) {
            $this->message = sprintf(Horde_Form_Translation::t("The image file was larger than the maximum allowed size (%d bytes)."), $this->_max_filesize);
            return false;
        }

        return true;
    }

    //TODO: Rename back to getInfo() after the V3 transition
    protected function getInfoV3($vars)
    {
        /* Get the upload. */
        $this->getImage($vars);

        /* Get image params stored in the hidden field. */
        $value = $this->getValue($vars);

        /* Check if we have image data */
        if (!isset($this->_img) || !isset($this->_img['img'])) {
            return '';
        }

        $info = $this->_img['img'];

        if (empty($info['file'])) {
            unset($info['file']);
            return $info;
        }

        if ($this->_show_keeporig) {
            $info['keep_orig'] = !empty($value['keep_orig']);
        }

        /* Set the uploaded value (either true or Horde_Browser_Exception). */
        $info['uploaded'] = &$this->_uploaded;

        /* If a modified file exists move it over the original. */
        if ($this->_show_keeporig && $info['keep_orig']) {
            /* Requested the saving of original file also. */
            $info['orig_file'] = Horde::getTempDir() . '/' . $info['file'];
            $info['file'] = Horde::getTempDir() . '/mod_' . $info['file'];

            /* Check if a modified file actually exists. */
            if (!file_exists($info['file'])) {
                $info['file'] = $info['orig_file'];
                unset($info['orig_file']);
            }
        } else {
            /* Saving of original not required. */
            $mod_file = Horde::getTempDir() . '/mod_' . $info['file'];
            $info['file'] = Horde::getTempDir() . '/' . $info['file'];
            if (file_exists($mod_file)) {
                /* Unlink first (has to be done on Windows machines?) */
                unlink($info['file']);
                rename($mod_file, $info['file']);
            }
        }

        return $info;
    }

    /**
     * Gets the upload and sets up the upload data array. Either
     * fetches an upload done with this submit or retrieves stored
     * upload info.
     *
     * @param Horde_Variables $vars The form state to check this field for
     *
     */
    private function _getUpload($vars)
    {
        /* Don't bother with this function if already called and set up vars. */
        if (!is_null($this->_img)) {
            return;
        }

        // TODO: refactor globals to DI
        global $session;

        $varname = $this->getVarName();
        $upload = $vars->get($varname);
        $hashName = $upload['hash'] ?? null;
        if ($hashName !== null) {
            $hashName = 'form/' . $hashName;
        }

        /* Check if file has been uploaded. */
        if ($this->uploadedFile !== null) {
            // PSR-7 path: use injected UploadedFileInterface
            $error = $this->uploadedFile->getError();
            if ($error === UPLOAD_ERR_OK) {
                $this->_uploaded = true;
            } else {
                $this->_uploaded = new Horde_Browser_Exception(
                    FileVariable::uploadErrorMessage($error),
                    $error
                );
            }
        } else {
            // Legacy path: $_FILES via Horde_Browser
            try {
                $new = $varname . '[new]';

                $GLOBALS['browser']->wasFileUploaded($new);
                $this->_uploaded = true;
            } catch (Horde_Browser_Exception $e) {
                $this->_uploaded = $e;
            }
        }

        if ($this->_uploaded === true) {
            /* A file has been uploaded on this submit. Save to temp dir for preview to work. */

            /* Get any existing values for the image upload field. */
            if ($hashName !== null) {
                $img = $session->get('horde', $hashName);
                $session->remove('horde', $hashName);
                $tmp_file = $img['file'] ?? null;
                if ($tmp_file === null) {
                    $tmp_file = Horde::getTempFile('Horde', false);
                } else {
                    $tmp_file = Horde::getTempDir() . '/' . basename($tmp_file);
                }
            } else {
                $tmp_file = Horde::getTempFile('Horde', false);
            }

            if ($this->uploadedFile !== null) {
                // PSR-7 path: write stream to temp file
                $stream = $this->uploadedFile->getStream();
                $dest = fopen($tmp_file, 'wb');
                while (!$stream->eof()) {
                    fwrite($dest, $stream->read(8192));
                }
                fclose($dest);

                $name = $this->uploadedFile->getClientFilename();
                $type = $this->uploadedFile->getClientMediaType();
                $size = $this->uploadedFile->getSize();

                if ($type === null || $type === '' || $type === 'application/octet-stream') {
                    $type = Horde_Mime_Magic::analyzeFile($tmp_file, $GLOBALS['conf']['mime']['magic_db'] ?? null);
                    if ($type === false) {
                        $type = Horde_Mime_Magic::filenameToMime($name ?? '');
                    }
                }
            } else {
                // Legacy path: read from $_FILES
                $new = $varname . '[new]';
                $keys = ArrayUtils::getFieldParts($new);

                $file = ArrayUtils::getElement($_FILES, $keys, 'tmp_name');
                move_uploaded_file($file, $tmp_file);

                $name = ArrayUtils::getElement($_FILES, $keys, 'name');
                $type = ArrayUtils::getElement($_FILES, $keys, 'type');
                if ($type === null || $type === '' || $type === 'application/octet-stream') {
                    $type = Horde_Mime_Magic::analyzeFile($tmp_file, $GLOBALS['conf']['mime']['magic_db'] ?? null);
                    if ($type === false) {
                        $type = Horde_Mime_Magic::filenameToMime($name);
                    }
                    ArrayUtils::setElement($_FILES, $keys, $type, 'type');
                }
                $size = ArrayUtils::getElement($_FILES, $keys, 'size');
            }

            $img = [
                'type' => $type,
                'name' => $name,
                'size' => $size,
                'file' => basename($tmp_file),
            ];
        } else {
            /* File has not been uploaded. */

            if ($vars->get('remove_' . $varname)) {
                /* File is explicitly removed */
                if ($hashName !== null) {
                    $session->remove('horde', $hashName);
                }
                return;
            }

            if ($this->_uploaded->getCode() == 4 && $hashName !== null && $session->exists('horde', $hashName)) {
                $img = $session->get('horde', $hashName);
                $session->remove('horde', $hashName);
                if (isset($img['error'])) {
                    $this->_uploaded = PEAR::raiseError($img['error']);
                }
            } else {
                $img = null;
            }
        }

        if ($img !== null) {
            $this->_img['img'] = $img;
            $session->set('horde', 'form/' . $this->getRandomId(), $img);
        }
    }

    /**
     * Returns the current image information.
     *
     * @param Horde_Variables $vars     The form state to check this field for
     * @deprecated The second parameter ($var) is deprecated/ignored
     * @return array  The current image hash.
     *
     * @api
     */
    public function getImage($vars, ...$args)
    {
        if (count($args) > 0) {
            self::Deprecated('Warning: The second ($var) parameter in getImage() is deprecated/ignored');
        }

        // TODO: refactor globals to DI
        global $session;

        $this->_getUpload($vars);

        if (!isset($this->_img)) {
            $image = $vars->get($this->getVarName());
            if ($image) {
                $image = $this->loadImageData($image);
                if (isset($image['img'])) {
                    $this->_img = $image;
                    $session->set('horde', 'form/' . $this->getRandomId(), $this->_img['img']);
                }
            }
        }

        return $this->_img;
    }

    /**
     * Loads any existing image data into the image field. Requires that the
     * array $image passed to it contains the structure:
     *   $image['load']['file'] - the filename of the image;
     *   $image['load']['data'] - the raw image data.
     *
     * @param array $image  The image array.
      *
      * @api
     */
    public function loadImageData($image)
    {
        /* No existing image data to load. */
        if (!isset($image['load'])) {
            return;
        }

        /* Save the data to the temp dir. */
        $tmp_file = Horde::getTempDir() . '/' . $image['load']['file'];
        if ($fd = fopen($tmp_file, 'w')) {
            fwrite($fd, $image['load']['data']);
            fclose($fd);
        }

        $image['img'] = [ 'file' => $image['load']['file'] ];
        unset($image['load']);

        return $image;
    }

    public function getRandomId()
    {
        if (!isset($this->_random)) {
            $this->_random = uniqid((string)mt_rand());
        }

        return $this->_random;
    }

    /**
     * Return info about field type.
      *
      * @api
     */
    public function about(): array
    {
        return [
            'name' => Horde_Form_Translation::t("Image upload"),
            'params' => [
                'show_upload'   => [
                    'label' => Horde_Form_Translation::t("Show upload?"),
                    'type'  => 'boolean',
                ],
                'show_keeporig' => [
                    'label' => Horde_Form_Translation::t("Show option to keep original?"),
                    'type'  => 'boolean',
                ],
                'max_filesize'  => [
                    'label' => Horde_Form_Translation::t("Maximum file size in bytes"),
                    'type'  => 'int',
                ],
            ],
        ];
    }
}
