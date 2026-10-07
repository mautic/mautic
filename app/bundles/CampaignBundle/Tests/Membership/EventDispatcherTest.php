<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Membership;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignBatchLeadChangeEvent;
use Mautic\CampaignBundle\Event\CampaignSingleLeadChangeEvent;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\EventDispatcher;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testLeadChangeEventDispatched(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CampaignSingleLeadChangeEvent::class));

        $this->getDispatcher()->dispatchMembershipChange(new Lead(), new Campaign(), Adder::NAME);
    }

    public function testBatchChangeEventDispatched(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CampaignBatchLeadChangeEvent::class));

        $this->getDispatcher()->dispatchBatchMembershipChange([new Lead()], new Campaign(), Adder::NAME);
    }

    private function getDispatcher(): EventDispatcher
    {
        return new EventDispatcher($this->eventDispatcher);
    }
}
