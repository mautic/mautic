<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ImportHelperTest extends TestCase
{
    private ExportHelper $exportHelper;

    private ImportHelper $importHelper;

    private string $mediaDir;

    private PathsHelper&MockObject $pathsHelper;

    /**
     * @var array<string>
     */
    private array $paths = [];

    protected function setUp(): void
    {
        $this->exportHelper = new ExportHelper(
            $this->createStub(TranslatorInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(FilePathResolver::class),
            $this->createStub(ProcessSignalService::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $filesystem = new Filesystem();

        $systemTempDirBase = sys_get_temp_dir().'/import_helper_test';
        $this->paths[]     = $systemTempDirBase;
        $pathsHelper       = $this->createMock(PathsHelper::class);

        $testTempDir = $systemTempDirBase.'/tmp';
        $pathsHelper->method('getTemporaryPath')->willReturn($testTempDir);
        $filesystem->mkdir($testTempDir);

        $this->mediaDir = $systemTempDirBase.'/media';
        $pathsHelper->method('getMediaPath')->willReturn($this->mediaDir);
        $filesystem->mkdir($this->mediaDir);

        $coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturn([
            'txt',
            'ics',
            'svg',
            'svgz',
            'php',
            'php6',
            'htw',
            'htt',
            'exe',
            'dll',
            'sh',
            'bash',
        ]);

        $this->pathsHelper  = $pathsHelper;
        $this->importHelper = new ImportHelper($pathsHelper, $coreParametersHelper);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->paths);

        parent::tearDown();
    }

    /**
     * A ZIP entry named `../escape.php` starts with `../`.
     * normalizePath() silently drops the leading `../` (returns `escape.php`),
     * so the validation check sees no `..` and passes.
     * Extraction then uses the raw filename, writing the file outside var/tmp.
     */
    public function testReadZipFileWithLeadingPathTraversalThrowsRuntimeException(): void
    {
        $zipPath       = sys_get_temp_dir().'/traversal_leading_'.uniqid().'.zip';
        $this->paths[] = $zipPath;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('campaign.json', json_encode(['key' => 'value']));
        $zip->addFromString('../escape.php', '<?php system($_GET["cmd"]);');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/[Uu]nsafe/');

        $this->importHelper->readZipFile($zipPath);
    }

    /**
     * A ZIP entry `assets/../../escape.php` has two `..` segments. normalizePath() correctly pops `assets` for the first `..`,
     * but then the second `..` finds an empty stack and is silently dropped,
     * turning the path into `escape.php` (no `..` remains, passes validation).
     * The assets copy stage uses the raw filename as the destination, escaping media/files/.
     */
    public function testReadZipFileWithDoublePathTraversalInAssetsThrowsRuntimeException(): void
    {
        $zipPath       = sys_get_temp_dir().'/traversal_assets_'.uniqid().'.zip';
        $this->paths[] = $zipPath;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('campaign.json', json_encode(['key' => 'value']));
        $zip->addFromString('assets/../../escape.php', '<?php echo "pwned";');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/[Uu]nsafe/');

        $this->importHelper->readZipFile($zipPath);
    }

    public function testReadFromZipWithAssets(): void
    {
        $jsonData = ['key' => 'value'];
        $tempDir  = sys_get_temp_dir();

        // Create temporary asset files.
        $assetFilePath1 = $tempDir.'/asset_test1_'.uniqid().'.txt';
        file_put_contents($assetFilePath1, 'Asset content 1');
        $this->paths[] = $assetFilePath1;

        $assetFilePath2 = $tempDir.'/asset_test2_'.uniqid().'.txt';
        file_put_contents($assetFilePath2, 'Asset content 2');
        $this->paths[] = $assetFilePath2;

        $assetList  = [$assetFilePath1, $assetFilePath2];
        $jsonOutput = json_encode($jsonData, JSON_THROW_ON_ERROR);

        // Call the method to create a zip file.
        $zipFilePath   = $this->exportHelper->writeToZipFile($jsonOutput, $assetList, '');
        $this->paths[] = $zipFilePath;

        $this->assertFileExists($zipFilePath);

        $this->assertSame($jsonData, $this->importHelper->readZipFile($zipFilePath));
    }

    #[DataProvider('dangerousAssetProvider')]
    public function testReadFromZipRejectsDangerousAssets(string $filename): void
    {
        $zipPath       = sys_get_temp_dir().'/dangerous_asset_'.uniqid().'.zip';
        $this->paths[] = $zipPath;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('campaign.json', json_encode(['key' => 'value']));
        $zip->addFromString('assets/'.$filename, 'malicious content');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsafe asset file detected in ZIP');

        $this->importHelper->readZipFile($zipPath);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dangerousAssetProvider(): iterable
    {
        yield 'Apache control file' => ['.htaccess'];
        yield 'hidden file with allowed extension' => ['.notes.txt'];
        yield 'PHP file allowed by configuration' => ['payload.PHP'];
        yield 'PHP 6 file allowed by configuration' => ['payload.php6'];
        yield 'PHP variant' => ['payload.phtml'];
        yield 'compound PHP extension' => ['payload.php.txt'];
        yield 'PHP configuration file' => ['.user.ini'];
        yield 'IIS control file' => ['web.config'];
        yield 'IIS WebHits file allowed by configuration' => ['payload.htw'];
        yield 'IIS hypertext template allowed by configuration' => ['payload.htt'];
        yield 'executable allowed by configuration' => ['payload.exe'];
        yield 'library allowed by configuration' => ['payload.dll'];
        yield 'shell script allowed by configuration' => ['payload.sh'];
        yield 'Bash script allowed by configuration' => ['payload.bash'];
        yield 'extension not on the allowlist' => ['payload.html'];
        yield 'compound PHP extension with SVG' => ['payload.php.svg'];
        yield 'hidden file with WebP extension' => ['.payload.webp'];
    }

    #[DataProvider('allowedAssetProvider')]
    public function testReadFromZipAllowsConfiguredCustomAssetExtension(string $assetFilename): void
    {
        $jsonData      = ['key' => 'value'];
        $zipPath       = sys_get_temp_dir().'/custom_asset_'.uniqid().'.zip';
        $this->paths[] = $zipPath;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('campaign.json', json_encode($jsonData));
        $zip->addFromString('assets/'.$assetFilename, 'image content');
        $zip->close();

        $this->assertSame($jsonData, $this->importHelper->readZipFile($zipPath));
        $this->assertFileExists($this->mediaDir.'/files/'.$assetFilename);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function allowedAssetProvider(): iterable
    {
        yield 'custom calendar' => ['calendar.ics'];
        yield 'SVG image' => ['image.svg'];
        yield 'compressed SVG image' => ['image.svgz'];
    }

    #[DataProvider('exportedImageProvider')]
    public function testReadFromZipAllowsExportedImagesMissingFromConfiguredExtensions(string $assetFilename): void
    {
        $coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturn(['csv', 'pdf', 'txt']);
        $importHelper = new ImportHelper($this->pathsHelper, $coreParametersHelper);

        $jsonData      = ['key' => 'value'];
        $zipPath       = sys_get_temp_dir().'/exported_image_'.uniqid().'.zip';
        $this->paths[] = $zipPath;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('entity_data.json', json_encode($jsonData));
        $zip->addFromString('assets/'.$assetFilename, 'image content');
        $zip->close();

        $this->assertSame($jsonData, $importHelper->readZipFile($zipPath));
        $this->assertFileExists($this->mediaDir.'/files/'.$assetFilename);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function exportedImageProvider(): iterable
    {
        yield 'email builder WebP image' => ['image.webp'];
        yield 'email builder SVG image' => ['image.svg'];
        yield 'campaign share WebP banner' => ['banner.webp'];
        yield 'campaign share gallery PNG image' => ['gallery/image_1.png'];
        yield 'uppercase JPEG image' => ['photo.JPG'];
    }

    public function testReadFromZipAllowsExplicitAssetDirectory(): void
    {
        $jsonData      = ['key' => 'value'];
        $zipPath       = sys_get_temp_dir().'/asset_directory_'.uniqid().'.zip';
        $this->paths[] = $zipPath;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('campaign.json', json_encode($jsonData));
        $zip->addEmptyDir('assets/documents');
        $zip->addFromString('assets/documents/readme.txt', 'Safe asset');
        $zip->close();

        $this->assertSame($jsonData, $this->importHelper->readZipFile($zipPath));
        $this->assertStringEqualsFile($this->mediaDir.'/files/documents/readme.txt', 'Safe asset');
    }

    /**
     * A real campaign/email export is a single highly-compressible JSON blob that compresses far
     * better than 1:10. It must not be rejected as a zip bomb just because of its compression ratio.
     */
    public function testReadFromZipAllowsHighlyCompressibleJson(): void
    {
        $jsonData   = ['custom_html' => str_repeat('<p>Highly compressible content.</p>', 5000)];
        $jsonOutput = json_encode($jsonData, JSON_THROW_ON_ERROR);

        $zipFilePath   = $this->exportHelper->writeToZipFile($jsonOutput, [], '');
        $this->paths[] = $zipFilePath;

        $this->assertSame($jsonData, $this->importHelper->readZipFile($zipFilePath));
    }

    /**
     * An entry whose uncompressed size exceeds the threshold and still compresses beyond the
     * allowed ratio is treated as a zip bomb and rejected.
     */
    public function testReadFromZipRejectsLargeZipBomb(): void
    {
        $tempDir = sys_get_temp_dir();

        // 2MB of a single repeated byte: well above the 1MB ratio-check threshold and compresses
        // far beyond 1:10.
        $bombPath = $tempDir.'/zip_bomb_'.uniqid().'.txt';
        file_put_contents($bombPath, str_repeat('A', 2 * 1024 * 1024));
        $this->paths[] = $bombPath;

        $jsonOutput    = json_encode(['key' => 'value'], JSON_THROW_ON_ERROR);
        $zipFilePath   = $this->exportHelper->writeToZipFile($jsonOutput, [$bombPath], '');
        $this->paths[] = $zipFilePath;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Suspicious compression ratio');

        $this->importHelper->readZipFile($zipFilePath);
    }
}
