<?php

declare(strict_types=1);

/**
 * Tests for file type getInfo() behavior.
 *
 * Verifies that getInfo() returns null (not empty array) when no file
 * was uploaded, and returns file metadata array on successful upload.
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

use Horde_Browser_Exception;
use Horde_Form_Type_file;
use Horde_Form_Variable;
use Horde_Variables;
use Horde\Form\V3\FileVariable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Form_Type_file::class)]
#[CoversClass(FileVariable::class)]
class FileTypeTest extends TestCase
{
    private function makeBrowserStub(bool $hasFile): object
    {
        return new class ($hasFile) {
            public function __construct(private bool $hasFile) {}

            public function wasFileUploaded(string $name): void
            {
                if (!$this->hasFile) {
                    throw new Horde_Browser_Exception('No file uploaded');
                }
            }
        };
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['browser']);
    }

    // ========================================================================
    // Legacy Horde_Form_Type_file tests
    // ========================================================================

    public function testLegacyGetInfoReturnsNullWhenNoFileUploaded(): void
    {
        $GLOBALS['browser'] = $this->makeBrowserStub(false);

        $type = new Horde_Form_Type_file();
        $var = new Horde_Form_Variable('Upload', 'upload_field', $type, false);
        $vars = new Horde_Variables([]);

        $result = $type->getInfo($vars, $var, null);

        $this->assertNull($result);
    }

    public function testLegacyGetInfoReturnsFileArrayOnUpload(): void
    {
        $GLOBALS['browser'] = $this->makeBrowserStub(true);
        $_FILES['upload_field'] = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/php1234',
            'error' => 0,
            'size' => 12345,
        ];

        $type = new Horde_Form_Type_file();
        $var = new Horde_Form_Variable('Upload', 'upload_field', $type, false);
        $vars = new Horde_Variables([]);

        $result = $type->getInfo($vars, $var, null);

        $this->assertIsArray($result);
        $this->assertSame('test.pdf', $result['name']);
        $this->assertSame('application/pdf', $result['type']);
        $this->assertSame('/tmp/php1234', $result['tmp_name']);
        $this->assertSame('/tmp/php1234', $result['file']);
        $this->assertSame(0, $result['error']);
        $this->assertSame(12345, $result['size']);

        unset($_FILES['upload_field']);
    }

    // ========================================================================
    // V3 FileVariable tests
    // ========================================================================

    public function testV3GetInfoReturnsNullWhenNoFileUploaded(): void
    {
        $GLOBALS['browser'] = $this->makeBrowserStub(false);

        $var = new FileVariable('Upload', 'upload_field', false);
        $vars = new Horde_Variables([]);

        $result = $var->getInfo($vars);

        $this->assertNull($result);
    }

    public function testV3GetInfoReturnsFileArrayOnUpload(): void
    {
        $GLOBALS['browser'] = $this->makeBrowserStub(true);
        $_FILES['upload_field'] = [
            'name' => 'report.xlsx',
            'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'tmp_name' => '/tmp/php5678',
            'error' => 0,
            'size' => 98765,
        ];

        $var = new FileVariable('Upload', 'upload_field', false);
        $vars = new Horde_Variables([]);

        $result = $var->getInfo($vars);

        $this->assertIsArray($result);
        $this->assertSame('report.xlsx', $result['name']);
        $this->assertSame('/tmp/php5678', $result['tmp_name']);
        $this->assertSame('/tmp/php5678', $result['file']);
        $this->assertSame(0, $result['error']);
        $this->assertSame(98765, $result['size']);

        unset($_FILES['upload_field']);
    }
}
