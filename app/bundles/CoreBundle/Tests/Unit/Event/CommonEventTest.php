<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Event;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreSave;
use PHPUnit\Framework\TestCase;

final class CommonEventTest extends TestCase
{
    public function testChangesReadInPreEventAreLostWithoutInheritance(): void
    {
        $asset = new Asset();
        $asset->setTitle('New title');

        $preSaveEvent = new EntityPreSave($asset);
        $this->assertSame(['title' => [null, 'New title']], $preSaveEvent->getChanges());

        // reading changes resets them on the entity
        $postSaveEvent = new EntityPostSave($asset);
        $this->assertSame([], $postSaveEvent->getChanges());
    }

    public function testInheritStateFromCarriesChangesToPostEvent(): void
    {
        $asset = new Asset();
        $asset->setTitle('New title');

        $preSaveEvent = new EntityPreSave($asset);
        $preSaveEvent->getChanges();

        $postSaveEvent = new EntityPostSave($asset);
        $postSaveEvent->inheritStateFrom($preSaveEvent);

        $this->assertSame(['title' => [null, 'New title']], $postSaveEvent->getChanges());
    }
}
