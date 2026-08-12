<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class GenerateProductionAssetsCommandTest extends MauticMysqlTestCase
{
    private const string CKEDITOR_FILE_NAME      = 'ckeditor.js';

    private const string TEMP_CKEDITOR_FILE_NAME = 'temp_ckeditor.js';

    private Filesystem $filesystem;

    private string $ckeditorFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = self::getContainer()->get(Filesystem::class);
        /** @var PathsHelper $pathHelper */
        $pathHelper       = self::getContainer()->get(PathsHelper::class);

        $this->ckeditorFilePath = $pathHelper->getVendorRootPath().'/media/libraries/ckeditor/';
    }

    public function testAssetGenerateCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:assets:generate');
        $this->assertStringContainsString('Production assets have been regenerated.', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
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
