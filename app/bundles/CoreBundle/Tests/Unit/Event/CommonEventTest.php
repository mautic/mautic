<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Event;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Event\AssetPostSaveEvent;
use Mautic\AssetBundle\Event\AssetPreSaveEvent;
use PHPUnit\Framework\TestCase;

final class CommonEventTest extends TestCase
{
    public function testChangesReadInPreEventAreLostWithoutInheritance(): void
    {
        $asset = new Asset();
        $asset->setTitle('New title');

        $preSaveEvent = new AssetPreSaveEvent($asset);
        $this->assertSame(['title' => [null, 'New title']], $preSaveEvent->getChanges());

        // reading changes resets them on the entity
        $postSaveEvent = new AssetPostSaveEvent($asset);
        $this->assertSame([], $postSaveEvent->getChanges());
    }

    public function testInheritStateFromCarriesChangesToPostEvent(): void
    {
        $asset = new Asset();
        $asset->setTitle('New title');

        $preSaveEvent = new AssetPreSaveEvent($asset);
        $preSaveEvent->getChanges();

        $postSaveEvent = new AssetPostSaveEvent($asset);
        $postSaveEvent->inheritStateFrom($preSaveEvent);

        $this->assertSame(['title' => [null, 'New title']], $postSaveEvent->getChanges());
    }
}
