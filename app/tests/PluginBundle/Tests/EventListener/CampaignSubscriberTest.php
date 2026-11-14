<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\PushContactInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\EventListener\CampaignSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
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

    public function testActionPushesToIntegrationsBundleIntegrationWithTheActionConfig(): void
    {
        $pendingEvent = $this->createPendingEvent(['integration' => 'Foo', 'config' => ['list_settings' => ['list' => 'a1']]]);
        $contact      = $pendingEvent->getPending()->first()->getLead();

        $integration = $this->createPublishedIntegration(true);
        $this->assertInstanceOf(\Mautic\LeadBundle\Entity\Lead::class, $contact);
        $integration->expects($this->once())
            ->method('pushContact')
            ->with($contact, ['list_settings' => ['list' => 'a1']]);

        $subscriber = $this->createSubscriber($integration);
        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    public function testActionFailsWithTheErrorOfAnIntegrationsBundleIntegration(): void
    {
        $pendingEvent = $this->createPendingEvent(['integration' => 'Foo']);

        $integration = $this->createPublishedIntegration(true);
        $integration->expects($this->once())
            ->method('pushContact')
            ->with($this->isInstanceOf(Lead::class), [])
            ->willThrowException(new PushContactException('List not found'));

        $subscriber = $this->createSubscriber($integration);
        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(1, $pendingEvent->getFailures());
        $this->assertSame('List not found', $pendingEvent->getFailures()->first()->getFailedLog()?->getReason());
    }

    public function testActionSkipsUnpublishedIntegrationsBundleIntegration(): void
    {
        $pendingEvent = $this->createPendingEvent(['integration' => 'Foo']);

        $integration = $this->createPublishedIntegration(false);
        $integration->expects($this->never())->method('pushContact');

        $subscriber = $this->createSubscriber($integration);
        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
    }

    public function testActionPassesWhenTheIntegrationHasNoSettings(): void
    {
        $pendingEvent = $this->createPendingEvent(['integration' => 'Foo']);

        $listed = $this->createStub(PushContactInterface::class);
        $listed->method('getName')->willReturn('Foo');

        $integrationHelper = $this->createStub(IntegrationHelper::class);
        $integrationHelper->method('getIntegrationObjects')->willReturn(['Foo' => $listed]);

        $integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $integrationsHelper->expects($this->once())->method('getIntegration')->willThrowException(new IntegrationNotFoundException());

        $subscriber = new CampaignSubscriber();
        $subscriber->autowirePushToIntegrationTrait($integrationHelper);
        $subscriber->autowirePushContactIntegrations($integrationsHelper);
        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
    }

    /**
     * @return PushContactInterface&MockObject
     */
    private function createPublishedIntegration(bool $isPublished): MockObject
    {
        $settings = new Integration();
        $settings->setIsPublished($isPublished);

        $integration = $this->createMock(PushContactInterface::class);
        $integration->method('getIntegrationConfiguration')
            ->willReturn($settings);

        return $integration;
    }

    private function createSubscriber(PushContactInterface $integration): CampaignSubscriber
    {
        // The integration found by the legacy helper only identifies it; the push goes through the hydrated one
        $listed = $this->createStub(PushContactInterface::class);
        $listed->method('getName')->willReturn('Foo');

        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects($this->once())->method('getIntegrationObjects')->with('Foo')->willReturn(['Foo' => $listed]);

        $integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $integrationsHelper->expects($this->once())->method('getIntegration')->with('Foo')->willReturn($integration);

        $subscriber = new CampaignSubscriber();
        $subscriber->autowirePushToIntegrationTrait($integrationHelper);
        $subscriber->autowirePushContactIntegrations($integrationsHelper);

        return $subscriber;
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createPendingEvent(array $properties = ['integration' => 'Foo']): PendingEvent
    {
        $event = new Event();
        $event->setCampaign(new Campaign());
        $event->setType('plugin.leadpush');
        $event->setProperties($properties);

        $log = new LeadEventLog();
        $log->setLead(new Lead());
        $log->setEvent($event);

        return new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$log]));
    }
}
