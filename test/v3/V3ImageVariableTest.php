<?php

namespace Horde\Form\Test\V3;

use Horde\Form\V3\ImageVariable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Horde_Form_Type_image;

#[CoversClass(ImageVariable::class)]
#[CoversClass(Horde_Form_Type_image::class)]
class V3ImageVariableTest extends TestCase
{
    private $testDir;
    private $targetFile;
    private $oldConf;
    private $oldInjector;

    protected function setUp(): void
    {
        $this->oldConf = $GLOBALS['conf'] ?? null;
        $this->oldInjector = $GLOBALS['injector'] ?? null;
        $this->testDir = sys_get_temp_dir() . '/horde-form-image-' . bin2hex(random_bytes(8));
        mkdir($this->testDir . '/tmp', 0o700, true);
        $GLOBALS['conf']['tmpdir'] = $this->testDir . '/tmp';
        $this->targetFile = $this->testDir . '/victim.php';
        file_put_contents($this->targetFile, 'unchanged');
        $tempSession = new class {
            private array $data = [];

            public function getScoped($app, $key)
            {
                return $this->data[$app][$key] ?? null;
            }

            public function setScoped($app, $key, $value): void
            {
                $this->data[$app][$key] = $value;
            }
        };
        $GLOBALS['injector'] = new class ($tempSession) {
            private object $session;

            public function __construct(object $session)
            {
                $this->session = $session;
            }

            public function getInstance($class)
            {
                return $this->session;
            }
        };
    }

    protected function tearDown(): void
    {
        foreach (glob($this->testDir . '/tmp/*') as $file) {
            unlink($file);
        }
        unlink($this->targetFile);
        rmdir($this->testDir . '/tmp');
        rmdir($this->testDir);

        if ($this->oldConf === null) {
            unset($GLOBALS['conf']);
        } else {
            $GLOBALS['conf'] = $this->oldConf;
        }
        if ($this->oldInjector === null) {
            unset($GLOBALS['injector']);
        } else {
            $GLOBALS['injector'] = $this->oldInjector;
        }
    }

    public function testLoadImageDataUsesGeneratedFilename(): void
    {
        $variable = new ImageVariable('Photo', 'photo', false);
        $image = $variable->loadImageData([
            'load' => [
                'file' => '../victim.php',
                'data' => '<?php echo "payload"; ?>',
            ],
        ]);

        $this->assertNotSame('victim.php', $image['img']['file']);
        $this->assertSame(basename($image['img']['file']), $image['img']['file']);
        $this->assertSame(
            '<?php echo "payload"; ?>',
            file_get_contents($this->testDir . '/tmp/' . $image['img']['file'])
        );
        $this->assertSame('unchanged', file_get_contents($this->targetFile));
    }

    public function testLegacyLoadImageDataUsesGeneratedFilename(): void
    {
        $type = new Horde_Form_Type_image();
        $image = $type->loadImageData([
            'load' => [
                'file' => '../victim.php',
                'data' => '<?php echo "payload"; ?>',
            ],
        ]);

        $this->assertNotSame('victim.php', $image['img']['file']);
        $this->assertSame(basename($image['img']['file']), $image['img']['file']);
        $this->assertSame(
            '<?php echo "payload"; ?>',
            file_get_contents($this->testDir . '/tmp/' . $image['img']['file'])
        );
        $this->assertSame('unchanged', file_get_contents($this->targetFile));
    }
}
