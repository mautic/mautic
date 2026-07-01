<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Membership;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
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
            ->with($this->isInstanceOf(CampaignLeadChangeEvent::class), CampaignEvents::CAMPAIGN_ON_LEADCHANGE);

        $this->getDispatcher()->dispatchMembershipChange(new Lead(), new Campaign(), Adder::NAME);
    }

    public function testBatchChangeEventDispatched(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CampaignLeadChangeEvent::class), CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE);

        $this->getDispatcher()->dispatchBatchMembershipChange([new Lead()], new Campaign(), Adder::NAME);
    }

    private function getDispatcher(): EventDispatcher
    {
        return new EventDispatcher($this->eventDispatcher);
    }
}
