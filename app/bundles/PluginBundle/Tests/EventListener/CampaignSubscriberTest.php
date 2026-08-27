<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\EventListener\CampaignSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class CampaignSubscriberTest extends TestCase
{
    public function testActionPassesLogWhenNoIntegrationRejects(): void
    {
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->method('getIntegrationObjects')->willReturn([]);

        $subscriber = new CampaignSubscriber();
        $subscriber->autowirePushToIntegrationTrait($integrationHelper);

        $pendingEvent = $this->createPendingEvent();

        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    private function createPendingEvent(): PendingEvent
    {
        $event = new Event();
        $event->setCampaign(new Campaign());
        $event->setType('plugin.leadpush');
        $event->setProperties(['integration' => 'Foo']);

        $log = new LeadEventLog();
        $log->setLead($this->createStub(Lead::class));
        $log->setEvent($event);

        return new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$log]));
    }
}
