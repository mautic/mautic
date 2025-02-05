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
        $this->assertNotEmpty($entities['campaign']);

        $this->assertArrayHasKey(0, $entities['campaign']);

        $this->assertSame($entityData, $entities['campaign'][0]);
    }

    public function testAddEntities(): void
    {
        $entities = [
            'campaign' => [
                'first'  => ['id' => 1, 'name' => 'Test Campaign'],
                'second' => ['id' => 2, 'name' => 'Second Campaign'],
            ],
            'segment' => [
                'only' => ['id' => 10, 'name' => 'Test Segment'],
            ],
        ];

        $this->event->addEntities($entities);

        $retrievedEntities = $this->event->getEntities();

        $this->assertArrayHasKey('campaign', $retrievedEntities);
        $this->assertArrayHasKey('segment', $retrievedEntities);
        $this->assertNotEmpty($retrievedEntities['campaign']);
        $this->assertNotEmpty($retrievedEntities['segment']);

        $this->assertArrayHasKey('first', $retrievedEntities['campaign']);
        $this->assertArrayHasKey('second', $retrievedEntities['campaign']);
        $this->assertArrayHasKey('only', $retrievedEntities['segment']);
    }

    public function testAddDependencyEntityAndGetDependencies(): void
    {
        $dependencyData = ['id' => 5, 'name' => 'Dependency Campaign'];
        $this->event->addDependencyEntity('campaign', $dependencyData);

        $dependencies = $this->event->getDependencies();

        $this->assertArrayHasKey('campaign', $dependencies);
        $this->assertNotEmpty($dependencies['campaign']);

        $this->assertArrayHasKey(0, $dependencies['campaign']);

        $this->assertSame($dependencyData, $dependencies['campaign'][0]);
    }

    public function testAddDependencies(): void
    {
        $dependencies = [
            'campaign' => [
                'dependency1' => ['id' => 3, 'name' => 'Dependency Campaign'],
            ],
            'form' => [
                'dependency2' => ['id' => 7, 'name' => 'Dependency Form'],
            ],
        ];

        $this->event->addDependencies($dependencies);

        $retrievedDependencies = $this->event->getDependencies();

        $this->assertArrayHasKey('campaign', $retrievedDependencies);
        $this->assertArrayHasKey('form', $retrievedDependencies);
        $this->assertNotEmpty($retrievedDependencies['campaign']);
        $this->assertNotEmpty($retrievedDependencies['form']);

        $this->assertArrayHasKey('dependency1', $retrievedDependencies['campaign']);
        $this->assertArrayHasKey('dependency2', $retrievedDependencies['form']);
    }
}
