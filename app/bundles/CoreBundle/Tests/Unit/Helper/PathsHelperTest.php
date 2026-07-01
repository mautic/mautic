<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class PathsHelperTest extends TestCase
{
    private string $cacheDir = __DIR__.'/resource/paths/cache';

    private string $logsDir  = __DIR__.'/resource/paths/logs';

    private string $rootDir  = __DIR__.'/resource/paths';

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    private PathsHelper $helper;

    protected function setUp(): void
    {
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
            $this->createStub(UserHelper::class), $this->coreParametersHelper, $this->cacheDir, $this->logsDir, $this->rootDir
        );
    }

    public function testGetLocalConfigFile(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/config/local.php', realpath($this->helper->getLocalConfigurationFile()));
    }

    public function testGetCachePath(): void
    {
        $this->assertSame($this->cacheDir, $this->helper->getCachePath());
    }

    public function testGetRootPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths', $this->helper->getRootPath());
    }

    public function testGetTemporaryPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/tmp', $this->helper->getTemporaryPath());
    }

    public function testGetLogsPath(): void
    {
        $this->assertSame($this->logsDir, $this->helper->getLogsPath());
    }

    public function testGetImagesPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/media/images', $this->helper->getImagePath());
    }

    public function testGetTranslationsPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/translations', $this->helper->getTranslationsPath());
    }

    public function testGetThemesPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/themes', $this->helper->getThemesPath());
    }

    public function testGetAssetsPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/media', $this->helper->getAssetsPath());
    }

    public function testGetCoreBundlesPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/app/bundles', $this->helper->getCoreBundlesPath());
    }

    public function testGetPluginsPath(): void
    {
        $this->assertSame(__DIR__.'/resource/paths/plugins', $this->helper->getPluginsPath());
    }

    public function testGetImportCampaignsPath(): void
    {
        $campaignImportPath = __DIR__.'/resource/paths/import/campaigns';

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParametersHelper->method('get')
            ->willReturnCallback(
                fn (string $key) => match ($key) {
                    'import_campaigns_dir' => $campaignImportPath,
                    'image_path'           => 'media/images',
                    'tmp_path'             => __DIR__.'/resource/paths/tmp',
                    default                => '',
                }
            );

        $helper = new PathsHelper($this->createStub(UserHelper::class), $this->coreParametersHelper, $this->cacheDir, $this->logsDir, $this->rootDir);

        $this->assertSame($campaignImportPath, $helper->getImportCampaignsPath());
    }

    public function testTempDirectoryIsCreatedIfItDoesNotExist(): void
    {
        $tempPath = __DIR__.'/resource/paths/no_exist/tmp';

        /** @var UserHelper&MockObject $userHelper */
        $userHelper = $this->createStub(UserHelper::class);

        /** @var CoreParametersHelper&MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(
                fn (string $key) => match ($key) {
                    'tmp_path' => $tempPath,
                    default    => '',
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
                fn (string $key) => match ($key) {
                    'dashboard_import_dir' => $dashboardDir,
                    default                => '',
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
