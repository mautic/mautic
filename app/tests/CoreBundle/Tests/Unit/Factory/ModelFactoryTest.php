<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Factory;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\PointBundle\Model\TriggerModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[AllowMockObjectsWithoutExpectations]
final class ModelFactoryTest extends TestCase
{
    /**
     * @var MockObject&ServiceLocator
     */
    private MockObject $container;

    /**
     * @var ModelFactory<object>
     */
    private ModelFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ServiceLocator::class);
        $this->factory   = new ModelFactory($this->container);
    }

    public function testGetModelLooksUpByModelKey(): void
    {
        $triggerModel = $this->createStub(TriggerModel::class);
        $modelKey     = 'point.trigger';

        $this->container->expects($this->once())
            ->method('has')
            ->with($modelKey)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with($modelKey)
            ->willReturn($triggerModel);

        $this->assertInstanceOf(TriggerModel::class, $this->factory->getModel($modelKey));
    }

    public function testGetModelExpandsSingleWordKeyToBundleDotName(): void
    {
        $pointModel = $this->createStub(TriggerModel::class);

        $this->container->expects($this->once())
            ->method('has')
            ->with('point.point')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('point.point')
            ->willReturn($pointModel);

        $this->assertInstanceOf(TriggerModel::class, $this->factory->getModel('point'));
    }

    public function testGetModelThrowsWhenModelKeyIsNotRegistered(): void
    {
        $this->container->method('has')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);

        $this->factory->getModel('point.missing');
    }
}
