<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class GenerateProductionAssetsCommandTest extends MauticMysqlTestCase
{
    private const CKEDITOR_FILE_NAME      = 'ckeditor.js';

    private const TEMP_CKEDITOR_FILE_NAME = 'temp_ckeditor.js';

    private Filesystem $filesystem;

    private string $ckeditorFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = self::getContainer()->get('mautic.filesystem');
        $pathHelper       = self::getContainer()->get('mautic.helper.paths');

        $this->ckeditorFilePath = $pathHelper->getVendorRootPath().'/media/libraries/ckeditor/';
    }

    public function testAssetGenerateCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:assets:generate');
        $this->assertStringContainsString('Production assets have been regenerated.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCkeditorFileNotExist(): void
    {
        $ckeditorFilePath = $this->ckeditorFilePath.self::CKEDITOR_FILE_NAME;
        if ($this->filesystem->exists($ckeditorFilePath)) {
            $this->filesystem->rename($ckeditorFilePath, $this->ckeditorFilePath.self::TEMP_CKEDITOR_FILE_NAME);
        }

        $commandTester = $this->testSymfonyCommand('mautic:assets:generate');
        $this->assertStringContainsString("{$ckeditorFilePath} does not exist. Execute `npm install` to generate it.", $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    protected function beforeTearDown(): void
    {
        if ($this->filesystem->exists($this->ckeditorFilePath.self::TEMP_CKEDITOR_FILE_NAME)) {
            $this->filesystem->rename($this->ckeditorFilePath.self::TEMP_CKEDITOR_FILE_NAME, $this->ckeditorFilePath.self::CKEDITOR_FILE_NAME);
        }
    }
}
