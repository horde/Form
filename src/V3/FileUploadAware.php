<?php

declare(strict_types=1);

namespace Horde\Form\V3;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Interface for variable types that handle file uploads.
 *
 * When a PSR-7 ServerRequestInterface is passed to BaseForm, uploaded files
 * are extracted from the request and injected into variables implementing
 * this interface via setUploadedFile() before validate() and getInfo() run.
 *
 * Variables that do NOT receive an injected file (i.e., setUploadedFile()
 * was never called or was called with null) fall back to legacy $_FILES /
 * $GLOBALS['browser'] handling.
 *
 * ## Implementing custom file-type variables
 *
 * To create a custom variable type that participates in PSR-7 file upload
 * injection, implement this interface:
 *
 * ```php
 * class AvatarVariable extends BaseVariable implements FileUploadAware
 * {
 *     private ?UploadedFileInterface $uploadedFile = null;
 *
 *     public function setUploadedFile(?UploadedFileInterface $file): void
 *     {
 *         $this->uploadedFile = $file;
 *     }
 *
 *     protected function isValid($vars, $value): bool
 *     {
 *         if ($this->uploadedFile !== null) {
 *             // Use $this->uploadedFile->getError(), getSize(), etc.
 *         }
 *         // ...
 *     }
 * }
 * ```
 *
 * BaseForm resolves the uploaded file by matching the variable's name
 * against the ServerRequest's uploaded files tree.
 */
interface FileUploadAware
{
    public function setUploadedFile(?UploadedFileInterface $file): void;
}
