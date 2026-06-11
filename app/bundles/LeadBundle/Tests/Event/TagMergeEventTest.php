<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Event\TagMergeEvent;
use PHPUnit\Framework\TestCase;

class TagMergeEventTest extends TestCase
{
    public function testConstructGettersSetters(): void
    {
        $primaryTag   = new Tag();
        $secondaryTag = new Tag();
        $event        = new TagMergeEvent($primaryTag, $secondaryTag);

        $this->assertSame($primaryTag, $event->getPrimaryTag());
        $this->assertSame($secondaryTag, $event->getSecondaryTag());
    }
}
