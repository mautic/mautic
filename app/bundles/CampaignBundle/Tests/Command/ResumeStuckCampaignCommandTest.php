<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Command\ResumeStuckCampaignCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;

#[Group('non-parallel')]
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
        $this->markEventLogAsCompleted($log);

        // Contact 3 - executed decision event with no path, stuck at no path action
        $this->createEventLog($contact3, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact3, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(true); // No path
        $this->markEventLogAsCompleted($log);

        // Contact 4 - stuck at yes path followup
        $this->createEventLog($contact4, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact4, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(false); // Yes path
        $this->markEventLogAsCompleted($log);
        $this->createEventLog($contact4, $yesPathAction, $campaign, 1);

        // Contact 5 - stuck at no path followup
        $this->createEventLog($contact5, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact5, $conditionEvent, $campaign, 1);
        $log->setNonActionPathTaken(true); // No path
        $this->markEventLogAsCompleted($log);
        $this->createEventLog($contact5, $noPathAction, $campaign, 1);

        // Contact 6 - Not Stuck as event is in scheduled state
        $this->createEventLog($contact6, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact6, $conditionEvent, $campaign, 1);
        $log->setIsScheduled(true);

        // Contact 7 - Not executed any executed root event.

        // Contact 8 - Not Stuck as event is added after first event is executed
        $l1 = $this->createEventLog($contact8, $rootEmail, $campaign, 1);
        $l1->setDateTriggered(new \DateTime()->modify('-5 minutes'));
        $this->markEventLogAsCompleted($l1);

        $log = $this->createEventLog($contact8, $conditionEvent, $campaign, 1);
        $log->setDateTriggered(new \DateTime()->modify('-5 minutes'));

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
    #[RunInSeparateProcess]
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
    #[RunInSeparateProcess]
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

    /**
     * Test the core bug fix: a condition event log at version=1 means the job was killed
     * mid-execution (entry inserted in DB but condition evaluation never completed).
     * The stuck command must NOT treat it as a completed condition and must NOT proceed
     * to execute children (yes/no path). Instead the condition itself must be re-executed.
     */
    public function testStuckConditionEventAtVersionOneIsReExecutedNotSkipped(): void
    {
        $campaign = $this->createCampaign('Stuck Condition Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contactStuck     = $this->createLead('Stuck Condition Contact');
        $contactCompleted = $this->createLead('Completed Condition Contact');

        $this->createCampaignLead($campaign, $contactStuck);
        $this->createCampaignLead($campaign, $contactCompleted);

        sleep(1);

        $rootAction = $this->createEvent('Root Action', $campaign, 'email.send', 'action');

        $conditionEvent = $this->createEvent('Stuck Condition', $campaign, 'lead.field_value', 'condition', [
            'field'    => 'points',
            'operator' => 'gt',
            'value'    => '4',
        ]);
        $conditionEvent->setParent($rootAction);

        $yesPathAction = $this->createEvent('Yes Child Action', $campaign, 'lead.changetags', 'action', [
            'add_tags' => ['yes-tag'],
        ]);
        $yesPathAction->setParent($conditionEvent);
        $yesPathAction->setDecisionPath('yes');

        $noPathAction = $this->createEvent('No Child Action', $campaign, 'lead.changetags', 'action', [
            'add_tags' => ['no-tag'],
        ]);
        $noPathAction->setParent($conditionEvent);
        $noPathAction->setDecisionPath('no');

        $this->em->persist($conditionEvent);
        $this->em->persist($yesPathAction);
        $this->em->persist($noPathAction);

        sleep(1);
        // contactStuck: root executed + condition log at version=1 (stuck mid-execution, non_action_path_taken=0)
        $this->createEventLog($contactStuck, $rootAction, $campaign, 1);
        $this->createEventLog($contactStuck, $conditionEvent, $campaign, 1);

        // contactCompleted: root executed + condition log fully completed at version=2
        $this->createEventLog($contactCompleted, $rootAction, $campaign, 1);
        $completedLog = $this->createEventLog($contactCompleted, $conditionEvent, $campaign, 1);
        $completedLog->setNonActionPathTaken(false); // Yes path taken
        $this->markEventLogAsCompleted($completedLog);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // The stuck condition (version=1) must appear as the next event to re-execute
        $this->assertStringContainsString('Stuck Condition', $output,
            'Stuck condition event (version=1) must itself be listed for re-execution');

        // The yes/no children must NOT appear for the stuck contact (contactStuck) in the dry-run
        // output because the condition was never completed (version=1). The query must guard
        // against picking up children of condition/decision logs with version=1.
        $this->assertStringNotContainsString('No Child Action', $output,
            'No-path child must not be listed — condition was never evaluated for any contact');

        // contactCompleted's child (yes path, version=2) SHOULD appear only for contactCompleted.
        // We verify the stuck contact's ID is NOT paired with Yes Child Action.
        $this->assertStringContainsString('Yes Child Action', $output,
            'Completed condition contact should show yes path child as next event');

        // Verify that the stuck contact (contactStuck) is NOT associated with Yes Child Action.
        // The output table has one row per contact per event, so we check that the stuck contact
        // ID does not appear on the same row as Yes Child Action.
        $outputLines = explode("\n", $output);
        foreach ($outputLines as $line) {
            if (str_contains($line, (string) $contactStuck->getId())) {
                $this->assertStringNotContainsString('Yes Child Action', $line,
                    'Stuck contact must NOT have Yes Child Action listed as a next event');
                $this->assertStringNotContainsString('No Child Action', $line,
                    'Stuck contact must NOT have No Child Action listed as a next event');
            }
        }

        $this->executeCommand(['campaign-id' => $campaignId]);
        $this->em->flush();
        $this->em->clear();

        $stuckContactYesLogs = $this->findLeadEventLogs($campaign, $contactStuck->getId(), $yesPathAction->getId());
        $this->assertCount(0, $stuckContactYesLogs,
            'Stuck contact must NOT have yes path executed — condition was never evaluated');

        $stuckContactNoLogs = $this->findLeadEventLogs($campaign, $contactStuck->getId(), $noPathAction->getId());
        $this->assertCount(0, $stuckContactNoLogs,
            'Stuck contact must NOT have no path executed — condition was never evaluated');

        $completedContactYesLogs = $this->findLeadEventLogs($campaign, $contactCompleted->getId(), $yesPathAction->getId());
        $this->assertCount(1, $completedContactYesLogs,
            'Completed condition contact must have yes path child executed');
    }

    /**
     * Same as testStuckConditionEventAtVersionOneIsReExecutedNotSkipped but for decision events.
     * A decision log stuck at version=1 must wait, and its yes/no path
     * children must NOT be executed prematurely.
     */
    public function testStuckDecisionEventAtVersionOneIsNotSkipped(): void
    {
        $campaign = $this->createCampaign('Stuck Decision Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contactStuck     = $this->createLead('Stuck Decision Contact');
        $contactCompleted = $this->createLead('Completed Decision Contact');

        $this->createCampaignLead($campaign, $contactStuck);
        $this->createCampaignLead($campaign, $contactCompleted);

        sleep(1);

        $rootAction = $this->createEvent('Root Action For Decision', $campaign, 'email.send', 'action');

        $decisionEvent = $this->createEvent('Stuck Decision', $campaign, 'asset.download', 'decision', []);
        $decisionEvent->setParent($rootAction);

        $yesPathAction = $this->createEvent('Decision Yes Child Action', $campaign, 'lead.changetags', 'action', [
            'add_tags' => ['yes-decision-tag'],
        ]);
        $yesPathAction->setParent($decisionEvent);
        $yesPathAction->setDecisionPath('yes');

        $noPathAction = $this->createEvent('Decision No Child Action', $campaign, 'lead.changetags', 'action', [
            'add_tags' => ['no-decision-tag'],
        ]);
        $noPathAction->setParent($decisionEvent);
        $noPathAction->setDecisionPath('no');

        $this->em->persist($decisionEvent);
        $this->em->persist($yesPathAction);
        $this->em->persist($noPathAction);

        // contactStuck: root executed + decision log at version=1 (stuck mid-execution)
        $this->createEventLog($contactStuck, $rootAction, $campaign, 1);
        $this->createEventLog($contactStuck, $decisionEvent, $campaign, 1);

        // contactCompleted: root executed + decision log fully completed (version=2, yes path)
        $this->createEventLog($contactCompleted, $rootAction, $campaign, 1);
        $completedLog = $this->createEventLog($contactCompleted, $decisionEvent, $campaign, 1);
        $completedLog->setNonActionPathTaken(false); // Yes path taken
        $this->markEventLogAsCompleted($completedLog);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        $this->assertStringNotContainsString('Stuck Decision', $output,
            'No Stuck decision event will be executed');

        $this->assertStringNotContainsString('Decision No Child Action', $output,
            'No-path child must not be listed — decision was never evaluated for any contact');

        $this->assertStringContainsString('Decision Yes Child Action', $output,
            'Completed decision contact should show yes path child as next event');

        $this->executeCommand(['campaign-id' => $campaignId]);
        $this->em->flush();
        $this->em->clear();

        // contactStuck must NOT have yes/no children executed
        $stuckYesLogs = $this->findLeadEventLogs($campaign, $contactStuck->getId(), $yesPathAction->getId());
        $this->assertCount(0, $stuckYesLogs,
            'Stuck contact must NOT have yes path executed — decision was never evaluated');

        $stuckNoLogs = $this->findLeadEventLogs($campaign, $contactStuck->getId(), $noPathAction->getId());
        $this->assertCount(0, $stuckNoLogs,
            'Stuck contact must NOT have no path executed — decision was never evaluated');

        $completedYesLogs = $this->findLeadEventLogs($campaign, $contactCompleted->getId(), $yesPathAction->getId());
        $this->assertCount(1, $completedYesLogs,
            'Completed decision contact must have yes path child executed');
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
        $this->markEventLogAsCompleted($log);

        // Contact 3 - executed decision event with no path, stuck at no path action
        $this->createEventLog($contact3, $rootEmail, $campaign, 1);
        $log = $this->createEventLog($contact3, $decisionEvent, $campaign, 1);
        $log->setNonActionPathTaken(true); // No path
        $this->markEventLogAsCompleted($log);

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

    /**
     * Test deeply nested event hierarchy (4+ levels).
     */
    public function testDeeplyNestedEventHierarchy(): void
    {
        $campaign = $this->createCampaign('Deep Hierarchy Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact = $this->createLead('Deep Hierarchy Contact');
        $this->createCampaignLead($campaign, $contact);

        sleep(1);

        // Create 5-level deep hierarchy
        $level1 = $this->createEvent('Level 1', $campaign, 'email.send', 'action');
        $level2 = $this->createEvent('Level 2', $campaign, 'lead.changepoints', 'action', ['points' => 5]);
        $level2->setParent($level1);

        $level3 = $this->createEvent('Level 3', $campaign, 'lead.changetags', 'action', ['add_tags' => ['l3']]);
        $level3->setParent($level2);

        $level4 = $this->createEvent('Level 4', $campaign, 'lead.changepoints', 'action', ['points' => 10]);
        $level4->setParent($level3);

        $level5 = $this->createEvent('Level 5', $campaign, 'lead.changetags', 'action', ['add_tags' => ['l5']]);
        $level5->setParent($level4);

        $this->em->persist($level2);
        $this->em->persist($level3);
        $this->em->persist($level4);
        $this->em->persist($level5);

        $this->createEventLog($contact, $level1, $campaign, 1);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // Level 2 should appear as next event
        $this->assertStringContainsString('Level 2', $output);
        $this->assertStringNotContainsString('Level 5', $output);

        // Execute and verify progression
        $this->executeCommand(['campaign-id' => $campaignId]);
        $this->em->flush();

        $level2Logs = $this->findLeadEventLogs($campaign, $contact->getId(), $level2->getId());
        $this->assertCount(1, $level2Logs, 'Should progress to Level 2');
    }

    /**
     * Test multiple sibling events at same level.
     */
    public function testMultipleSiblingEvents(): void
    {
        $campaign = $this->createCampaign('Sibling Events Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Sibling Contact 1');
        $contact2 = $this->createLead('Sibling Contact 2');

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);

        sleep(1);

        $rootEvent = $this->createEvent('Root', $campaign, 'email.send', 'action');

        // Create 3 sibling children
        $sibling1 = $this->createEvent('Sibling 1', $campaign, 'lead.changepoints', 'action', ['points' => 5]);
        $sibling1->setParent($rootEvent);

        $sibling2 = $this->createEvent('Sibling 2', $campaign, 'lead.changetags', 'action', ['add_tags' => ['s2']]);
        $sibling2->setParent($rootEvent);

        $sibling3 = $this->createEvent('Sibling 3', $campaign, 'lead.changepoints', 'action', ['points' => 10]);
        $sibling3->setParent($rootEvent);

        $this->em->persist($sibling1);
        $this->em->persist($sibling2);
        $this->em->persist($sibling3);

        $this->createEventLog($contact1, $rootEvent, $campaign, 1);
        $this->createEventLog($contact2, $rootEvent, $campaign, 1);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // All siblings should appear as potential next events
        $this->assertStringContainsString('Sibling 1', $output);
        $this->assertStringContainsString('Sibling 2', $output);
        $this->assertStringContainsString('Sibling 3', $output);
    }

    /**
     * Test: Events with trigger date in future should not execute.
     */
    public function testFutureScheduledEventsShouldNotExecute(): void
    {
        $campaign = $this->createCampaign('Future Scheduled Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact = $this->createLead('Future Scheduled Contact');
        $this->createCampaignLead($campaign, $contact);

        sleep(1);

        $rootEvent  = $this->createEvent('Root Event', $campaign, 'email.send', 'action');
        $childEvent = $this->createEvent('Child Event', $campaign, 'lead.changepoints', 'action', ['points' => 10]);
        $childEvent->setParent($rootEvent);
        $this->em->persist($childEvent);

        $this->createEventLog($contact, $rootEvent, $campaign, 1);
        // Child event scheduled for future
        $scheduledLog = $this->createEventLog($contact, $childEvent, $campaign, 1);
        $scheduledLog->setIsScheduled(true);
        $scheduledLog->setTriggerDate(new \DateTime()->modify('+1 day'));

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // Scheduled event should NOT appear
        $this->assertStringNotContainsString('Child Event', $output);
    }

    /**
     * Test: Events with trigger date in past (but scheduled) should NOT trigger by this command.
     * Note: In practice, scheduled events remain scheduled regardless of trigger date.
     * This test verifies actual behavior where scheduled events don't appear as "next events".
     */
    public function testPastScheduledEventsShouldNotAppearInNextEvents(): void
    {
        $campaign = $this->createCampaign('Past Scheduled Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact = $this->createLead('Past Scheduled Contact');
        $this->createCampaignLead($campaign, $contact);

        sleep(1);

        $rootEvent  = $this->createEvent('Root Event', $campaign, 'email.send', 'action');
        $childEvent = $this->createEvent('Child Event', $campaign, 'lead.changepoints', 'action', ['points' => 10]);
        $childEvent->setParent($rootEvent);
        $this->em->persist($childEvent);

        $this->createEventLog($contact, $rootEvent, $campaign, 1);
        $scheduledLog = $this->createEventLog($contact, $childEvent, $campaign, 1);
        $scheduledLog->setIsScheduled(true);
        $scheduledLog->setTriggerDate(new \DateTime()->modify('-1 day'));

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        $this->assertStringNotContainsString((string) $contact->getId(), $output,
            'Contacts with scheduled events should not appear in next events');
    }

    /**
     * Test: Events from other campaigns should not be processed.
     */
    public function testMultipleCampaignsIsolation(): void
    {
        $campaign1 = $this->createCampaign('Campaign 1');
        $campaign1->setIsPublished(true);
        $this->em->persist($campaign1);

        $campaign2 = $this->createCampaign('Campaign 2');
        $campaign2->setIsPublished(true);
        $this->em->persist($campaign2);

        $this->em->flush();

        $contact1 = $this->createLead('Contact 1');
        $contact2 = $this->createLead('Contact 2');

        $this->createCampaignLead($campaign1, $contact1);
        $this->createCampaignLead($campaign2, $contact2);

        sleep(1);

        $event1 = $this->createEvent('Event 1', $campaign1, 'email.send', 'action');
        $child1 = $this->createEvent('Child 1', $campaign1, 'lead.changepoints', 'action', ['points' => 5]);
        $child1->setParent($event1);

        $event2 = $this->createEvent('Event 2', $campaign2, 'email.send', 'action');
        $child2 = $this->createEvent('Child 2', $campaign2, 'lead.changepoints', 'action', ['points' => 5]);
        $child2->setParent($event2);

        $this->em->persist($child1);
        $this->em->persist($child2);

        $this->createEventLog($contact1, $event1, $campaign1, 1);
        $this->createEventLog($contact2, $event2, $campaign2, 1);

        $this->em->flush();

        sleep(1);

        // Process only campaign 1
        $output = $this->executeCommand([
            'campaign-id'   => $campaign1->getId(),
            '--dry-run'     => true,
        ]);

        $this->assertStringContainsString('Child 1', $output);
        $this->assertStringNotContainsString('Child 2', $output);

        $this->executeCommand(['campaign-id' => $campaign1->getId()]);
        $this->em->flush();

        $campaign2ContactLogs = $this->findLeadEventLogs($campaign2, $contact2->getId(), $child2->getId());
        $this->assertCount(0, $campaign2ContactLogs,
            'Campaign 2 events should not be processed when processing Campaign 1');
    }

    /**
     * Test: Contact deleted from campaign should not have events processed.
     */
    public function testDeletedFromCampaignContactNotProcessed(): void
    {
        $campaign = $this->createCampaign('Deleted Contact Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Active Contact');
        $contact2 = $this->createLead('Deleted Contact');

        $this->createCampaignLead($campaign, $contact1);
        $campaignLead2 = $this->createCampaignLead($campaign, $contact2);
        $campaignLead2->setManuallyRemoved(true);

        sleep(1);

        $event = $this->createEvent('Event', $campaign, 'email.send', 'action');
        $child = $this->createEvent('Child', $campaign, 'lead.changepoints', 'action', ['points' => 5]);
        $child->setParent($event);
        $this->em->persist($child);

        $this->createEventLog($contact1, $event, $campaign, 1);
        $this->createEventLog($contact2, $event, $campaign, 1);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // Active contact should appear
        $this->assertStringContainsString((string) $contact1->getId(), $output);
        // Deleted contact should NOT appear
        $this->assertStringNotContainsString((string) $contact2->getId(), $output);
    }

    /**
     * Test: Multiple yes-path conditions with different evaluations.
     */
    public function testMultipleConditionPathsIndependent(): void
    {
        $campaign = $this->createCampaign('Multi-Condition Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact1 = $this->createLead('Condition Contact 1');
        $contact2 = $this->createLead('Condition Contact 2');
        $contact3 = $this->createLead('Condition Contact 3');

        $this->createCampaignLead($campaign, $contact1);
        $this->createCampaignLead($campaign, $contact2);
        $this->createCampaignLead($campaign, $contact3);

        sleep(1);

        $rootEvent = $this->createEvent('Root', $campaign, 'email.send', 'action');

        $cond1 = $this->createEvent('Condition 1', $campaign, 'lead.field_value', 'condition', ['field' => 'points']);
        $cond1->setParent($rootEvent);

        $cond1Yes = $this->createEvent('Cond1 Yes', $campaign, 'lead.changetags', 'action', ['add_tags' => ['c1yes']]);
        $cond1Yes->setParent($cond1);
        $cond1Yes->setDecisionPath('yes');

        $cond1No = $this->createEvent('Cond1 No', $campaign, 'lead.changetags', 'action', ['add_tags' => ['c1no']]);
        $cond1No->setParent($cond1);
        $cond1No->setDecisionPath('no');

        $this->em->persist($cond1);
        $this->em->persist($cond1Yes);
        $this->em->persist($cond1No);

        // All executed root
        $this->createEventLog($contact1, $rootEvent, $campaign, 1);
        $this->createEventLog($contact2, $rootEvent, $campaign, 1);
        $this->createEventLog($contact3, $rootEvent, $campaign, 1);

        // Contact 1 took yes path
        $log1 = $this->createEventLog($contact1, $cond1, $campaign, 1);
        $log1->setNonActionPathTaken(false);
        $this->markEventLogAsCompleted($log1);

        // Contact 2 took no path
        $log2 = $this->createEventLog($contact2, $cond1, $campaign, 1);
        $log2->setNonActionPathTaken(true);
        $this->markEventLogAsCompleted($log2);

        // Contact 3 condition not evaluated yet (stuck at version 1)
        $this->createEventLog($contact3, $cond1, $campaign, 1);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // Yes child should appear for contact 1
        $this->assertStringContainsString('Cond1 Yes', $output);
        // No child should appear for contact 2
        $this->assertStringContainsString('Cond1 No', $output);
        // Condition should appear for contact 3
        $this->assertStringContainsString('Condition 1', $output);
    }

    /**
     * Test: Condition with no path taken should only execute no-path children.
     */
    public function testConditionNoPathExecution(): void
    {
        $campaign = $this->createCampaign('No Path Execution Campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $campaignId = $campaign->getId();

        $contact = $this->createLead('No Path Contact');
        $this->createCampaignLead($campaign, $contact);

        sleep(1);

        $root = $this->createEvent('Root', $campaign, 'email.send', 'action');

        $cond = $this->createEvent('Condition', $campaign, 'lead.field_value', 'condition');
        $cond->setParent($root);

        $condYes = $this->createEvent('Cond Yes', $campaign, 'lead.changetags', 'action', ['add_tags' => ['yes']]);
        $condYes->setParent($cond);
        $condYes->setDecisionPath('yes');

        $condNo = $this->createEvent('Cond No', $campaign, 'lead.changetags', 'action', ['add_tags' => ['no']]);
        $condNo->setParent($cond);
        $condNo->setDecisionPath('no');

        $this->em->persist($cond);
        $this->em->persist($condYes);
        $this->em->persist($condNo);

        $this->createEventLog($contact, $root, $campaign, 1);

        $condLog = $this->createEventLog($contact, $cond, $campaign, 1);
        $condLog->setNonActionPathTaken(true); // No path
        $this->markEventLogAsCompleted($condLog);

        $this->em->flush();

        sleep(1);

        $output = $this->executeCommand([
            'campaign-id' => $campaignId,
            '--dry-run'   => true,
        ]);

        // Only no-path child should appear
        $this->assertStringContainsString('Cond No', $output);
        $this->assertStringNotContainsString('Cond Yes', $output);

        $this->executeCommand(['campaign-id' => $campaignId]);
        $this->em->flush();

        // Verify only no-path child was executed
        $yesLogs = $this->findLeadEventLogs($campaign, $contact->getId(), $condYes->getId());
        $this->assertCount(0, $yesLogs, 'Yes path should not be executed');

        $noLogs = $this->findLeadEventLogs($campaign, $contact->getId(), $condNo->getId());
        $this->assertCount(1, $noLogs, 'No path should be executed');
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
        $decisionLog = $this->createEventLog($contact3, $parentEvent2, $campaign, 1);
        $this->markEventLogAsCompleted($decisionLog);

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
