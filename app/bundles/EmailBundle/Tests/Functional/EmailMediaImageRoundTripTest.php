<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Proves the full builder-image round-trip across the real export/import plumbing: a builder image is packed
 * into the archive on export, restored without tripping the zip-bomb guard on import, relocated into the
 * served media images directory, and its reference rewritten to a host-relative URL.
 */
final class EmailMediaImageRoundTripTest extends MauticMysqlTestCase
{
    /**
     * @var list<string>
     */
    private array $createdFiles = [];

    public function testBuilderImageSurvivesExportImportRoundTrip(): void
    {
        try {
            $this->runRoundTrip();
        } finally {
            foreach ($this->createdFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function runRoundTrip(): void
    {
        $container        = self::getContainer();
        $pathsHelper      = $container->get(PathsHelper::class);
        $exportHelper     = $container->get(ExportHelper::class);
        $importHelper     = $container->get(ImportHelper::class);
        $dispatcher       = $container->get(EventDispatcherInterface::class);
        // The helper is a private service; build it from the same dependencies the container would inject.
        $mediaImageHelper = new EmailMediaImageHelper(
            $container->get(PathsHelper::class),
            $container->get(CoreParametersHelper::class),
        );

        $imageDir = rtrim($pathsHelper->getImagePath(), '/');
        $filesDir = rtrim($pathsHelper->getMediaPath(), '/').'/files';
        $fileName = 'roundtrip_'.bin2hex(random_bytes(6)).'.png';
        $bytes    = 'fake-png-bytes';

        // The builder image as it lives on the exporting instance.
        $sourceFile           = $imageDir.'/'.$fileName;
        $this->createdFiles[] = $sourceFile;
        file_put_contents($sourceFile, $bytes);

        $customHtml = '<img src="https://origin.example.com/media/images/'.$fileName.'">';
        $emailData  = ['id' => 1, 'uuid' => 'round-trip', 'custom_html' => $customHtml, 'content' => []];

        // 1) Export: the listener collects the image so ExportHelper packs it into the archive.
        // The event's declared section type is loose; real export data holds a list of entities per name.
        $data = [[Email::ENTITY_NAME => [$emailData]]];
        // @phpstan-ignore argument.type
        $assetListEvent = $dispatcher->dispatch(new AssetExportListEvent($data));
        $assetList      = $assetListEvent->getList() ?? [];

        $this->assertContains($sourceFile, $assetList, 'The builder image must be collected for the export archive.');

        $zipFilePath          = $exportHelper->writeToZipFile((string) json_encode($data), $assetList, '');
        $this->createdFiles[] = $zipFilePath;

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipFilePath));
        $this->assertNotFalse($zip->locateName('assets/'.$fileName), 'The archive must contain the image file.');
        $zip->close();

        // Simulate importing onto a fresh instance: neither the served copy nor the restored copy exists yet.
        @unlink($sourceFile);
        $restoredFile         = $filesDir.'/'.$fileName;
        $this->createdFiles[] = $restoredFile;
        @unlink($restoredFile);

        // 2) Import: readZipFile must not flag the highly-compressible JSON as a zip bomb and restores assets.
        $importHelper->readZipFile($zipFilePath);
        $this->assertFileExists($restoredFile, 'The image is restored into the media files directory.');

        // 3) Import rewrite: relocate to the served images directory and produce a host-relative URL.
        $rewritten = $mediaImageHelper->restoreInHtml($customHtml);

        $this->assertSame('<img src="/media/images/'.$fileName.'">', $rewritten);
        $this->assertFileExists($imageDir.'/'.$fileName, 'The image is available where the builder serves images.');
        $this->assertSame($bytes, file_get_contents($imageDir.'/'.$fileName));
    }
}
