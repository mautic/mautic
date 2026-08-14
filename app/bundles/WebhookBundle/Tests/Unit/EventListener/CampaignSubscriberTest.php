<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\WebhookBundle\EventListener\CampaignSubscriber;
use Mautic\WebhookBundle\Helper\CampaignHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CampaignSubscriberTest extends TestCase
{
    /**
     * @var MockObject&Client
     */
    private MockObject $client;

    private CampaignSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client      = $this->createMock(Client::class);
        $companyRepository = $this->createStub(CompanyRepository::class);
        $companyRepository->method('getCompaniesByLeadId')->willReturn([new Company()]);

        $campaignHelper   = new CampaignHelper($this->client, $this->createStub(EventDispatcherInterface::class), $companyRepository);
        $this->subscriber = new CampaignSubscriber($campaignHelper);
    }

    public function testOnCampaignTriggerActionPassesWhenWebhookSucceeds(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->willReturn(new Response(200));

        $pendingEvent = $this->createPendingEvent();

        $this->subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    public function testOnCampaignTriggerActionFailsWhenWebhookThrows(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('connection refused'));

        $pendingEvent = $this->createPendingEvent();

        $this->subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(1, $pendingEvent->getFailures());
    }

    public function testOnCampaignTriggerActionSkipsForeignContext(): void
    {
        $this->client->expects($this->never())->method('get');

        $pendingEvent = $this->createPendingEvent('some.other.action');

        $this->subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    private function createPendingEvent(string $type = 'campaign.sendwebhook'): PendingEvent
    {
        $contact = $this->createStub(Lead::class);
        $contact->method('getProfileFields')->willReturn(['email' => 'john@doe.email']);
        $contact->method('getIpAddresses')->willReturn(new ArrayCollection());

        $log = new LeadEventLog();
        $log->setLead($contact);

        $event = new Event();
        $event->setCampaign(new Campaign());
        $event->setType($type);
        $event->setProperties([
            'url'     => 'https://mautic.org',
            'method'  => 'get',
            'timeout' => 10,
        ]);
        $log->setEvent($event);

        return new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$log]));
    }
}
