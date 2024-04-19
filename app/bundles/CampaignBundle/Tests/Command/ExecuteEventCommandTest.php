<?php

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CampaignBundle\Tests\Functional\Fixtures\FixtureHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use PHPUnit\Framework\Assert;

class ExecuteEventCommandTest extends AbstractCampaignCommand
{
    public function testEventsAreExecutedForInactiveEventWithSingleContact(): void
    {
        putenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS=1');

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3']);

        // There should be three events scheduled
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(3, $byEvent[2]);

        $logIds = [];
        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Event is not scheduled for lead ID '.$log['lead_id']);
            }

            $logIds[] = $log['id'];
        }

        $this->testSymfonyCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => implode(',', $logIds)]);

        // There should still be three events scheduled
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(3, $byEvent[2]);

        foreach ($byEvent[2] as $log) {
            if (0 === (int) $log['is_scheduled']) {
                $this->fail('Event is not scheduled for lead ID '.$log['lead_id']);
            }
        }

        // Pop off the last so we can test that only the two given are executed
        $lastId = array_pop($logIds);

        // Wait 6 seconds to go past scheduled time
        static::getContainer()->get(ScheduledExecutioner::class)->setNowTime(new \DateTime('+'.self::CONDITION_SECONDS.' seconds'));

        $this->testSymfonyCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => implode(',', $logIds)]);

        // The events should have executed
        $byEvent = $this->getCampaignEventLogs([2]);
        $this->assertCount(3, $byEvent[2]);

        foreach ($byEvent[2] as $log) {
            // Lasta
            if ($log['id'] === $lastId) {
                if (0 === (int) $log['is_scheduled']) {
                    $this->fail('Event is not scheduled when it should be for lead ID '.$log['lead_id']);
                }

                continue;
            }

            if (1 === (int) $log['is_scheduled']) {
                $this->fail('Event is still scheduled for lead ID '.$log['lead_id']);
            }
        }

        putenv('CAMPAIGN_EXECUTIONER_SCHEDULER_ACKNOWLEDGE_SECONDS=0');
    }

    public function testRepublishScheduledCampaignEventActionWhenEventFailedBecauseCampaignWasUnpublished(): void
    {
        $fixtureHelper = new FixtureHelper($this->em);
        $contact       = $fixtureHelper->createContact('some@contact.email');
        $campaign      = $fixtureHelper->createCampaign('Scheduled event test');
        $fixtureHelper->addContactToCampaign($contact, $campaign);
        $fixtureHelper->createCampaignWithScheduledEvent($campaign);

        $this->em->flush();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        Assert::assertStringContainsString('1 total event was scheduled', $commandResult->getDisplay());

        $campaign->setIsPublished(false);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        $leadEventLogRepository = $this->em->getRepository(LeadEventLog::class);
        \assert($leadEventLogRepository instanceof LeadEventLogRepository);

        $log = $leadEventLogRepository->findOneBy(['lead' => $contact, 'campaign' => $campaign]);
        \assert($log instanceof LeadEventLog);

        Assert::assertTrue($log->getIsScheduled());

        // Time machine so we don't have to wait for that long.
        $log->setTriggerDate(new \DateTime('2 days ago'));
        $log->setDateTriggered(new \DateTime('2 days ago'));
        $log->setIsScheduled(true);
        $this->em->persist($log);
        $this->em->flush();
        $this->em->clear();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => $log->getId()]);

        Assert::assertStringContainsString('0 total events(s) to be processed', $commandResult->getDisplay());
        Assert::assertStringContainsString('0 total events were executed', $commandResult->getDisplay());
        Assert::assertStringContainsString('0 total events were scheduled', $commandResult->getDisplay());

        $log = $leadEventLogRepository->findOneBy(['lead' => $contact, 'campaign' => $campaign]);
        \assert($log instanceof LeadEventLog);

        Assert::assertTrue($log->getIsScheduled());
        Assert::assertSame([], $log->getMetadata());
    }

    public function testRepublishScheduledCampaignEventActionWhenEventFailedBecauseCampaignPublishDownIsInThePast(): void
    {
        $fixtureHelper = new FixtureHelper($this->em);
        $contact       = $fixtureHelper->createContact('some@contact.email');
        $campaign      = $fixtureHelper->createCampaign('Scheduled event test');
        $fixtureHelper->addContactToCampaign($contact, $campaign);
        $fixtureHelper->createCampaignWithScheduledEvent($campaign);

        $this->em->flush();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        Assert::assertStringContainsString('1 total event was scheduled', $commandResult->getDisplay());

        $campaign->setPublishUp(new \DateTime('3 days ago'));
        $campaign->setPublishDown(new \DateTime('1 days ago'));
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->clear();

        $leadEventLogRepository = $this->em->getRepository(LeadEventLog::class);
        \assert($leadEventLogRepository instanceof LeadEventLogRepository);

        $log = $leadEventLogRepository->findOneBy(['lead' => $contact, 'campaign' => $campaign]);
        \assert($log instanceof LeadEventLog);

        Assert::assertTrue($log->getIsScheduled());

        // Time machine so we don't have to wait for that long.
        $log->setTriggerDate(new \DateTime('2 days ago'));
        $log->setDateTriggered(new \DateTime('2 days ago'));
        $log->setIsScheduled(true);
        $this->em->persist($log);
        $this->em->flush();
        $this->em->clear();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => $log->getId()]);

        Assert::assertStringContainsString('1 total events(s) to be processed', $commandResult->getDisplay());
        Assert::assertStringContainsString('0 total events were executed', $commandResult->getDisplay());
        Assert::assertStringContainsString('0 total events were scheduled', $commandResult->getDisplay());
    }

    public function testScheduledCampaignEventActionIfScheduledAtDefined(): void
    {
        $interval      = 5;
        $unit          = 'i';
        $fixtureHelper = new FixtureHelper($this->em);
        $contact       = $fixtureHelper->createContact('some@contact.email');
        $campaign      = $fixtureHelper->createCampaign('Scheduled event test');
        $fixtureHelper->addContactToCampaign($contact, $campaign);
        $hour = new \DateTime();
        $hour->add((new DateTimeHelper())->buildInterval($interval, $unit));
        $fixtureHelper->createCampaignWithScheduledEvent($campaign, $interval, $unit, $hour);

        $this->em->flush();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        Assert::assertStringContainsString('1 total event was scheduled', $commandResult->getDisplay());

        $leadEventLogRepository = $this->em->getRepository(LeadEventLog::class);
        \assert($leadEventLogRepository instanceof LeadEventLogRepository);

        $log = $leadEventLogRepository->findOneBy(['lead' => $contact, 'campaign' => $campaign]);
        \assert($log instanceof LeadEventLog);

        Assert::assertTrue($log->getIsScheduled());

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:execute', ['--scheduled-log-ids' => $log->getId()]);

        Assert::assertStringContainsString('1 total events(s) to be processed', $commandResult->getDisplay());
        Assert::assertStringContainsString('1 total event was scheduled', $commandResult->getDisplay());
        Assert::assertStringContainsString('0 total events were executed', $commandResult->getDisplay());
    }
}
