<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportHelperTest extends TestCase
{
    private ExportHelper $exportHelper;

    private ImportHelper $importHelper;

    private PathsHelper&MockObject $pathsHelper;

    /**
     * @var array<string>
     */
    private array $paths = [];

    protected function setUp(): void
    {
        $this->exportHelper = new ExportHelper(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(FilePathResolver::class),
            $this->createMock(ProcessSignalService::class),
            $this->createMock(EventDispatcherInterface::class),
        );

        $filesystem = new Filesystem();

        $systemTempDirBase = sys_get_temp_dir().'/import_helper_test';
        $this->paths[]     = $systemTempDirBase;
        $this->pathsHelper = $this->createMock(PathsHelper::class);

        $testTempDir = $systemTempDirBase.'/tmp';
        $this->pathsHelper->method('getTemporaryPath')->willReturn($testTempDir);
        $filesystem->mkdir($testTempDir);

        $mediaDir = $systemTempDirBase.'/media';
        $this->pathsHelper->method('getMediaPath')->willReturn($mediaDir);
        $filesystem->mkdir($mediaDir);

        $this->importHelper = new ImportHelper($this->pathsHelper);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->paths);

        parent::tearDown();
    }

    public function testReadFromZipWithAssets(): void
    {
        $jsonData = ['key' => 'value'];
        $tempDir  = sys_get_temp_dir();

        // Create temporary asset files.
        $assetFilePath1 = tempnam($tempDir, 'asset_test1');
        file_put_contents($assetFilePath1, 'Asset content 1');
        $this->paths[] = $assetFilePath1;

        $assetFilePath2 = tempnam($tempDir, 'asset_test2');
        file_put_contents($assetFilePath2, 'Asset content 2');
        $this->paths[] = $assetFilePath2;

        $assetList  = [$assetFilePath1, $assetFilePath2];
        $jsonOutput = json_encode($jsonData, JSON_THROW_ON_ERROR);

        // Call the method to create a zip file.
        $zipFilePath   = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, '');
        $this->paths[] = $zipFilePath;

        self::assertFileExists($zipFilePath);

        self::assertSame($jsonData, $this->importHelper->readZipFile($zipFilePath));
    }
}
