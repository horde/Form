<?php

declare(strict_types=1);

/**
 * Tests for PSR-7 file upload support in V3 FileVariable and BaseForm.
 *
 * Verifies that UploadedFileInterface objects injected via setUploadedFile()
 * are used for validation and getInfo() extraction, bypassing the legacy
 * $_FILES / $GLOBALS['browser'] path.
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL-2.1).
 *
 * @category   Horde
 * @package    Form
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */

namespace Horde\Form\Test\Unit\Type;

use Horde\Form\V3\BaseForm;
use Horde\Form\V3\FileVariable;
use Horde\Form\V3\FileUploadAware;
use Horde\Http\ServerRequest;
use Horde\Http\StreamFactory;
use Horde\Http\UploadedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

#[CoversClass(FileVariable::class)]
#[CoversClass(BaseForm::class)]
class FileUploadPsr7Test extends TestCase
{
    private function createUploadedFile(
        string $content,
        string $filename,
        string $mediaType,
        int $error = UPLOAD_ERR_OK
    ): UploadedFileInterface {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream($content);
        return new UploadedFile(
            $stream,
            $streamFactory,
            $filename,
            $mediaType,
            $error,
            strlen($content)
        );
    }

    private function createNoFileUpload(): UploadedFileInterface
    {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream('');
        return new UploadedFile(
            $stream,
            $streamFactory,
            '',
            '',
            UPLOAD_ERR_NO_FILE,
            0
        );
    }

    // ========================================================================
    // FileVariable with injected UploadedFileInterface
    // ========================================================================

    public function testIsValidReturnsTrueForSuccessfulUpload(): void
    {
        $upload = $this->createUploadedFile('PDF content', 'report.pdf', 'application/pdf');

        $var = new FileVariable('Attachment', 'attachment', true);
        $var->setUploadedFile($upload);

        $vars = new \Horde_Variables([]);
        $this->assertTrue($var->isValid($vars, null));
    }

    public function testIsValidReturnsFalseWhenRequiredAndNoFile(): void
    {
        $upload = $this->createNoFileUpload();

        $var = new FileVariable('Attachment', 'attachment', true);
        $var->setUploadedFile($upload);

        $vars = new \Horde_Variables([]);
        $this->assertFalse($var->isValid($vars, null));
    }

    public function testIsValidReturnsTrueWhenOptionalAndNoFile(): void
    {
        $upload = $this->createNoFileUpload();

        $var = new FileVariable('Attachment', 'attachment', false);
        $var->setUploadedFile($upload);

        $vars = new \Horde_Variables([]);
        $this->assertTrue($var->isValid($vars, null));
    }

    public function testGetInfoReturnsFileMetadataFromPsr7Upload(): void
    {
        $content = 'fake PDF bytes here';
        $upload = $this->createUploadedFile($content, 'doc.pdf', 'application/pdf');

        $var = new FileVariable('Document', 'document', false);
        $var->setUploadedFile($upload);

        $vars = new \Horde_Variables([]);
        $info = $var->getInfo($vars);

        $this->assertIsArray($info);
        $this->assertSame('doc.pdf', $info['name']);
        $this->assertSame('application/pdf', $info['type']);
        $this->assertSame(strlen($content), $info['size']);
        $this->assertSame(UPLOAD_ERR_OK, $info['error']);
        $this->assertInstanceOf(UploadedFileInterface::class, $info['uploaded_file']);
        // tmp_name should be a real file with the content
        $this->assertFileExists($info['tmp_name']);
        $this->assertSame($content, file_get_contents($info['tmp_name']));
    }

    public function testGetInfoReturnsNullWhenNoFileUploaded(): void
    {
        $upload = $this->createNoFileUpload();

        $var = new FileVariable('Document', 'document', false);
        $var->setUploadedFile($upload);

        $vars = new \Horde_Variables([]);
        $info = $var->getInfo($vars);

        $this->assertNull($info);
    }

    public function testUploadErrorMessageIsTranslated(): void
    {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream('');
        $upload = new UploadedFile($stream, $streamFactory, 'big.zip', 'application/zip', UPLOAD_ERR_INI_SIZE, 0);

        $var = new FileVariable('File', 'file', true);
        $var->setUploadedFile($upload);

        $vars = new \Horde_Variables([]);
        $this->assertFalse($var->isValid($vars, null));
        $this->assertNotEmpty($var->getMessage());
    }

    // ========================================================================
    // FileUploadAware interface check
    // ========================================================================

    public function testFileVariableImplementsFileUploadAware(): void
    {
        $var = new FileVariable('File', 'file', false);
        $this->assertInstanceOf(FileUploadAware::class, $var);
    }

    // ========================================================================
    // BaseForm integration with PSR-7 request
    // ========================================================================

    public function testBaseFormExtractsUploadedFilesFromPsr7Request(): void
    {
        $content = 'image data';
        $upload = $this->createUploadedFile($content, 'photo.jpg', 'image/jpeg');

        $request = new ServerRequest('POST', '/submit');
        $request = $request->withParsedBody(['title' => 'My Photo']);
        $request = $request->withUploadedFiles(['photo' => $upload]);

        $form = new BaseForm($request, 'Upload Form');
        $form->addVariable('Title', 'title', 'text', true);
        $form->addVariable('Photo', 'photo', 'file', true);

        $this->assertTrue($form->validate());

        $info = $form->getInfo();
        $this->assertSame('My Photo', $info['title']);
        $this->assertIsArray($info['photo']);
        $this->assertSame('photo.jpg', $info['photo']['name']);
        $this->assertSame('image/jpeg', $info['photo']['type']);
        $this->assertFileExists($info['photo']['tmp_name']);
        $this->assertSame($content, file_get_contents($info['photo']['tmp_name']));
    }

    public function testBaseFormValidationFailsForMissingRequiredFile(): void
    {
        $upload = $this->createNoFileUpload();

        $request = new ServerRequest('POST', '/submit');
        $request = $request->withParsedBody(['title' => 'No Photo']);
        $request = $request->withUploadedFiles(['photo' => $upload]);

        $form = new BaseForm($request, 'Upload Form');
        $form->useToken(false);
        $form->addVariable('Title', 'title', 'text', true);
        $form->addVariable('Photo', 'photo', 'file', true);

        $this->assertFalse($form->validate());
        $this->assertNotNull($form->getError('photo'));
    }

    public function testBaseFormLegacyPathUnaffectedWhenArrayInput(): void
    {
        // When input is a plain array, no uploaded files are extracted —
        // the legacy $_FILES path remains active (tested in FileTypeTest)
        $form = new BaseForm(['name' => 'test'], 'Simple Form');
        $form->useToken(false);
        $form->addVariable('Name', 'name', 'text', true);

        $this->assertTrue($form->validate());
        $info = $form->getInfo();
        $this->assertSame('test', $info['name']);
    }
}
