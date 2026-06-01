<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

final class EventSchedulerExtendTriggerDateFunctionalTest extends MauticMysqlTestCase
{
    private function createPublishAuditLog(Campaign $campaign, \DateTime $dateAdded, bool $isPublished): void
    {
        $auditLog = new AuditLog();
        $auditLog->setBundle('campaign');
        $auditLog->setObject('campaign');
        $auditLog->setObjectId((int) $campaign->getId());
        $auditLog->setAction('update');
        $auditLog->setUserName('admin');
        $auditLog->setUserId(1);
        $auditLog->setIpAddress('127.0.0.1');
        $auditLog->setDateAdded($dateAdded);
        $auditLog->setDetails([
            'isPublished' => [
                '0' => !$isPublished,
                '1' => $isPublished,
            ],
        ]);

        $this->em->persist($auditLog);
    }

    public function testCampaignTriggerCommandWithNegativeSecondsDoesNotCrash(): void
    {
        $contact = new Lead();
        $this->em->persist($contact);

        $campaign = new Campaign();
        $campaign->setName('Test Campaign Negative Interval');
        $campaign->setRepublishBehavior('count_only_while_published');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        // Campaign was republished most recently NOW (this becomes lastPublishDate)
        $this->createPublishAuditLog($campaign, new \DateTime('now'), true);

        $event = new Event();
        $event->setName('Test Event');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setCampaign($campaign);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setTriggerInterval(5); // 5 days interval
        $event->setTriggerIntervalUnit('d');
        $this->em->persist($event);

        // Add contact to campaign
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setDateAdded(new \DateTime('-30 days'));
        $campaignLead->setManuallyAdded(false);
        $this->em->persist($campaignLead);

        // Log was created 30 days ago, was supposed to trigger 25 days ago (30 - 5)
        // lastPublishDate = now, dateTriggered = 30 days ago
        // secondsToAdd = 5 days - 30 days = NEGATIVE!
        // Without fix: tries to build "PT-2160000S" interval -> Exception
        $log = new LeadEventLog();
        $log->setEvent($event);
        $log->setLead($contact);
        $log->setCampaign($campaign);
        $log->setDateTriggered(new \DateTime('-30 days'));
        $log->setRotation(1);
        $log->setIsScheduled(true);
        $log->setTriggerDate(new \DateTime('-25 days')); // Was supposed to trigger 25 days ago (overdue!)
        $this->em->persist($log);

        $this->em->flush();

        $logId = $log->getId();

        $this->em->clear();

        // Without the fix, this throws: Exception "Unknown or bad format (PT-XXXXS)"
        // With the fix, this executes successfully
        $output = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()])->getDisplay();

        $this->assertStringNotContainsString('Exception', $output, 'Command should execute without errors');

        // Verify the event was processed
        $updatedLog = $this->em->getRepository(LeadEventLog::class)->find($logId);

        $this->assertNotNull($updatedLog, 'Event log should exist');
        $this->assertNotNull($updatedLog->getTriggerDate(), 'Trigger date should be set');
    }

    public function testCampaignTriggerCommandWithPositiveSecondsSchedulesCorrectly(): void
    {
        $contact = new Lead();
        $this->em->persist($contact);

        $campaign = new Campaign();
        $campaign->setName('Test Campaign Positive Interval');
        $campaign->setRepublishBehavior('count_only_while_published');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        // Campaign was published 3 days ago
        $this->createPublishAuditLog($campaign, new \DateTime('-3 days'), true);

        $event = new Event();
        $event->setName('Test Event');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setCampaign($campaign);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setTriggerInterval(10); // 10 days interval
        $event->setTriggerIntervalUnit('d');
        $this->em->persist($event);

        // Add contact to campaign
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setDateAdded(new \DateTime('-3 days'));
        $campaignLead->setManuallyAdded(false);
        $this->em->persist($campaignLead);

        // Log was created 3 days ago, should trigger 7 days from now (3 + 10 - 3 = 7)
        $log = new LeadEventLog();
        $log->setEvent($event);
        $log->setLead($contact);
        $log->setCampaign($campaign);
        $log->setDateTriggered(new \DateTime('-3 days'));
        $log->setRotation(1);
        $log->setIsScheduled(true);
        $log->setTriggerDate(new \DateTime('+7 days')); // Should trigger in the future
        $this->em->persist($log);

        $this->em->flush();

        $logId = $log->getId();

        $this->em->clear();

        $output = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()])->getDisplay();

        $this->assertStringNotContainsString('Exception', $output, 'Command should execute without errors');

        // The event should be rescheduled to approximately 7 days from now (10 day interval - 3 days elapsed)
        $updatedLog = $this->em->getRepository(LeadEventLog::class)->find($logId);

        $this->assertNotNull($updatedLog, 'Event log should exist');
        $this->assertNotNull($updatedLog->getTriggerDate(), 'Trigger date should be set');

        $expectedTriggerDate = new \DateTime('+7 days');
        $lowerBound          = (clone $expectedTriggerDate)->modify('-10 seconds');
        $upperBound          = (clone $expectedTriggerDate)->modify('+10 seconds');

        $this->assertGreaterThan($lowerBound, $updatedLog->getTriggerDate(), 'Event should be scheduled around 7 days from now');
        $this->assertLessThan($upperBound, $updatedLog->getTriggerDate(), 'Event should be scheduled around 7 days from now');
    }
}
