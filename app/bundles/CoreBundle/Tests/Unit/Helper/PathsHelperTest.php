<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PathsHelperTest extends TestCase
{
    private $cacheDir = __DIR__.'/resource/paths/cache';
    private $logsDir  = __DIR__.'/resource/paths/logs';
    private $rootDir  = __DIR__.'/resource/paths/app';

    /**
     * @var MockObject|UserHelper
     */
    private $userHelper;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var PathsHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->userHelper           = $this->createMock(UserHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'image_path':
                            return 'media/images';
                        case 'tmp_path':
                            return __DIR__.'/resource/paths/tmp';
                        default:
                            return '';
                    }
                }
            );
        $this->helper = new PathsHelper(
            $this->userHelper, $this->coreParametersHelper, $this->cacheDir, $this->logsDir, $this->rootDir
        );
    }

    public function testGetLocalConfigFile()
    {
        $this->assertEquals(__DIR__.'/resource/paths/app/config/local.php', $this->helper->getLocalConfigurationFile());
    }

    public function testGetCachePath()
    {
        $this->assertEquals($this->cacheDir, $this->helper->getCachePath());
    }

    public function testGetRootPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths', $this->helper->getRootPath());
    }

    public function testGetTemporaryPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/tmp', $this->helper->getTemporaryPath());
    }

    public function testGetLogsPath()
    {
        $this->assertEquals($this->logsDir, $this->helper->getLogsPath());
    }

    public function testGetImagesPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/media/images', $this->helper->getImagePath());
    }

    public function testGetTranslationsPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/translations', $this->helper->getTranslationsPath());
    }

    public function testGetThemesPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/themes', $this->helper->getThemesPath());
    }

    public function testGetAssetsPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/media', $this->helper->getAssetsPath());
    }

    public function testGetCoreBundlesPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/app/bundles', $this->helper->getCoreBundlesPath());
    }

    public function testGetPluginsPath()
    {
        $this->assertEquals(__DIR__.'/resource/paths/plugins', $this->helper->getPluginsPath());
    }
}
