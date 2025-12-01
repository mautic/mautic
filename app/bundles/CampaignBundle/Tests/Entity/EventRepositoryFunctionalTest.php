<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class EventRepositoryFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<string, array{?\DateTime, ?\DateTime, int}>
     */
    public static function dataGetContactPendingEventsConsidersCampaignPublishUpAndDown(): iterable
    {
        yield 'Publish Up and Down not set' => [null, null, 1];
        yield 'Publish Up and Down set' => [new \DateTime('-1 day'), new \DateTime('+1 day'), 1];
        yield 'Publish Up and Down set with Publish Up in the future' => [new \DateTime('+1 day'), new \DateTime('+2 day'), 0];
        yield 'Publish Up and Down set with Publish Down in the past' => [new \DateTime('-2 day'), new \DateTime('-1 day'), 0];
        yield 'Publish Up in the past' => [new \DateTime('-1 day'), null, 1];
        yield 'Publish Up in the future' => [new \DateTime('+1 day'), null, 0];
        yield 'Publish Down in the past' => [null, new \DateTime('-1 day'), 0];
        yield 'Publish Down in the future' => [null, new \DateTime('+1 day'), 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataGetContactPendingEventsConsidersCampaignPublishUpAndDown')]
    public function testGetContactPendingEventsConsidersCampaignPublishUpAndDown(?\DateTime $publishUp, ?\DateTime $publishDown, int $expectedCount): void
    {
        $repository = static::getContainer()->get('mautic.campaign.repository.event');
        \assert($repository instanceof EventRepository);

        $campaign = $this->createCampaign();
        $event    = $this->createEvent($campaign);
        $lead     = $this->createLead();
        $this->createCampaignMember($lead, $campaign);

        $campaign->setPublishUp($publishUp);
        $campaign->setPublishDown($publishDown);
        $this->em->persist($campaign);
        $this->em->flush();

        Assert::assertCount($expectedCount, $repository->getContactPendingEvents($lead->getId(), $event->getType()));
    }

    public function testSetEventsAsDeletedWithRedirectUpdatesChains(): void
    {
        $repository = static::getContainer()->get('mautic.campaign.repository.event');
        \assert($repository instanceof EventRepository);

        $campaign = $this->createCampaign();

        $eventA = $this->createEvent($campaign);
        $eventA->setName('Event A');

        $eventB = $this->createEvent($campaign);
        $eventB->setName('Event B');

        $eventC = $this->createEvent($campaign);
        $eventC->setName('Event C');

        $eventD = $this->createEvent($campaign);
        $eventD->setName('Event D');

        $eventA->setDeleted();
        $eventA->setRedirectEvent($eventC);

        $eventB->setDeleted();
        $eventB->setRedirectEvent($eventC);

        $this->em->persist($eventA);
        $this->em->persist($eventB);
        $this->em->persist($eventC);
        $this->em->persist($eventD);
        $this->em->flush();

        $eventCId = $eventC->getId();
        $eventDId = $eventD->getId();

        $repository->setEventsAsDeletedWithRedirect([
            [
                'id'            => $eventCId,
                'redirectEvent' => $eventDId,
            ],
        ]);

        $this->em->clear();

        $reloadedEventA = $this->em->find(Event::class, $eventA->getId());
        $reloadedEventB = $this->em->find(Event::class, $eventB->getId());
        $reloadedEventC = $this->em->find(Event::class, $eventCId);

        Assert::assertNotNull($reloadedEventC->getDeleted());
        Assert::assertSame($eventDId, $reloadedEventA->getRedirectEvent()?->getId());
        Assert::assertSame($eventDId, $reloadedEventB->getRedirectEvent()?->getId());
        Assert::assertSame($eventDId, $reloadedEventC->getRedirectEvent()?->getId());
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test');
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName('test');
        $event->setCampaign($campaign);
        $event->setType('test.type');
        $event->setEventType('action');
        $this->em->persist($event);

        return $event;
    }

    private function createCampaignMember(Lead $lead, Campaign $campaign): void
    {
        $member = new CampaignMember();
        $member->setLead($lead);
        $member->setCampaign($campaign);
        $member->setDateAdded(new \DateTime());
        $this->em->persist($member);
    }
}
