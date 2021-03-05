<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic Contributors.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuilderIntegrationsHelperTest extends TestCase
{
    /**
     * @var IntegrationsHelper|MockObject
     */
    private $integrationsHelper;

    /**
     * @var BuilderIntegrationsHelper
     */
    private $builderIntegrationsHelper;

    protected function setUp(): void
    {
        $this->integrationsHelper        = $this->createMock(IntegrationsHelper::class);
        $this->builderIntegrationsHelper = new BuilderIntegrationsHelper($this->integrationsHelper);
    }

    public function testBuilderNotFoundIfFeatureSupportedButNotEnabled(): void
    {
        $builder     = $this->createMock(BuilderInterface::class);
        $integration = new Integration();

        $builder->expects($this->once())
            ->method('isSupported')
            ->with('page')
            ->willReturn(true);

        $builder->expects($this->once())
            ->method('getIntegrationConfiguration')
            ->willReturn($integration);

        $this->builderIntegrationsHelper->addIntegration($builder);

        $this->expectException(IntegrationNotFoundException::class);

        $this->builderIntegrationsHelper->getBuilder('page');
    }

    public function testBuilderNotFoundIfFeatureIsNotSupported(): void
    {
        $builder = $this->createMock(BuilderInterface::class);
        $builder->expects($this->once())
            ->method('isSupported')
            ->with('page')
            ->willReturn(false);

        $builder->expects($this->never())
            ->method('getIntegrationConfiguration');

        $this->builderIntegrationsHelper->addIntegration($builder);

        $this->expectException(IntegrationNotFoundException::class);

        $this->builderIntegrationsHelper->getBuilder('page');
    }

    public function testBuilderFoundIfFeatureIsSupportedAndBuilderEnabled(): void
    {
        $builder = $this->createMock(BuilderInterface::class);

        $integration = new Integration();
        $integration->setIsPublished(true);

        $builder->expects($this->once())
            ->method('isSupported')
            ->with('page')
            ->willReturn(true);

        $builder->expects($this->once())
            ->method('getIntegrationConfiguration')
            ->willReturn($integration);

        $this->builderIntegrationsHelper->addIntegration($builder);

        $foundBuilder = $this->builderIntegrationsHelper->getBuilder('page');

        Assert::assertSame($builder, $foundBuilder);
    }

    public function testBuilderNamesAreReturned(): void
    {
        $builder1 = $this->createMock(BuilderInterface::class);
        $builder1->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('builder1');
        $builder1->expects($this->once())
            ->method('getDisplayName')
            ->willReturn('Builder One');
        $this->builderIntegrationsHelper->addIntegration($builder1);

        $builder2 = $this->createMock(BuilderInterface::class);
        $builder2->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('builder2');
        $builder2->expects($this->once())
            ->method('getDisplayName')
            ->willReturn('Builder Two');
        $this->builderIntegrationsHelper->addIntegration($builder2);

        Assert::assertSame(
            [
                'builder1' => 'Builder One',
                'builder2' => 'Builder Two',
            ],
            $this->builderIntegrationsHelper->getBuilderNames()
        );
    }
}
