<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Console\Output\BufferedOutput;

final class ScheduledExecutionerFunctionalTest extends MauticMysqlTestCase
{
    private ScheduledExecutioner $scheduledExecutioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduledExecutioner = self::getContainer()->get('mautic.campaign.executioner.scheduled');
        \assert($this->scheduledExecutioner instanceof ScheduledExecutioner);
    }

    public function testEventsAreExecuted(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent($campaign, 'Event 1');
        $event2   = $this->createEvent($campaign, 'Event 2');
        $contact  = $this->createContact();

        $log1 = $this->createScheduledLog($event1, $contact, null, 1);
        $log2 = $this->createScheduledLog($event1, $contact, null, 2);
        $log3 = $this->createScheduledLog($event2, $contact, null, 3);
        $log4 = $this->createScheduledLog($event2, $contact, null, 4);

        $this->em->persist($campaign);
        $this->em->persist($event1);
        $this->em->persist($event2);
        $this->em->persist($contact);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->persist($log3);
        $this->em->persist($log4);
        $this->em->flush();

        $limiter = new ContactLimiter(100, 0, 0, 0);
        $counter = $this->scheduledExecutioner->execute($campaign, $limiter, new BufferedOutput());

        $this->assertEquals(4, $counter->getTotalEvaluated());
    }

    public function testEventsAreExecutedInQuietMode(): void
    {
        $campaign = $this->createCampaign();
        $event1   = $this->createEvent($campaign, 'Event 1');
        $event2   = $this->createEvent($campaign, 'Event 2');
        $contact  = $this->createContact();

        $log1 = $this->createScheduledLog($event1, $contact, null, 5);
        $log2 = $this->createScheduledLog($event1, $contact, null, 6);
        $log3 = $this->createScheduledLog($event2, $contact, null, 7);
        $log4 = $this->createScheduledLog($event2, $contact, null, 8);

        $this->em->persist($campaign);
        $this->em->persist($event1);
        $this->em->persist($event2);
        $this->em->persist($contact);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->persist($log3);
        $this->em->persist($log4);
        $this->em->flush();

        $limiter = new ContactLimiter(100, 0, 0, 0);
        $counter = $this->scheduledExecutioner->execute($campaign, $limiter); // Quiet mode - no output

        $this->assertEquals(4, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsAreExecuted(): void
    {
        $campaign = $this->createCampaign();
        $event    = $this->createEvent($campaign);
        $contact  = $this->createContact();

        $log1 = $this->createScheduledLog($event, $contact, null, 9);
        $log2 = $this->createScheduledLog($event, $contact, null, 10);

        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->persist($contact);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->flush();

        $counter = $this->scheduledExecutioner->executeByIds([$log1->getId(), $log2->getId()]);

        $this->assertEquals(2, $counter->getTotalEvaluated());
    }

    public function testEventsAreScheduled(): void
    {
        $campaign = $this->createCampaign();
        $event    = $this->createScheduledEvent($campaign);
        $contact  = $this->createContact();

        $currentDate = new \DateTime();
        $log1        = $this->createScheduledLog($event, $contact, $currentDate, 11);
        $log2        = $this->createScheduledLog($event, $contact, $currentDate, 12);

        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->persist($contact);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->flush();

        $limiter = new ContactLimiter(100, 0, 0, 0);
        $counter = $this->scheduledExecutioner->execute($campaign, $limiter);

        // Both events should be evaluated since they are due for execution
        $this->assertEquals(2, $counter->getTotalEvaluated());
        // No events should be scheduled since they were due and processed
        $this->assertEquals(0, $counter->getTotalScheduled());
    }

    public function testDeletedEventsAreRedirectedToTargetEvent(): void
    {
        $campaign = $this->createCampaign();

        // Create the redirect target event (the event that deleted events should redirect to)
        $redirectTargetEvent = $this->createEvent($campaign, 'Redirect Target Event');

        // Create the original event that will be deleted
        $deletedEvent = $this->createEvent($campaign, 'Deleted Event');

        // Create a unique contact for this test to avoid rotation conflicts
        $contact = $this->createContact();
        $contact->setEmail('redirection-test@example.com');

        $this->em->persist($campaign);
        $this->em->persist($redirectTargetEvent);
        $this->em->persist($deletedEvent);
        $this->em->persist($contact);
        $this->em->flush();

        // Now set up the redirection relationship
        $deletedEvent->setDeleted();
        $deletedEvent->setRedirectEvent($redirectTargetEvent);

        // Create an existing log for the redirect target event to test rotation calculation
        $existingLogForTarget = $this->createScheduledLog($redirectTargetEvent, $contact, null, 1);

        // Create scheduled logs for the deleted event
        $log1 = $this->createScheduledLog($deletedEvent, $contact, null, 1);
        $log2 = $this->createScheduledLog($deletedEvent, $contact, null, 2);

        $campaignLead = $this->createCampaignLead($contact, $campaign, 2);

        $this->em->persist($deletedEvent);
        $this->em->persist($existingLogForTarget);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->persist($campaignLead);
        $this->em->flush();

        // Verify the event is properly set up for redirection
        $this->assertTrue($deletedEvent->isDeleted(), 'Event should be marked as deleted');
        $this->assertNotNull($deletedEvent->getRedirectEvent(), 'Event should have a redirect event');
        $this->assertTrue($deletedEvent->shouldBeRedirected(), 'Event should be redirected');
        $this->assertEquals(
            $redirectTargetEvent->getId(),
            $deletedEvent->getRedirectEvent()->getId(),
            'Redirect event should match target event'
        );

        // Process logs one by one to avoid race condition in rotation calculation
        $counter1 = $this->scheduledExecutioner->executeByIds([$log1->getId()]);
        $counter2 = $this->scheduledExecutioner->executeByIds([$log2->getId()]);

        $totalEvaluated = $counter1->getTotalEvaluated() + $counter2->getTotalEvaluated();

        $this->assertEquals(2, $totalEvaluated, 'Both events should be evaluated');

        // After execution, reload the logs from database to see the updated state
        $log1 = $this->em->find(LeadEventLog::class, $log1->getId());
        $log2 = $this->em->find(LeadEventLog::class, $log2->getId());

        // Verify the logs now point to the redirect target event
        $this->assertEquals(
            $redirectTargetEvent->getId(),
            $log1->getEvent()->getId(),
            'Log1 should now point to redirect target event'
        );
        $this->assertEquals(
            $redirectTargetEvent->getId(),
            $log2->getEvent()->getId(),
            'Log2 should now point to redirect target event'
        );

        // Verify rotation values are correctly calculated
        // (should be 3 and 4 since we had an existing log with rotation 2)
        $this->assertContains($log1->getRotation(), [3, 4], 'Log1 should have rotation 3 or 4');
        $this->assertContains($log2->getRotation(), [3, 4], 'Log2 should have rotation 3 or 4');
        $this->assertNotEquals(
            $log1->getRotation(),
            $log2->getRotation(),
            'Log1 and Log2 should have different rotations'
        );

        // Verify metadata contains information about the original event
        $log1Metadata = $log1->getMetadata();
        $log2Metadata = $log2->getMetadata();

        $this->assertArrayHasKey('last_redirected_from', $log1Metadata, 'Log1 should have redirection metadata');
        $this->assertArrayHasKey('last_redirected_from', $log2Metadata, 'Log2 should have redirection metadata');
        $this->assertEquals(
            $deletedEvent->getId(),
            $log1Metadata['last_redirected_from'],
            'Log1 metadata should contain original event ID'
        );
        $this->assertEquals(
            $deletedEvent->getId(),
            $log2Metadata['last_redirected_from'],
            'Log2 metadata should contain original event ID'
        );

        $this->assertArrayHasKey('originalEventName', $log1Metadata, 'Log1 should have original event name');
        $this->assertArrayHasKey('originalEventName', $log2Metadata, 'Log2 should have original event name');
        $this->assertEquals(
            'Deleted Event',
            $log1Metadata['originalEventName'],
            'Log1 metadata should contain original event name'
        );
        $this->assertEquals(
            'Deleted Event',
            $log2Metadata['originalEventName'],
            'Log2 metadata should contain original event name'
        );
    }

    public function testSpecificEventsAreScheduledWithRedirection(): void
    {
        $campaign = $this->createCampaign();
        $event    = $this->createScheduledEvent($campaign);
        $contact  = $this->createContact();

        $futureDate = new \DateTime('+1 hour');
        $log1       = $this->createScheduledLog($event, $contact, $futureDate, 17);
        $log2       = $this->createScheduledLog($event, $contact, $futureDate, 18);

        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->persist($contact);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->flush();

        $counter = $this->scheduledExecutioner->executeByIds([$log1->getId(), $log2->getId()]);

        $this->assertEquals(2, $counter->getTotalEvaluated());
    }

    public function testSpecificEventsWithUnpublishedCampaign(): void
    {
        $campaign = $this->createCampaign(false);
        $event    = $this->createEvent($campaign);
        $contact  = $this->createContact();

        $log1 = $this->createScheduledLog($event, $contact, null, 19);
        $log2 = $this->createScheduledLog($event, $contact, null, 20);

        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->persist($contact);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->flush();

        $counter = $this->scheduledExecutioner->executeByIds([$log1->getId(), $log2->getId()]);

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testExecuteByIdsWithNonDeletedEvent(): void
    {
        $campaign = $this->createCampaign();
        $event    = $this->createEvent($campaign, 'Normal Event');
        $contact  = $this->createContact();

        $log = $this->createScheduledLog($event, $contact, null, 21);

        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->persist($contact);
        $this->em->persist($log);
        $this->em->flush();

        $counter = $this->scheduledExecutioner->executeByIds([$log->getId()]);

        // Event should be evaluated since it's not deleted and campaign is published
        $this->assertEquals(1, $counter->getTotalEvaluated());

        // Verify the log still points to the original event (no redirection occurred)
        $updatedLog = $this->em->find(LeadEventLog::class, $log->getId());
        $this->assertEquals($event->getId(), $updatedLog->getEvent()->getId());
        $this->assertEquals('Normal Event', $updatedLog->getEvent()->getName());
    }

    public function testRedirectionIncrementsContactRotation(): void
    {
        $campaign      = $this->createCampaign();
        $originalEvent = $this->createEvent($campaign, 'Original Event');
        $redirectEvent = $this->createEvent($campaign, 'Redirect Event');
        $contact       = $this->createContact();

        $originalEvent->setDeleted(new \DateTime());
        $originalEvent->setRedirectEvent($redirectEvent);

        // Add contact to campaign with initial rotation 0
        $campaignMember = new CampaignLead();
        $campaignMember->setCampaign($campaign);
        $campaignMember->setLead($contact);
        $campaignMember->setDateAdded(new \DateTime());
        $campaignMember->setRotation(0);

        // Create a scheduled log for the original (deleted) event
        $log = $this->createScheduledLog($originalEvent, $contact, null, 1);

        $this->em->persist($campaign);
        $this->em->persist($originalEvent);
        $this->em->persist($redirectEvent);
        $this->em->persist($contact);
        $this->em->persist($campaignMember);
        $this->em->persist($log);
        $this->em->flush();

        $initialRotation = $campaignMember->getRotation();

        $limiter = new ContactLimiter(100, 0, 0, 0);
        $this->scheduledExecutioner->execute($campaign, $limiter, new BufferedOutput());

        $this->em->refresh($campaignMember);

        $this->assertEquals($initialRotation + 1, $campaignMember->getRotation(),
            'Campaign member rotation should be incremented during redirection.');

        $updatedLog = $this->em->getRepository(LeadEventLog::class)->findOneBy([
            'lead'     => $contact,
            'campaign' => $campaign,
        ]);

        $this->assertNotNull($updatedLog, 'Log should exist after redirection');
        $this->assertEquals($redirectEvent->getId(), $updatedLog->getEvent()->getId(),
            'Log should be updated to reference the redirect event');
    }

    private function createCampaign(bool $published = true): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $campaign->setIsPublished($published);

        return $campaign;
    }

    private function createEvent(Campaign $campaign, string $name = 'Test Event'): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName($name);
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setProperties(['points' => 10]);

        return $event;
    }

    private function createScheduledEvent(Campaign $campaign): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Test Scheduled Event');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setTriggerInterval(1);
        $event->setTriggerIntervalUnit('h');

        return $event;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('test@example.com');
        $contact->setFirstname('Test');
        $contact->setLastname('Contact');

        return $contact;
    }

    private function createScheduledLog(Event $event, Lead $contact, ?\DateTime $triggerDate = null,
        int $rotation = 1): LeadEventLog
    {
        $log = new LeadEventLog();
        $log->setEvent($event);
        $log->setCampaign($event->getCampaign());
        $log->setLead($contact);
        $log->setTriggerDate($triggerDate ?? new \DateTime());
        $log->setIsScheduled(true);
        $log->setRotation($rotation);

        return $log;
    }

    private function createCampaignLead(Lead $contact, Campaign $campaign, int $rotation = 1): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setRotation($rotation);
        $campaignLead->setDateAdded(new \DateTime());

        return $campaignLead;
    }
}
