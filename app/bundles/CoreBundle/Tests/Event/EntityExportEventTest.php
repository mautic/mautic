<?php

namespace Mautic\CoreBundle\Tests\Event;

use Mautic\CoreBundle\Event\EntityExportEvent;
use PHPUnit\Framework\TestCase;

class EntityExportEventTest extends TestCase
{
    private EntityExportEvent $event;

    protected function setUp(): void
    {
        $this->event = new EntityExportEvent('campaign', 123);
    }

    public function testConstructorAndGetters(): void
    {
        $this->assertSame('campaign', $this->event->getEntityName());
        $this->assertSame(123, $this->event->getEntityId());
    }

    public function testAddEntityAndGetEntities(): void
    {
        $entityData = ['id' => 1, 'name' => 'Test Campaign'];
        $this->event->addEntity('campaign', $entityData);

        $entities = $this->event->getEntities();

        $this->assertArrayHasKey('campaign', $entities);
        $this->assertCount(1, $entities['campaign']);
        $this->assertSame($entityData, $entities['campaign'][0]);
    }

    public function testAddEntities(): void
    {
        $entities = [
            'campaign' => [
                ['id' => 1, 'name' => 'Test Campaign'],
                ['id' => 2, 'name' => 'Second Campaign'],
            ],
            'segment' => [
                ['id' => 10, 'name' => 'Test Segment'],
            ],
        ];

        $this->event->addEntities($entities);

        $retrievedEntities = $this->event->getEntities();

        $this->assertArrayHasKey('campaign', $retrievedEntities);
        $this->assertArrayHasKey('segment', $retrievedEntities);
        $this->assertCount(2, $retrievedEntities['campaign']);
        $this->assertCount(1, $retrievedEntities['segment']);
    }

    public function testAddDependencyEntityAndGetDependencies(): void
    {
        $dependencyData = ['id' => 5, 'name' => 'Dependency Campaign'];
        $this->event->addDependencyEntity('campaign', $dependencyData);

        $dependencies = $this->event->getDependencies();

        $this->assertArrayHasKey('campaign', $dependencies);
        $this->assertCount(1, $dependencies['campaign']);
        $this->assertSame($dependencyData, $dependencies['campaign'][0]);
    }

    public function testAddDependencies(): void
    {
        $dependencies = [
            'campaign' => [
                ['id' => 3, 'name' => 'Dependency Campaign'],
            ],
            'form' => [
                ['id' => 7, 'name' => 'Dependency Form'],
            ],
        ];

        $this->event->addDependencies($dependencies);

        $retrievedDependencies = $this->event->getDependencies();

        $this->assertArrayHasKey('campaign', $retrievedDependencies);
        $this->assertArrayHasKey('form', $retrievedDependencies);
        $this->assertCount(1, $retrievedDependencies['campaign']);
        $this->assertCount(1, $retrievedDependencies['form']);
    }
}
