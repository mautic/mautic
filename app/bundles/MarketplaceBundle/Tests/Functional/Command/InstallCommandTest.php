<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Command\InstallCommand;
use Mautic\MarketplaceBundle\DTO\ConsoleOutput;
use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;

final class InstallCommandTest extends AbstractMauticTestCase
{
    /**
     * @var MockObject&ComposerHelper
     */
    private MockObject $composerHelper;

    /**
     * @var MockObject&PackageModel
     */
    private MockObject $packageModel;

    /**
     * @var MockObject&ResourceInstallerInterface
     */
    private MockObject $resourceInstaller;

    private MockObject&UserModel $userModel;

    private MockObject&UserRepository $userRepository;

    private string $packageName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->composerHelper    = $this->createMock(ComposerHelper::class);
        $this->packageModel      = $this->createMock(PackageModel::class);
        $this->resourceInstaller = $this->createMock(ResourceInstallerInterface::class);
        $this->userModel         = $this->createMock(UserModel::class);
        $this->userRepository    = $this->createMock(UserRepository::class);
        $this->packageName       = 'koco/mautic-recaptcha-bundle';

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('isAdmin')->willReturn(true);
        $this->userModel->method('getEntity')->willReturn($user);
    }

    public function testInstallCommand(): void
    {
        $this->packageModel->method('getPackageDetail')
            ->with($this->packageName)
            ->willReturn($this->getPackageDetail());

        $this->composerHelper->method('install')
            ->with($this->packageName)
            ->willReturn(new ConsoleOutput(0, 'OK'));

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $this->packageName],
            $command
        );

        $this->assertSame(0, $result->getStatusCode());
    }

    public function testInstallCommandWithDryRun(): void
    {
        $this->packageModel->method('getPackageDetail')
            ->with($this->packageName)
            ->willReturn($this->getPackageDetail());

        $this->composerHelper->method('install')
            ->with($this->packageName)
            ->willReturn(new ConsoleOutput(0, 'OK'));

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $this->packageName, '--dry-run' => null],
            $command
        );

        $this->assertSame(0, $result->getStatusCode());
        $this->assertStringContainsString('dry-running this installation', $result->getDisplay());
    }

    public function testInstallCommandWithNonExistingPackage(): void
    {
        $packageName = 'mautic/non-existent-plugin';

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willThrowException(new ApiException('Package not found', 404));

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $this->expectException(\InvalidArgumentException::class);

        $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName],
            $command
        );
    }

    public function testInstallCommandWithComposerNotAvailable(): void
    {
        $packageName = 'mautic/non-existent-plugin';

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willThrowException(new ApiException('Internal Server Error', 500));

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $this->expectException(\Exception::class);

        $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName],
            $command
        );
    }

    public function testInstallCommandWithWrongPackageType(): void
    {
        $packageName                      = 'mautic/package-with-wrong-type';
        $packageDetail                    = $this->getPackageDetail();
        $packageDetail->packageBase->type = 'non-existent-type';

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willReturn($packageDetail);

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $this->expectException(\Exception::class);

        $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName],
            $command
        );
    }

    public function testInstallCommandWithFailedComposerCommand(): void
    {
        $packageName = 'mautic/crash-package';

        $this->composerHelper->method('install')
            ->with($packageName)
            ->willReturn(new ConsoleOutput(1, 'Something went wrong during the installation'));

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willReturn($this->getPackageDetail());

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);
        $result  = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
        $this->assertSame("Installing mautic/crash-package, this might take a while...\nError while installing this plugin.\nSomething went wrong during the installation\n", $result->getDisplay());
    }

    public function testInstallResourceCommand(): void
    {
        $packageName = 'vukovicpredrag/mautic-test-campaign-template';

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('install')
            ->with($packageName, $this->anything())
            ->willReturn(['success' => true, 'summary' => [], 'errors' => []]);

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName, '--user-id' => '1'],
            $command
        );

        $this->assertSame(0, $result->getStatusCode());
        $this->assertStringContainsString('has successfully been installed', $result->getDisplay());
    }

    public function testInstallResourceCommandWithDryRun(): void
    {
        $packageName = 'vukovicpredrag/mautic-test-campaign-template';

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willReturn($this->getResourcePackageDetail());

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName, '--dry-run' => null],
            $command
        );

        $this->assertSame(0, $result->getStatusCode());
        $this->assertStringContainsString('dry-run mode', $result->getDisplay());
    }

    public function testInstallResourceCommandWithFailure(): void
    {
        $packageName = 'vukovicpredrag/mautic-test-campaign-template';

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('install')
            ->with($packageName, $this->anything())
            ->willReturn(['success' => false, 'summary' => [], 'errors' => ['Import failed']]);

        $command = new InstallCommand($this->composerHelper, $this->packageModel, $this->resourceInstaller, $this->userModel, $this->userRepository);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName, '--user-id' => '1'],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
        $this->assertStringContainsString('Error while installing this resource', $result->getDisplay());
    }

    private function getPackageDetail(): PackageDetail
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/detail.json'), true);

        return PackageDetail::fromArray($payload['package']);
    }

    private function getResourcePackageDetail(): PackageDetail
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/detail_resource.json'), true);

        return PackageDetail::fromArray($payload['package']);
    }
}
