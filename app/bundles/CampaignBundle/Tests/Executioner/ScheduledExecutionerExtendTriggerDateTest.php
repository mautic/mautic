<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Enum\RepublishBehavior;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CampaignBundle\Tests\CampaignAuditLogTrait;
use Mautic\CampaignBundle\Tests\Command\AbstractCampaignCommand;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class ScheduledExecutionerExtendTriggerDateTest extends AbstractCampaignCommand
{
    use CampaignAuditLogTrait;

    /**
     * @param array<array{dateAdded: string, details: array<string, array<int, mixed>>}> $auditLogs
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('republishBehaviorProvider')]
    public function testExecuteEventCommandWithRepublishBehavior(
        string $republishBehavior,
        string $triggerMode,
        int $intervalDays,
        array $auditLogs,
        string $expectedTriggerDate,
        bool $expectedIsScheduled,
    ): void {
        // Create an unpublished campaign with specified republish behavior
        $campaign = new Campaign();
        $campaign->setName("Campaign with {$republishBehavior} behavior");
        $campaign->setIsPublished(true);
        $campaign->setRepublishBehavior($republishBehavior);

        $this->em->persist($campaign);

        // Create an interval event
        $event = new Event();
        $event->setName('Test Interval Event');
        $event->setType('email.send');
        $event->setCampaign($campaign);
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode($triggerMode);
        $event->setTriggerInterval($intervalDays);
        $event->setTriggerIntervalUnit('d');

        $this->em->persist($event);

        // Create a contact
        $contact = new Lead();
        $contact->setEmail("test-{$republishBehavior}@example.com");

        $this->em->persist($contact);

        $dateAdded   = new \DateTime('2024-10-02 00:00:00');
        $triggerDate = clone $dateAdded;
        $triggerDate->add(new \DateInterval("P{$intervalDays}D"));

        // Create an event log with past trigger date
        $eventLog = new LeadEventLog();
        $eventLog->setEvent($event);
        $eventLog->setCampaign($campaign);
        $eventLog->setLead($contact);
        $eventLog->setTriggerDate($triggerDate);
        $eventLog->setDateTriggered($dateAdded);
        $eventLog->setIsScheduled(true);

        $this->em->persist($eventLog);
        $this->em->flush();

        $this->saveAuditLogs($this->em, $auditLogs, $campaign);

        $this->em->clear();

        // Execute using the command
        $logId         = $eventLog->getId();
        $commandTester = $this->testSymfonyCommand('mautic:campaigns:execute', [
            '--scheduled-log-ids' => (string) $logId,
            '--execution-time'    => (new \DateTime('2025-01-10 00:02:00'))->format(Interval::LOG_DATE_FORMAT),
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $eventLog = $this->em->find(LeadEventLog::class, $logId);
        \assert($eventLog instanceof LeadEventLog);

        Assert::assertSame($expectedTriggerDate, $eventLog->getTriggerDate()?->format(DateTimeHelper::FORMAT_DB));
        Assert::assertSame($expectedIsScheduled, $eventLog->getIsScheduled());
    }

    /**
     * @return array<string, array{republishBehavior: string, triggerMode: string, intervalDays: int, auditLogs: array<array{dateAdded: string, details: array<string, array<int, mixed>>}>, expectedTriggerDate: string, expectedIsScheduled: bool}>
     */
    public static function republishBehaviorProvider(): array
    {
        $unpublishedAfter5days = 'published, unpublished and published';
        $auditLogs             = [
            $unpublishedAfter5days => [
                [
                    'dateAdded' => '2024-10-01 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
                [
                    'dateAdded' => '2024-10-05 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2024-10-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
            ],
        ];

        $startDate      = new \DateTime('2024-10-02 00:00:00');
        $endDate        = new \DateTime('2034-10-02 00:00:00');
        $tenYearsInDays = $endDate->diff($startDate)->days;

        return [
            'Same the original trigger date as it should not change' => [
                'republishBehavior'   => RepublishBehavior::COUNT_ALL_TIME->value,
                'triggerMode'         => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'        => 10,
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled' => false, // Executed anyway as the trigger date is still in the past
            ],
            '10 days after last publish which is 2024-10-10 00:00:00' => [
                'republishBehavior'   => RepublishBehavior::RESTART_ON_PUBLISH->value,
                'triggerMode'         => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'        => 10,
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2024-10-20 00:00:00', // 10 days after last publish
                'expectedIsScheduled' => false, // Executed anyway as the trigger date is still in the past
            ],
            'Scheduled at 2024-10-02 00:00:00 for 10 days (2024-10-12 00:00:00), unpublished at 2024-10-05 00:00:00 after 3 days, published 2024-10-10 00:00:00 (5 days). From this it would be another 10 days but 3 were already served.' => [
                'republishBehavior'   => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'         => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'        => 10,
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2024-10-17 00:00:00', // 3 days were already published, 7 remaining to go.
                'expectedIsScheduled' => false, // Executed anyway as the trigger date is still in the past
            ],
            'Scheduled 10 years into the future so it should reschedule, not execute. Republished 2024-10-10 00:00:00 with 3 days already published before.' => [
                'republishBehavior'   => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'         => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'        => $tenYearsInDays, // 3652 days (Warning, this test will fail in 10 years. Sending regards to future maintainers!)
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2034-10-07 00:00:00', // 10 years after 2024-10-10 minus 3 days before the last publish date.
                'expectedIsScheduled' => true, // Not executed but rescheduled for 10 years in the future
            ],
            'Absolute date trigger mode should not do anything' => [
                'republishBehavior'   => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'         => Event::TRIGGER_MODE_DATE,
                'intervalDays'        => 10,
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled' => false, // Executed anyway as the trigger date is still in the past
            ],
            'Immediate trigger mode should not extend anything' => [
                'republishBehavior'   => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'         => Event::TRIGGER_MODE_IMMEDIATE,
                'intervalDays'        => 10,
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2024-10-12 00:00:00', // Original trigger date
                'expectedIsScheduled' => false, // Executed anyway as the trigger date is still in the past
            ],
            'If the interval is empty then there will be no extension' => [
                'republishBehavior'   => RepublishBehavior::COUNT_ONLY_WHILE_PUBLISHED->value,
                'triggerMode'         => Event::TRIGGER_MODE_INTERVAL,
                'intervalDays'        => 0,
                'auditLogs'           => $auditLogs[$unpublishedAfter5days],
                'expectedTriggerDate' => '2024-10-02 00:00:00', // Since there is no interval, the trigger date remains the same as the date triggered (created)
                'expectedIsScheduled' => false, // Executed anyway as the trigger date is still in the past
            ],
        ];
    }
}
