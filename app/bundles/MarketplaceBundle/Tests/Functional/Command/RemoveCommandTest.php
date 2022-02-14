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
    public function testRemoveCommand(): void
    {
        $packageName = 'koco/mautic-recaptcha-bundle';
        $composer    = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->with($packageName)
            ->willReturn(new ConsoleOutputModel(0, 'OK'));
        $composer->method('getMauticPluginPackages')
            ->willReturn(['koco/mautic-recaptcha-bundle']);
        $logger  = $this->createMock(LoggerInterface::class);
        $command = new RemoveCommand($composer, $logger);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $packageName],
            $command
        );

        Assert::assertSame(0, $result->getStatusCode());
    }
}
