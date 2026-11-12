<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Event;

use Mautic\CoreBundle\Event\EntityExportEvent;
use PHPUnit\Framework\TestCase;

final class EntityExportEventTest extends TestCase
{
    public function testAddEntitiesKeepsTheEntitiesAlreadyAdded(): void
    {
        $event = new EntityExportEvent('emails', 1);
        $event->addEntity('emails', ['id' => 1, 'name' => 'Sent by the campaign']);
        $event->addEntity('forms', ['id' => 2, 'name' => 'Unsubscribe form']);

        // Like an email exported along with the unsubscribe form, and that form's action sending another email
        $event->addEntities([
            'emails' => [['id' => 3, 'name' => 'Sent by the form'], ['id' => 1, 'name' => 'Exported again']],
            'forms'  => [['id' => 2, 'name' => 'Exported again']],
        ]);

        $this->assertSame([
            'emails' => [['id' => 1, 'name' => 'Sent by the campaign'], ['id' => 3, 'name' => 'Sent by the form']],
            'forms'  => [['id' => 2, 'name' => 'Unsubscribe form']],
        ], $event->getEntities());
    }

    public function testAddEntityDataAddsToTheEntityWithTheId(): void
    {
        $event = new EntityExportEvent('emails', 1);
        $event->addEntity('emails', ['id' => 1, 'name' => 'One']);
        $event->addEntity('emails', ['id' => 2, 'name' => 'Two']);

        $event->addEntityData('emails', 2, ['plugin' => ['data' => 'value']]);
        $event->addEntityData('emails', 5, ['plugin' => ['data' => 'not added']]);

        $this->assertSame([
            'emails' => [['id' => 1, 'name' => 'One'], ['id' => 2, 'name' => 'Two', 'plugin' => ['data' => 'value']]],
        ], $event->getEntities());
    }
}
