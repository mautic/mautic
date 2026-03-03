<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
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

    public function testGetCampaignEmailEvents(): void
    {
        // 1. Create a campaign
        $campaign = new Campaign();
        $campaign->setName('Test Campaign for Emails');
        $this->em->persist($campaign);
        $this->em->flush();

        // 2. Create some emails
        $email1 = new Email();
        $email1->setName('Test Email 1');
        $this->em->persist($email1);

        $email2 = new Email();
        $email2->setName('Test Email 2');
        $this->em->persist($email2);

        $this->em->flush();

        // 3. Create campaign events linked to these emails
        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Send Email 1');
        $event1->setChannel('email');
        $event1->setChannelId($email1->getId());
        $event1->setType('email.send');
        $event1->setEventType(Event::TYPE_ACTION);
        $this->em->persist($event1);

        $event2 = new Event();
        $event2->setCampaign($campaign);
        $event2->setName('Send Email 2');
        $event2->setChannel('email');
        $event2->setChannelId($email2->getId());
        $event2->setType('email.send');
        $event2->setEventType(Event::TYPE_ACTION);
        $this->em->persist($event2);

        // A non-email event to make sure it's filtered out
        $event3 = new Event();
        $event3->setCampaign($campaign);
        $event3->setName('Update Lead');
        $event3->setChannel('lead');
        $event3->setType('lead.update');
        $event3->setEventType(Event::TYPE_ACTION);
        $this->em->persist($event3);

        $this->em->flush();

        // 4. Call the method under test
        $repository   = self::getContainer()->get('mautic.campaign.repository.event');
        \assert($repository instanceof EventRepository);
        $resultEmails = $repository->getCampaignEmailEvents($campaign->getId());

        // 5. Assert the results
        $this->assertCount(2, $resultEmails);
        $this->assertInstanceOf(Email::class, $resultEmails[0]);
        $this->assertInstanceOf(Email::class, $resultEmails[1]);

        $resultEmailIds = [];
        foreach ($resultEmails as $email) {
            $resultEmailIds[] = $email->getId();
        }

        $this->assertContains($email1->getId(), $resultEmailIds);
        $this->assertContains($email2->getId(), $resultEmailIds);
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
