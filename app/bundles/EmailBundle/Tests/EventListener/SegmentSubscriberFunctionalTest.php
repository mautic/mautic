<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Event\ListBatchChangeEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SegmentSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public function testLeadListChangeEventHasListeners(): void
    {
        $dispatcher = $this->getService(EventDispatcherInterface::class);

        $this->assertTrue($dispatcher->hasListeners(ListChangeEvent::class));
        $this->assertTrue($dispatcher->hasListeners(ListBatchChangeEvent::class));
    }
}
