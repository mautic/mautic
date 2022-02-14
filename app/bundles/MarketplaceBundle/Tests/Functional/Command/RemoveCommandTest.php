<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Command\RemoveCommand;
use Mautic\MarketplaceBundle\Model\ConsoleOutputModel;
use PHPUnit\Framework\Assert;

final class RemoveCommandTest extends AbstractMauticTestCase
{
    public function testInstallCommand(): void
    {
        $packageName = 'koco/mautic-recaptcha-bundle';
        $composer    = $this->createMock(ComposerHelper::class);
        $composer->method('remove')
            ->with($packageName)
            ->willReturn(new ConsoleOutputModel(0, 'OK'));
        $command = new RemoveCommand($composer);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:remove',
            ['package' => $packageName],
            $command
        );

        Assert::assertSame(Command::SUCCESS, $result->getStatusCode());
    }
}
