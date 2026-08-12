<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\LeadEvents;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Group('database')]
final class SegmentSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public function testLeadListChangeEventHasListeners(): void
    {
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);

        $this->assertTrue($dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE));
        $this->assertTrue($dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE));
    }
}
