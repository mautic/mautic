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

    private $rootDir  = __DIR__.'/resource/paths';

    /**
     * @var MockObject|UserHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $userHelper;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $coreParametersHelper;

    private \Mautic\CoreBundle\Helper\PathsHelper $helper;

    protected function setUp(): void
    {
        $this->userHelper           = $this->createMock(UserHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                fn (string $key) => match ($key) {
                    'image_path' => 'media/images',
                    'tmp_path'   => __DIR__.'/resource/paths/tmp',
                    default      => '',
                }
            );
        $this->helper = new PathsHelper(
            $this->userHelper,
            $this->coreParametersHelper,
            $this->cacheDir,
            $this->logsDir,
            $this->rootDir
        );
    }

    public function testGetLocalConfigFile(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/config/local.php', realpath($this->helper->getLocalConfigurationFile()));
    }

    public function testGetCachePath(): void
    {
        $this->assertEquals($this->cacheDir, $this->helper->getCachePath());
    }

    public function testGetRootPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths', $this->helper->getRootPath());
    }

    public function testGetTemporaryPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/tmp', $this->helper->getTemporaryPath());
    }

    public function testGetLogsPath(): void
    {
        $this->assertEquals($this->logsDir, $this->helper->getLogsPath());
    }

    public function testGetImagesPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/media/images', $this->helper->getImagePath());
    }

    public function testGetTranslationsPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/translations', $this->helper->getTranslationsPath());
    }

    public function testGetThemesPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/themes', $this->helper->getThemesPath());
    }

    public function testGetAssetsPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/media', $this->helper->getAssetsPath());
    }

    public function testGetCoreBundlesPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/app/bundles', $this->helper->getCoreBundlesPath());
    }

    public function testGetPluginsPath(): void
    {
        $this->assertEquals(__DIR__.'/resource/paths/plugins', $this->helper->getPluginsPath());
    }
}
