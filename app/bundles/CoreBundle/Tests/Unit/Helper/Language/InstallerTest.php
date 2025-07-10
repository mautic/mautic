<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\Language;

use Mautic\CoreBundle\Helper\Language\Installer;
use Symfony\Component\Filesystem\Filesystem;

class InstallerTest extends \PHPUnit\Framework\TestCase
{
    public function testInstaller(): void
    {
        $translationsDirectory = __DIR__.'/../resource/language';

        $installer = new Installer($translationsDirectory.'/translations');

        $zipper  = new \ZipArchive();
        $zipper->open($translationsDirectory.'/es.zip');
        $zipper->extractTo($translationsDirectory.'/tmp');

        $this->assertFileExists($translationsDirectory.'/tmp/es');

        $installer->install($translationsDirectory.'/tmp', 'es');

        // did the installer create the language folder?
        $languagePath = $translationsDirectory.'/translations/es';
        $this->assertFileExists($languagePath);

        // did it copy the config?
        $this->assertFileExists($languagePath.'/config.json');

        // did it ignore the php config?
        $this->assertFileDoesNotExist($languagePath.'/config.php');

        // did it ignore the extra files?
        $this->assertFileDoesNotExist($languagePath.'/random.txt');
        $this->assertFileDoesNotExist($languagePath.'/RandomFolder');

        // did it create the bundles?
        $this->assertFileExists($languagePath.'/CoreBundle');
        $this->assertFileExists($languagePath.'/CampaignBundle');

        // did it copy the INI files?
        $this->assertFileExists($languagePath.'/CoreBundle/messages.ini');
        $this->assertFileExists($languagePath.'/CoreBundle/flashes.ini');
        $this->assertFileExists($languagePath.'/CampaignBundle/messages.ini');
        $this->assertFileExists($languagePath.'/CampaignBundle/flashes.ini');

        // did it ignore the bundle's extra files?
        $this->assertFileDoesNotExist($languagePath.'/CoreBundle/random.txt');

        // did the installer cleanup appropriately
        $this->assertFileExists($translationsDirectory.'/tmp/es');
        $installer->cleanup();
        $this->assertFileDoesNotExist($translationsDirectory.'/tmp/es');

        // cleanup the test
        (new Filesystem())->remove($languagePath);
    }
}
