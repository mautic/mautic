<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Command\InstallCommand;
use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Model\ConsoleOutputModel;
use Mautic\MarketplaceBundle\Model\PackageModel;
use PHPUnit\Framework\Assert;

final class InstallCommandTest extends AbstractMauticTestCase
{
    public function testInstallCommand(): void
    {
        $packageName = 'koco/mautic-recaptcha-bundle';
        $payload     = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/detail.json'), true);

        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('install')
            ->with($packageName)
            ->willReturn(new ConsoleOutputModel(0, 'OK'));

        $packageModel = $this->createMock(PackageModel::class);
        $packageModel->method('getPackageDetail')
            ->with($packageName)
            ->willReturn(PackageDetail::fromArray($payload['package']));

        $command = new InstallCommand($composer, $packageModel);

        $result = $this->testSymfonyCommand(
            'mautic:marketplace:install',
            ['package' => $packageName],
            $command
        );

        Assert::assertSame(0, $result->getStatusCode());
    }
}
