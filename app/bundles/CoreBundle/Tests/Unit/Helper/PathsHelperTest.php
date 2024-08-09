<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class PathsHelperTest extends TestCase
{
    private $cacheDir = __DIR__.'/resource/paths/cache';

    private $logsDir  = __DIR__.'/resource/paths/logs';

    private $rootDir  = __DIR__.'/resource/paths';

    /**
     * @var MockObject|UserHelper
     */
    private MockObject $userHelper;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    private PathsHelper $helper;

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
            $this->userHelper, $this->coreParametersHelper, $this->cacheDir, $this->logsDir, $this->rootDir
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

    public function testTempDirectoryIsCreatedIfItDoesNotExist(): void
    {
        $tempPath = __DIR__.'/resource/paths/no_exist/tmp';

        /** @var UserHelper&MockObject $userHelper */
        $userHelper = $this->createMock(UserHelper::class);

        /** @var CoreParametersHelper&MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(
                function (string $key) use ($tempPath) {
                    switch ($key) {
                        case 'tmp_path':
                            return $tempPath;
                        default:
                            return '';
                    }
                }
            );

        $this->assertFileDoesNotExist($tempPath);

        $helper = new PathsHelper($userHelper, $coreParametersHelper, $this->cacheDir, $this->logsDir, $this->rootDir);

        $helper->getSystemPath('tmp');

        $this->assertFileExists($tempPath);

        // Cleanup
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/resource/paths/no_exist');
    }

    public function testUserDashboardDirectoryIsCreatedIfItDoesNotExist(): void
    {
        $dashboardDir = __DIR__.'/resource/paths/no_exist/dashboard';

        /** @var UserHelper&MockObject $userHelper */
        $userHelper           = $this->createMock(UserHelper::class);
        $user                 = $this->createMock(User::class);
        $user->method('getId')
            ->willReturn(1);
        $userHelper->method('getUser')
            ->willReturn($user);

        /** @var CoreParametersHelper&MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(
                function (string $key) use ($dashboardDir) {
                    switch ($key) {
                        case 'dashboard_import_dir':
                            return $dashboardDir;
                        default:
                            return '';
                    }
                }
            );

        $this->assertFileDoesNotExist($dashboardDir);

        $helper = new PathsHelper($userHelper, $coreParametersHelper, $this->cacheDir, $this->logsDir, $this->rootDir);
        $helper->getSystemPath('dashboard.user');
        $this->assertFileExists($dashboardDir.'/1');

        // Cleanup
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/resource/paths/no_exist');
    }
}
