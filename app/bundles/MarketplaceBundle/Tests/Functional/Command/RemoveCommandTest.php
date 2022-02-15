<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Command\RemoveCommand;
use Mautic\MarketplaceBundle\Model\ConsoleOutputModel;
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;

final class RemoveCommandTest extends AbstractMauticTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;
    private string $packageName;

    public function setUp(): void
    {
        parent::setUp();
        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->packageName = 'koco/mautic-recaptcha-bundle';
    }

    public function testRemoveCommand(): void
    {
        $composer    = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->with($this->packageName)
            ->willReturn(new ConsoleOutputModel(0, 'OK'));
        $composer->method('getMauticPluginPackages')
            ->willReturn(['koco/mautic-recaptcha-bundle']);
        $command = new RemoveCommand($composer, $this->logger);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $this->packageName],
            $command
        );

        Assert::assertSame(0, $result->getStatusCode());
    }

    public function testRemoveCommandWithInvalidPackageType(): void
    {
        $composer    = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->with($this->packageName)
            ->willReturn(new ConsoleOutputModel(0, 'OK'));
        $composer->method('getMauticPluginPackages')
            ->willReturn([]);
        $command = new RemoveCommand($composer, $this->logger);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $this->packageName],
            $command
        );

        Assert::assertSame(1, $result->getStatusCode());
    }

    public function testRemoveCommandWithComposerError(): void
    {
        $composer    = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->with($this->packageName)
            ->willReturn(new ConsoleOutputModel(1, 'Error while removing package'));
        $composer->method('getMauticPluginPackages')
            ->willReturn([]);
        $command = new RemoveCommand($composer, $this->logger);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $this->packageName],
            $command
        );

        Assert::assertSame(1, $result->getStatusCode());
    }
}
