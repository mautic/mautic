<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Command\ResumeStuckCampaignCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;

final class ResumeStuckCampaignCommandTest extends AbstractCampaignCommand
{
    protected function setUp(): void
    {
        $this->configParams['campaigns_resume_stuck_records_after'] = '2025-08-01 00:00:00';
        parent::setUp();

        $this->createStuckContactsTestData();
    }

    public function testCommandExecutionWithDryRun(): void
    {
        $output = $this->executeCommand(
            [
                'campaign-id'   => 1,
                '--dry-run'     => true,
            ]
        );

        $this->assertStringContainsString('Dry run only. No events were executed.', $output);

        $this->assertStringContainsString('Contact ID', $output);
        $this->assertStringContainsString('Next Event ID', $output);
        $this->assertStringContainsString('Next Event Name', $output);
    }

    public function testCommandExecutionWithoutCampaignId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "campaign-id")');
        $this->executeCommand([]);
    }

    public function testCommandExecutionWithInvalidCampaignId(): void
    {
        $output = $this->executeCommand(
            [
                'campaign-id' => 999,
            ]
        );

        $this->assertStringContainsString('Campaign with ID 999 not found', $output);
    }

    public function testCommandExecutionWithUnpublishedCampaign(): void
    {
        $campaign = $this->createCampaign('Unpublished Campaign');
        $campaign->setIsPublished(false);
        $this->em->persist($campaign);
        $this->em->flush();

        $output = $this->executeCommand(
            [
                'campaign-id' => $campaign->getId(),
            ]
        );

        $this->assertStringContainsString('Campaign with ID '.$campaign->getId().' is not published', $output);
    }

    public function testComplexCampaignExecution(): void
    {
        $campaign = $this->createCampaign('Complex Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Complex Contact 1');
        $contact2 = $this->createLead('Complex Contact 2');
        $contact3 = $this->createLead('Complex Contact 3');
        $contact4 = $this->createLead('Complex Contact 4');
        $contact5 = $this->createLead('Complex Contact 5');
        $contact6 = $this->createLead('Complex Contact 6');
        $contact7 = $this->createLead('Complex Contact 7');
        $contact8 = $this->createLead('Complex Contact 8');

        $this->createCampaignLead($campaign, $contact7);
        $this->createCampaignLead($campaign, $contact8);

        sleep(1); // wait 1 second so that compare timestamp

        $rootEmail = $this->createEvent('Welcome Email', $campaign, 'email.send', 'action', ['email' => '1']);

        $conditionEvent = $this->createEvent('Check Contact Field Value Condition', $campaign, 'lead.field_value', 'condition', [
            'field'    => 'points',
            'operator' => 'gt',
            'value'    => '4',
        ]);
        $conditionEvent->setParent($rootEmail);

        // Third level events - YES path from decision
        $yesPathAction = $this->createEvent('Yes Path - Add Tag', $campaign, 'lead.changetags', 'action', [
            'add_tags' => [
                'greater than 4',
            ],
        ]);
        $yesPathAction->setParent($conditionEvent);
        $yesPathAction->setDecisionPath('yes');

        // Third level events - NO path from decision
        $noPathAction = $this->createEvent('No Path - Add Tag', $campaign, 'lead.changetags', 'action', [
            'remove_tags' => [
                'less than 4',
            ],
        ]);
        $noPathAction->setParent($conditionEvent);
        $noPathAction->setDecisionPath('no');

        // Fourth level events from YES path
        $yesFollowupEmail = $this->createEvent('Yes Path Followup', $campaign, 'email.send', 'action', ['email' => '1']);
        $yesFollowupEmail->setParent($yesPathAction);

        // Fourth level events from NO path
        $noFollowupEmail = $this->createEvent('No Path Followup', $campaign, 'email.send', 'action', ['email' => '1']);
        $noFollowupEmail->setParent($noPathAction);

        $this->em->persist($conditionEvent);
        $this->em->persist($yesPathAction);
        $this->em->persist($noPathAction);
        $this->em->persist($yesFollowupEmail);
        $this->em->persist($noFollowupEmail);

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);
        $this->createCampaignLead($campaign, $contact3);
        $this->createCampaignLead($campaign, $contact4);
        $this->createCampaignLead($campaign, $contact5);
        $this->createCampaignLead($campaign, $contact6);

        // Create event logs to simulate events that have already been executed
        // Contact 1 - executed root email and wait event, ready for decision
        $this->createEventLog($contact1, $rootEmail, $campaign, 1);

        // Contact 2 - executed decision event with yes path, stuck at yes path action
        $this->createEventLog($contact2, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact2, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(false); // Yes path

        // Contact 3 - executed decision event with no path, stuck at no path action
        $this->createEventLog($contact3, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact3, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(true); // No path

        // Contact 4 - stuck at yes path followup
        $this->createEventLog($contact4, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact4, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(false); // Yes path
        $this->createEventLog($contact4, $yesPathAction, $campaign, 1);

        // Contact 5 - stuck at no path followup
        $this->createEventLog($contact5, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact5, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(true); // No path
        $this->createEventLog($contact5, $noPathAction, $campaign, 1);

        // Contact 6 - Not Stuck as event is in scheduled state
        $this->createEventLog($contact6, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact6, $conditionEvent, $campaign, 1);
        $log->setIsScheduled(true);

        // Contact 7 - Not executed any executed root event.

        // Contact 8 - Not Stuck as event is added after first event is executed
        $this->createEventLog($contact8, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact8, $conditionEvent, $campaign, 1);
        $log->setDateTriggered((new \DateTime())->modify('-5 minutes'));

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand(
            [
                'campaign-id'   => $campaignId,
                '--dry-run'     => true,
            ]
        );

        // Verify we found stuck contacts
        $this->assertStringContainsString('Next Event ID', $output);
        $this->assertStringContainsString('Next Event Name', $output);
        $this->assertStringContainsString('Check Contact Field Value Condition', $output);
        $this->assertStringContainsString('Yes Path - Add Tag', $output);
        $this->assertStringContainsString('No Path - Add Tag', $output);
        $this->assertStringNotContainsString((string) $contact8->getId(), $output);

        $output = $this->executeCommand(
            [
                'campaign-id' => $campaignId,
            ]
        );
        $this->em->flush();

        $this->assertStringContainsString('Executing next events', $output);
        $this->assertStringContainsString('total events were executed', $output);

        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $conditionEvent->getId());
        $this->assertCount(1, $contact1Logs, 'Contact 1 should have progressed to the condition event');

        $contact2Logs = $this->findLeadEventLogs($campaign, $contact2->getId(), $yesPathAction->getId());
        $this->assertCount(1, $contact2Logs, 'Contact 2 should have progressed through yes path action');

        $contact3Logs = $this->findLeadEventLogs($campaign, $contact3->getId(), $noPathAction->getId());
        $this->assertCount(1, $contact3Logs, 'Contact 3 should have progressed through no path action');

        $contact4Logs = $this->findLeadEventLogs($campaign, $contact4->getId(), $yesFollowupEmail->getId());
        $this->assertCount(1, $contact4Logs, 'Contact 4 should have progressed to the yes path followup');

        $contact5Logs = $this->findLeadEventLogs($campaign, $contact5->getId(), $noFollowupEmail->getId());
        $this->assertCount(1, $contact5Logs, 'Contact 5 should have progressed to the no path followup');

        $contact6NewLogs = $this->findLeadEventLogs($campaign, $contact6->getId());
        $this->assertCount(2, $contact6NewLogs, 'Contact 6 should not have new unscheduled logs as it was in scheduled state');
    }

    /**
     * Test executing the command with a linear campaign with manually removed contacts.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testCampaignWithManuallyRemovedContacts(): void
    {
        $campaign = $this->createCampaign('Campaign with Manually Removed Contacts');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Active Contact 1');
        $contact2 = $this->createLead('Active Contact 2');
        $contact3 = $this->createLead('Manually Removed Contact');
        $contact4 = $this->createLead('Active Contact 3');
        $contact5 = $this->createLead('Manually Removed Contact 2');

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);
        $this->createCampaignLead($campaign, $contact3, true); // Manually removed
        $this->createCampaignLead($campaign, $contact4);
        $this->createCampaignLead($campaign, $contact5, true); // Manually removed

        $welcomeEmail = $this->createEvent('Welcome Email', $campaign, 'email.send', 'action', ['email' => '1']);
        $addPoints    = $this->createEvent('Add Points', $campaign, 'lead.changepoints', 'action', ['points' => 10]);
        $addPoints->setParent($welcomeEmail);
        $finalEmail = $this->createEvent('Final Email', $campaign, 'email.send', 'action', ['email' => '1']);
        $finalEmail->setParent($addPoints);

        $this->em->persist($addPoints);
        $this->em->persist($finalEmail);

        // Contact 1 - executed welcome email, ready for add points
        $this->createEventLog($contact1, $welcomeEmail, $campaign, 1);

        // Contact 2 - executed welcome email and add points, ready for final email
        $this->createEventLog($contact2, $welcomeEmail, $campaign, 1);
        $this->createEventLog($contact2, $addPoints, $campaign, 1);

        // Contact 3 - manually removed but has welcome email log
        $this->createEventLog($contact3, $welcomeEmail, $campaign, 1);

        // Contact 4 - stuck at welcome email
        $this->createEventLog($contact4, $welcomeEmail, $campaign, 1);

        $this->em->flush();

        $output = $this->executeCommand(
            [
                'campaign-id'   => $campaignId,
                '--dry-run'     => true,
            ]
        );

        // Verify we found stuck contacts but not manually removed ones
        $this->assertStringContainsString('Next Event ID', $output);
        $this->assertStringContainsString('Next Event Name', $output);
        $this->assertStringContainsString('Add Points', $output);
        $this->assertStringContainsString('Final Email', $output);

        // Active Contact 1 and Contact 2 should be in the output
        $this->assertStringContainsString((string) $contact1->getId(), $output);
        $this->assertStringContainsString((string) $contact2->getId(), $output);

        // Manually removed contacts should not be in the output
        $this->assertStringNotContainsString((string) $contact3->getId(), $output);

        $output = $this->executeCommand(
            [
                'campaign-id' => $campaignId,
            ]
        );

        $this->em->flush();

        $this->assertStringContainsString('Executing next events', $output);
        $this->assertStringContainsString('total events were executed', $output);

        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $addPoints->getId());
        $this->assertCount(1, $contact1Logs, 'Active contact 1 should have progressed to add points event');

        $contact2Logs = $this->findLeadEventLogs($campaign, $contact2->getId(), $finalEmail->getId());
        $this->assertCount(1, $contact2Logs, 'Active contact 2 should have progressed to final email event');

        $contact3Logs = $this->findLeadEventLogs($campaign, $contact3->getId(), $addPoints->getId());
        $this->assertCount(0, $contact3Logs, 'Manually removed contact should not have progressed');

        $contact4Logs = $this->findLeadEventLogs($campaign, $contact4->getId(), $addPoints->getId());
        $this->assertCount(1, $contact4Logs, 'Active contact 4 should have progressed to add points event');

        $contact5Logs = $this->findLeadEventLogs($campaign, $contact5->getId());
        $this->assertCount(0, $contact5Logs, 'Manually removed contact should not have any logs');
    }

    /**
     * Test executing the command with a linear campaign with deleted Events.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testCampaignWithDeletedEvents(): void
    {
        $campaign = $this->createCampaign('Campaign with Deleted Events');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Active Contact 1');
        $contact2 = $this->createLead('Active Contact 2');
        $contact3 = $this->createLead('Active Contact 3');

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);
        $this->createCampaignLead($campaign, $contact3); // Manually removed

        // Create a simple linear campaign with 3 events
        $welcomeEmail = $this->createEvent('Welcome Email', $campaign, 'email.send', 'action');
        $addPoints    = $this->createEvent('Add Points', $campaign, 'lead.changepoints', 'action', ['points' => 10, 'eventType' => 'action']);
        $addPoints->setParent($welcomeEmail);
        $welcomeEmail->addChild($addPoints);

        $deletedEmail = $this->createEvent('Deleted Event', $campaign, 'email.send', 'action', ['email' => '1']);
        $deletedEmail->setParent($addPoints);
        $deletedEmail->setDeleted(null);

        $finalEmail = $this->createEvent('Final Email', $campaign, 'email.send', 'action', ['email' => '1']);
        $finalEmail->setParent($addPoints);
        $addPoints->addChild($finalEmail);

        $this->em->persist($addPoints);
        $this->em->persist($finalEmail);

        // Contact 1 - executed welcome email, ready for add points
        $this->createEventLog($contact1, $welcomeEmail, $campaign, 1);

        // Contact 2 - executed welcome email and add points, ready for final email
        $this->createEventLog($contact2, $welcomeEmail, $campaign, 1);
        $this->createEventLog($contact2, $addPoints, $campaign, 1);

        // Contact 3 - same as contact 1 just to verify it processes
        $this->createEventLog($contact3, $welcomeEmail, $campaign, 1);

        $this->em->flush();

        $output = $this->executeCommand(
            [
                'campaign-id'      => $campaignId,
                '--dry-run'        => true,
                '--min-contact-id' => $contact1->getId(),
                '--max-contact-id' => $contact3->getId(),
            ]
        );

        // Verify we found stuck contacts but not manually removed ones
        $this->assertStringContainsString('Next Event ID', $output);
        $this->assertStringContainsString('Next Event Name', $output);
        $this->assertStringContainsString('Add Points', $output);
        $this->assertStringContainsString('Final Email', $output);

        // Active Contact 1 and Contact 2 should be in the output
        $this->assertStringContainsString((string) $contact1->getId(), $output);
        $this->assertStringContainsString((string) $contact2->getId(), $output);

        $output = $this->executeCommand(
            [
                'campaign-id' => $campaignId,
            ]
        );

        $this->em->flush();

        $this->assertStringContainsString('Executing next events', $output);
        $this->assertStringContainsString('total events were executed', $output);

        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $addPoints->getId());
        $this->assertCount(1, $contact1Logs, 'Active contact 1 should have progressed to add points event');

        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $finalEmail->getId());
        $this->assertCount(1, $contact1Logs, 'Active contact 1 should have progressed to Final Email event');

        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $deletedEmail->getId());
        $this->assertCount(0, $contact1Logs, 'Active contact 1 should not have processed Deleted Event');

        $contact2Logs = $this->findLeadEventLogs($campaign, $contact2->getId(), $finalEmail->getId());
        $this->assertCount(1, $contact2Logs, 'Active contact 2 should have progressed to final email event');

        $contact3Logs = $this->findLeadEventLogs($campaign, $contact3->getId(), $addPoints->getId());
        $this->assertCount(1, $contact3Logs, 'Active contact 3 should have progressed to add points event');
    }

    public function testDecisionTypeEventsAreIgnored(): void
    {
        $campaign = $this->createCampaign('Complex Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Complex Contact 1');
        $contact2 = $this->createLead('Complex Contact 2');
        $contact3 = $this->createLead('Complex Contact 3');

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);
        $this->createCampaignLead($campaign, $contact3);

        $rootEmail = $this->createEvent('Welcome Email', $campaign, 'email.send', 'action');

        $decisionEvent = $this->createEvent('Asset Download Decision', $campaign, 'asset.download', 'decision', []);
        $decisionEvent->setParent($rootEmail);

        // Third level events - YES path from decision
        $yesPathAction = $this->createEvent('Yes Path - Add Tag', $campaign, 'lead.changetags', 'action', [
            'add_tags' => [
                'greater than 4',
            ],
        ]);
        $yesPathAction->setParent($decisionEvent);
        $yesPathAction->setDecisionPath('yes');

        // Third level events - NO path from decision
        $noPathAction = $this->createEvent('No Path - Add Tag', $campaign, 'lead.changetags', 'action', [
            'remove_tags' => [
                'less than 4',
            ],
        ]);
        $noPathAction->setParent($decisionEvent);
        $noPathAction->setDecisionPath('no');

        // Fourth level events from YES path
        $yesFollowupEmail = $this->createEvent('Yes Path Followup', $campaign, 'email.send', 'action');
        $yesFollowupEmail->setParent($yesPathAction);

        // Fourth level events from NO path
        $noFollowupEmail = $this->createEvent('No Path Followup', $campaign, 'email.send', 'action');
        $noFollowupEmail->setParent($noPathAction);

        $this->em->persist($decisionEvent);
        $this->em->persist($yesPathAction);
        $this->em->persist($noPathAction);
        $this->em->persist($yesFollowupEmail);
        $this->em->persist($noFollowupEmail);

        // Create event logs to simulate events that have already been executed
        // Contact 1 - executed root email and wait event, ready for decision
        $this->createEventLog($contact1, $rootEmail, $campaign, 1);

        // Contact 2 - executed decision event with yes path, stuck at yes path action
        $this->createEventLog($contact2, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact2, $decisionEvent, $campaign, 1);
        $log->setNonActionPathTaken(false); // Yes path

        // Contact 3 - executed decision event with no path, stuck at no path action
        $this->createEventLog($contact3, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact3, $decisionEvent, $campaign, 1);
        $log->setNonActionPathTaken(true); // No path

        $this->em->flush();

        $output = $this->executeCommand(
            [
                'campaign-id'   => $campaignId,
                '--dry-run'     => true,
            ]
        );

        // Verify we found stuck contacts
        $this->assertStringContainsString('Next Event ID', $output);
        $this->assertStringContainsString('Next Event Name', $output);
        $this->assertStringNotContainsString('Asset Download Decision', $output);
        $this->assertStringContainsString('Yes Path - Add Tag', $output);
        $this->assertStringContainsString('No Path - Add Tag', $output);

        $output = $this->executeCommand(
            [
                'campaign-id' => $campaignId,
            ]
        );
        $this->em->flush();

        $this->assertStringContainsString('Executing next events', $output);
        $this->assertStringContainsString('total events were executed', $output);

        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $decisionEvent->getId());
        $this->assertCount(0, $contact1Logs, 'Contact 1 should NOT have progressed to the decision event');
        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $yesPathAction->getId());
        $this->assertCount(0, $contact1Logs, 'Contact 1 should NOT have progressed to the yes path event');
        $contact1Logs = $this->findLeadEventLogs($campaign, $contact1->getId(), $noPathAction->getId());
        $this->assertCount(0, $contact1Logs, 'Contact 1 should NOT have progressed to the no path event');

        $contact2Logs = $this->findLeadEventLogs($campaign, $contact2->getId(), $yesPathAction->getId());
        $this->assertCount(1, $contact2Logs, 'Contact 2 should have progressed through yes path action');

        $contact3Logs = $this->findLeadEventLogs($campaign, $contact3->getId(), $noPathAction->getId());
        $this->assertCount(1, $contact3Logs, 'Contact 3 should have progressed through no path action');
    }

    private function createStuckContactsTestData(): void
    {
        $campaign = $this->em->getRepository(Campaign::class)->find(1);
        if (!$campaign) {
            $campaign = $this->createCampaign('Test Campaign');
            $campaign->setIsPublished(true);
            $this->em->persist($campaign);
        }

        $contact1 = $this->createLead('Contact 1');
        $contact2 = $this->createLead('Contact 2');
        $contact3 = $this->createLead('Contact 3');

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);
        $this->createCampaignLead($campaign, $contact3);

        $parentEvent1 = $this->createEvent('Parent Event 1', $campaign, 'email.send', 'action');
        $parentEvent2 = $this->createEvent('Parent Decision', $campaign, 'email.open', 'decision');

        $childEvent1 = $this->createEvent('Child Event 1', $campaign, 'lead.changetags', 'action', [
            'remove_tags' => [
                'less than 4',
            ],
        ]);
        $childEvent1->setParent($parentEvent1);
        $childEvent2 = $this->createEvent('Child Event 2', $campaign, 'lead.changepoints', 'action', ['points' => 5]);
        $childEvent2->setParent($parentEvent2);

        $this->em->persist($childEvent1);
        $this->em->persist($childEvent2);

        // Create event logs for parent events to simulate the events were already executed
        $this->createEventLog($contact1, $parentEvent1, $campaign, 1);
        $this->createEventLog($contact2, $parentEvent1, $campaign, 1);
        $this->createEventLog($contact3, $parentEvent2, $campaign, 1);

        $this->em->flush();
    }

    /**
     * @param array<mixed> $args
     */
    private function executeCommand(array $args = []): string
    {
        $commandTester = $this->testSymfonyCommand(ResumeStuckCampaignCommand::COMMAND_NAME, $args);

        return $commandTester->getDisplay();
    }

    /**
     * Find event logs for a specific campaign and lead with optional event filter.
     *
     * @return array<int,LeadEventLog> Array of matching lead event logs
     */
    private function findLeadEventLogs(Campaign $campaign, int|string $leadId, ?int $eventId = null): array
    {
        $criteria = [
            'campaign' => $campaign,
            'lead'     => $leadId,
        ];

        if (null !== $eventId) {
            $criteria['event'] = $eventId;
        }

        return $this->em->getRepository(LeadEventLog::class)->findBy($criteria);
    }
}
