<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Command\RemoveCommand;
use Mautic\MarketplaceBundle\DTO\ConsoleOutput;
use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class RemoveCommandTest extends AbstractMauticTestCase
{
    private \PHPUnit\Framework\MockObject\Stub&LoggerInterface $logger;

    private MockObject&PackageModel $packageModel;

    private MockObject&ResourceInstallerInterface $resourceInstaller;

    private string $packageName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger            = $this->createStub(LoggerInterface::class);
        $this->packageModel      = $this->createMock(PackageModel::class);
        $this->resourceInstaller = $this->createMock(ResourceInstallerInterface::class);
        $this->packageName       = 'koco/mautic-recaptcha-bundle';
    }

    public function testRemoveCommand(): void
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->expects($this->once())->method('remove')
            ->with($this->packageName)
            ->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('getMauticPluginPackages')
            ->willReturn(['koco/mautic-recaptcha-bundle']);

        $this->packageModel->method('getPackageDetail')
            ->with($this->packageName)
            ->willReturn($this->getPluginPackageDetail());

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $this->packageName],
            $command
        );

        $this->assertSame(0, $result->getStatusCode());
    }

    public function testRemoveCommandWithInvalidPackageType(): void
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('getMauticPluginPackages')
            ->willReturn([]);

        $this->packageModel->method('getPackageDetail')
            ->with($this->packageName)
            ->willReturn($this->getPluginPackageDetail());

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $this->packageName],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
    }

    public function testRemoveCommandWithComposerError(): void
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->willReturn(new ConsoleOutput(1, 'Error while removing package'));
        $composer->method('getMauticPluginPackages')
            ->willReturn(['koco/mautic-recaptcha-bundle']);

        $this->packageModel->method('getPackageDetail')
            ->with($this->packageName)
            ->willReturn($this->getPluginPackageDetail());

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $this->packageName],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
    }

    public function testRemoveResourceCommand(): void
    {
        $resourcePackageName = 'vukovicpredrag/mautic-test-campaign-template';
        $composer            = $this->createStub(ComposerHelper::class);

        $this->packageModel->method('getPackageDetail')
            ->with($resourcePackageName)
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with($resourcePackageName)
            ->willReturn(true);

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $resourcePackageName],
            $command
        );

        $this->assertSame(0, $result->getStatusCode());
        $this->assertStringContainsString('has successfully been removed', $result->getDisplay());
    }

    public function testRemoveResourceCommandWhenNotInstalled(): void
    {
        $resourcePackageName = 'vukovicpredrag/mautic-test-campaign-template';
        $composer            = $this->createStub(ComposerHelper::class);

        $this->packageModel->method('getPackageDetail')
            ->with($resourcePackageName)
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with($resourcePackageName)
            ->willReturn(false);

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $resourcePackageName],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
        $this->assertStringContainsString('is not currently installed', $result->getDisplay());
    }

    public function testRemoveResourceCommandWithError(): void
    {
        $resourcePackageName = 'vukovicpredrag/mautic-test-campaign-template';
        $composer            = $this->createStub(ComposerHelper::class);

        $this->packageModel->method('getPackageDetail')
            ->with($resourcePackageName)
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with($resourcePackageName)
            ->willReturn(true);

        $this->resourceInstaller->method('uninstall')
            ->with($resourcePackageName)
            ->willThrowException(new \RuntimeException('Failed to delete entities'));

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $resourcePackageName],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
        $this->assertStringContainsString('Error while removing resource', $result->getDisplay());
    }

    public function testRemoveCommandWithNonExistingPackage(): void
    {
        $packageName = 'mautic/non-existent-plugin';
        $composer    = $this->createStub(ComposerHelper::class);

        $this->packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willThrowException(new ApiException('Package not found', 404));

        $command = new RemoveCommand($composer, $this->logger, $this->packageModel, $this->resourceInstaller);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $packageName],
            $command
        );

        $this->assertSame(1, $result->getStatusCode());
        $this->assertStringContainsString('not found', $result->getDisplay());
    }

    private function getPluginPackageDetail(): PackageDetail
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
