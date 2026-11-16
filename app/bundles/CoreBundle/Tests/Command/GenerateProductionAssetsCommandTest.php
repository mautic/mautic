<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class GenerateProductionAssetsCommandTest extends MauticMysqlTestCase
{
    private const STALE_LIBRARIES_CSS = 'stale libraries css';

    private const CKEDITOR_FILE_NAME      = 'ckeditor.js';

    private const TEMP_CKEDITOR_FILE_NAME = 'temp_ckeditor.js';

    private Filesystem $filesystem;

    private string $ckeditorFilePath;

    private string $librariesCssFilePath;

    private string $currentThemePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = self::getContainer()->get(Filesystem::class);
        /** @var PathsHelper $pathHelper */
        $pathHelper       = self::getContainer()->get(PathsHelper::class);

        $this->ckeditorFilePath     = $pathHelper->getVendorRootPath().'/media/libraries/ckeditor/';
        $this->librariesCssFilePath = $pathHelper->getSystemPath('media', true).'/css/libraries.css';
        $this->currentThemePath     = $pathHelper->getSystemPath('current_theme', true);
    }

    public function testAssetGenerateCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:assets:generate');
        $this->assertStringContainsString('Production assets have been regenerated.', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testAssetGenerateCommandClearsStaleLibrariesCss(): void
    {
        $hadOriginalFile = $this->filesystem->exists($this->librariesCssFilePath);
        $originalContent = $hadOriginalFile
            ? $this->filesystem->readFile($this->librariesCssFilePath)
            : null;

        try {
            $this->filesystem->dumpFile($this->librariesCssFilePath, self::STALE_LIBRARIES_CSS);

            $commandTester = $this->testSymfonyCommand('mautic:assets:generate');

            $this->assertSame(0, $commandTester->getStatusCode());
            $this->assertSame('', $this->filesystem->readFile($this->librariesCssFilePath));
        } finally {
            if ($hadOriginalFile) {
                $this->filesystem->dumpFile($this->librariesCssFilePath, $originalContent);
            } elseif ($this->filesystem->exists($this->librariesCssFilePath)) {
                unlink($this->librariesCssFilePath);
            }
        }
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testAssetGenerateCommandPreservesCurrentLibrariesOverride(): void
    {
        $overrideDir  = $this->currentThemePath.'/css';
        $overrideFile = $overrideDir.'/libraries_custom.css';

        $hadOriginalOverride = $this->filesystem->exists($overrideFile);
        $originalOverride    = $hadOriginalOverride
            ? $this->filesystem->readFile($overrideFile)
            : null;

        $hadOriginalLibraries = $this->filesystem->exists($this->librariesCssFilePath);
        $originalLibraries    = $hadOriginalLibraries
            ? $this->filesystem->readFile($this->librariesCssFilePath)
            : null;

        try {
            $this->filesystem->mkdir($overrideDir);
            $this->filesystem->dumpFile($overrideFile, '.current-override { display: block; }');
            $this->filesystem->dumpFile($this->librariesCssFilePath, self::STALE_LIBRARIES_CSS);

            $commandTester = $this->testSymfonyCommand('mautic:assets:generate');

            $this->assertSame(0, $commandTester->getStatusCode());
            $this->assertStringContainsString(
                '.current-override',
                $this->filesystem->readFile($this->librariesCssFilePath)
            );
            $this->assertStringNotContainsString(
                self::STALE_LIBRARIES_CSS,
                $this->filesystem->readFile($this->librariesCssFilePath)
            );
        } finally {
            if ($hadOriginalOverride) {
                $this->filesystem->dumpFile($overrideFile, $originalOverride);
            } elseif ($this->filesystem->exists($overrideFile)) {
                unlink($overrideFile);
            }

            if ($hadOriginalLibraries) {
                $this->filesystem->dumpFile($this->librariesCssFilePath, $originalLibraries);
            } elseif ($this->filesystem->exists($this->librariesCssFilePath)) {
                unlink($this->librariesCssFilePath);
            }
        }
    }

    public function testCkeditorFileNotExist(): void
    {
        $ckeditorFilePath = $this->ckeditorFilePath.self::CKEDITOR_FILE_NAME;
        if ($this->filesystem->exists($ckeditorFilePath)) {
            $this->filesystem->rename($ckeditorFilePath, $this->ckeditorFilePath.self::TEMP_CKEDITOR_FILE_NAME);
        }

        $commandTester = $this->testSymfonyCommand('mautic:assets:generate');
        $this->assertStringContainsString("{$ckeditorFilePath} does not exist. Execute `npm install` to generate it.", $commandTester->getDisplay());
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    protected function beforeTearDown(): void
    {
        if ($this->filesystem->exists($this->ckeditorFilePath.self::TEMP_CKEDITOR_FILE_NAME)) {
            $this->filesystem->rename($this->ckeditorFilePath.self::TEMP_CKEDITOR_FILE_NAME, $this->ckeditorFilePath.self::CKEDITOR_FILE_NAME);
        }
    }
}
