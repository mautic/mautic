<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Executioner;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Functional tests for decision event redirection scenarios.
 * Tests redirection FROM decision events TO other event types (actions/conditions).
 */
final class InactiveExecutionerFunctionalTest extends MauticMysqlTestCase
{
    private InactiveExecutioner $inactiveExecutioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inactiveExecutioner = self::getContainer()->get('mautic.campaign.executioner.inactive');
        \assert($this->inactiveExecutioner instanceof InactiveExecutioner);
    }

    public function testDecisionRedirectionToAlreadyExecutedAction(): void
    {
        $campaign     = $this->createCampaign();
        $contact      = $this->createContact();
        $campaignLead = $this->createCampaignLead($campaign, $contact);

        // Create parent action event (already executed)
        $parentEvent = $this->createActionEvent($campaign, 'Parent Event');

        // Create target action that contact has ALREADY executed
        $targetAction = $this->createActionEvent($campaign, 'Target Action');

        // Create decision event (to be deleted and redirected to already executed action)
        $originalDecision = $this->createDecisionEvent($campaign, 'Source Decision');
        $originalDecision->setParent($parentEvent);

        // Create negative child for the decision (inactive path)
        $negativeAction = $this->createActionEvent($campaign, 'Negative Action', $originalDecision, 'no');
        $originalDecision->addChild($negativeAction);

        // Set up redirection: decision redirects to already executed action
        $originalDecision->setDeleted(new \DateTime());
        $originalDecision->setRedirectEvent($targetAction);

        // Log: Parent event was executed successfully
        $parentEventLog = $this->createEventLog($campaign, $parentEvent, $contact);

        // Log: Target action was ALREADY executed (rotation 1)
        $existingTargetLog = $this->createEventLog($campaign, $targetAction, $contact);

        $this->em->persist($campaign);
        $this->em->persist($contact);
        $this->em->persist($campaignLead);
        $this->em->persist($parentEvent);
        $this->em->persist($targetAction);
        $this->em->persist($originalDecision);
        $this->em->persist($negativeAction);
        $this->em->persist($parentEventLog);
        $this->em->persist($existingTargetLog);
        $this->em->flush();

        $output  = new BufferedOutput();
        $limiter = new ContactLimiter(100, 0, 0, 0, [$contact->getId()]);

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') || define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $this->inactiveExecutioner->validate($originalDecision->getId(), $limiter, $output);

        $targetActionLogsAfter = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $targetAction,
            'lead'  => $contact,
        ], ['rotation' => 'ASC']);

        // Verify that rotation was incremented
        if (count($targetActionLogsAfter) >= 2) {
            $rotations   = array_map(fn ($log) => $log->getRotation(), $targetActionLogsAfter);
            $maxRotation = max($rotations);

            $this->assertGreaterThan(1, $maxRotation, 'Expected rotation to be incremented for already executed event');
        } else {
            $this->fail('Expected redirection to create new log entry');
        }
    }

    public function testDecisionRedirectionToAction(): void
    {
        $campaign     = $this->createCampaign();
        $contact      = $this->createContact();
        $campaignLead = $this->createCampaignLead($campaign, $contact);

        // Create parent action event (already executed)
        $parentEvent = $this->createActionEvent($campaign, 'Parent Event');

        // Create decision event (to be deleted and redirected)
        $originalDecision = $this->createDecisionEvent($campaign, 'Original Decision');
        $originalDecision->setParent($parentEvent);

        // Create negative child for the decision (inactive path)
        $negativeAction = $this->createActionEvent($campaign, 'Negative Action', $originalDecision, 'no');

        // Add the negative action as a child of the decision
        $originalDecision->addChild($negativeAction);

        // Create redirect target action
        $redirectAction = $this->createActionEvent($campaign, 'Redirect Action');

        // Set up redirection: decision is deleted and redirects to action
        $originalDecision->setDeleted(new \DateTime());
        $originalDecision->setRedirectEvent($redirectAction);

        // Log: Parent event was executed successfully
        $parentEventLog = $this->createEventLog($campaign, $parentEvent, $contact);

        // DON'T create any logs for the decision - this means the contact is "inactive"
        // and hasn't executed the decision yet, making them eligible for the inactive decision logic

        $this->em->persist($campaign);
        $this->em->persist($contact);
        $this->em->persist($campaignLead);
        $this->em->persist($parentEvent);
        $this->em->persist($originalDecision);
        $this->em->persist($negativeAction);
        $this->em->persist($redirectAction);
        $this->em->persist($parentEventLog);
        $this->em->flush();

        $output  = new BufferedOutput();
        $limiter = new ContactLimiter(100, 0, 0, 0, [$contact->getId()]);

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') || define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $this->inactiveExecutioner->validate($originalDecision->getId(), $limiter, $output);

        // Verify redirection worked by checking if redirect action was executed
        $redirectActionLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $redirectAction,
            'lead'  => $contact,
        ]);

        $this->assertGreaterThan(0, count($redirectActionLogs), 'Expected logs for redirect action');
    }

    public function testDecisionRedirectionToCondition(): void
    {
        $campaign     = $this->createCampaign();
        $contact      = $this->createContact();
        $campaignLead = $this->createCampaignLead($campaign, $contact);

        // Create parent action event (already executed)
        $parentEvent = $this->createActionEvent($campaign, 'Parent Event Action');

        // Create original decision event (to be deleted and redirected)
        $originalDecision = $this->createDecisionEvent($campaign, 'Original Decision');
        $originalDecision->setParent($parentEvent);

        // Create negative child for the original decision (should NOT be executed)
        $originalNegativeAction = $this->createActionEvent($campaign,
            'Original Negative Action', $originalDecision, 'no');
        $originalDecision->addChild($originalNegativeAction);

        // Create target condition that we redirect to
        $redirectCondition = $this->createConditionEvent($campaign, 'Redirect Condition');

        // Set up redirection: original decision redirects to condition
        $originalDecision->setDeleted(new \DateTime());
        $originalDecision->setRedirectEvent($redirectCondition);

        // Log: Parent event was executed successfully
        $parentEventLog = $this->createEventLog($campaign, $parentEvent, $contact);

        $this->em->persist($campaign);
        $this->em->persist($contact);
        $this->em->persist($campaignLead);
        $this->em->persist($parentEvent);
        $this->em->persist($originalDecision);
        $this->em->persist($originalNegativeAction);
        $this->em->persist($redirectCondition);
        $this->em->persist($parentEventLog);
        $this->em->flush();

        $output  = new BufferedOutput();
        $limiter = new ContactLimiter(100, 0, 0, 0, [$contact->getId()]);

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') || define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $counter = $this->inactiveExecutioner->validate($originalDecision->getId(), $limiter, $output);

        // Verify that the redirect condition was executed
        $redirectConditionLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $redirectCondition,
            'lead'  => $contact,
        ]);

        // Verify that the original decision's negative action was NOT executed
        $originalNegativeActionLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $originalNegativeAction,
            'lead'  => $contact,
        ]);

        // Assertions - verify basic redirection to condition works
        $this->assertGreaterThan(0, count($redirectConditionLogs), 'Expected redirect condition to be executed');
        $this->assertEquals(0, count($originalNegativeActionLogs),
            'Original decision negative action should NOT be executed');

        // Verify execution counters
        $this->assertGreaterThan(0, $counter->getTotalEvaluated(), 'Expected contacts to be evaluated');
        $this->assertGreaterThan(0, $counter->getTotalExecuted(), 'Expected events to be executed');
    }

    public function testMultipleDecisionRedirectionFromSingleParent(): void
    {
        $campaign     = $this->createCampaign();
        $contact      = $this->createContact();
        $campaignLead = $this->createCampaignLead($campaign, $contact);

        // Create parent action event (already executed)
        $parentEvent = $this->createActionEvent($campaign, 'Parent Action Event');

        // Create first decision event with negative child
        $firstDecision = $this->createDecisionEvent($campaign, 'First Decision');
        $firstDecision->setParent($parentEvent);

        $firstNegativeAction = $this->createActionEvent($campaign, 'First Negative Action', $firstDecision, 'no');
        $firstNegativeAction->setTriggerInterval(1);
        $firstNegativeAction->setTriggerIntervalUnit('d'); // 1 day delay
        $firstDecision->addChild($firstNegativeAction);

        // Create second decision event with negative child (different delay)
        $secondDecision = $this->createDecisionEvent($campaign, 'Second Decision');
        $secondDecision->setParent($parentEvent);

        $secondNegativeAction = $this->createActionEvent($campaign, 'Second Negative Action', $secondDecision, 'no');
        $secondNegativeAction->setTriggerInterval(2);
        $secondNegativeAction->setTriggerIntervalUnit('d'); // 2 day delay
        $secondDecision->addChild($secondNegativeAction);

        // Create redirect target events
        $firstRedirectAction  = $this->createActionEvent($campaign, 'First Redirect Action');
        $secondRedirectAction = $this->createActionEvent($campaign, 'Second Redirect Action');

        // Set up redirection: delete both decisions and redirect to different events
        $firstDecision->setDeleted(new \DateTime());
        $firstDecision->setRedirectEvent($firstRedirectAction);

        $secondDecision->setDeleted(new \DateTime());
        $secondDecision->setRedirectEvent($secondRedirectAction);

        // Log: Parent event was executed successfully
        $parentEventLog = $this->createEventLog($campaign, $parentEvent, $contact);

        // Don't create any logs for the decisions - contacts are "inactive" and waiting for both decisions

        $this->em->persist($campaign);
        $this->em->persist($contact);
        $this->em->persist($campaignLead);
        $this->em->persist($parentEvent);
        $this->em->persist($firstDecision);
        $this->em->persist($firstNegativeAction);
        $this->em->persist($secondDecision);
        $this->em->persist($secondNegativeAction);
        $this->em->persist($firstRedirectAction);
        $this->em->persist($secondRedirectAction);
        $this->em->persist($parentEventLog);
        $this->em->flush();

        $output  = new BufferedOutput();
        $limiter = new ContactLimiter(100, 0, 0, 0, [$contact->getId()]);

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') || define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        // Validate first decision (should redirect to first redirect action)
        $this->inactiveExecutioner->validate($firstDecision->getId(), $limiter, $output);

        // Validate second decision (should redirect to second redirect action)
        $this->inactiveExecutioner->validate($secondDecision->getId(), $limiter, $output);

        // Verify both redirections worked
        $firstRedirectLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $firstRedirectAction,
            'lead'  => $contact,
        ]);

        $secondRedirectLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $secondRedirectAction,
            'lead'  => $contact,
        ]);

        $this->assertGreaterThan(0, count($firstRedirectLogs), 'Expected logs for first redirect action');
        $this->assertGreaterThan(0, count($secondRedirectLogs), 'Expected logs for second redirect action');

        // Verify original negative actions were NOT executed
        $firstNegativeLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $firstNegativeAction,
            'lead'  => $contact,
        ]);

        $secondNegativeLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'event' => $secondNegativeAction,
            'lead'  => $contact,
        ]);

        $this->assertCount(0, $firstNegativeLogs, 'Expected no logs for first negative action (should be bypassed)');
        $this->assertCount(0, $secondNegativeLogs, 'Expected no logs for second negative action (should be bypassed)');
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Decision Redirection Test Campaign');
        $campaign->setIsPublished(true);

        return $campaign;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setFirstname('Test');
        $contact->setLastname('Contact');
        $contact->setEmail('test@example.com');

        return $contact;
    }

    private function createCampaignLead(Campaign $campaign, Lead $contact): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setDateAdded(new \DateTime('-2 days'));
        $campaignLead->setManuallyAdded(false);
        $campaignLead->setManuallyRemoved(false);
        $campaignLead->setRotation(1);

        return $campaignLead;
    }

    private function createEventLog(Campaign $campaign, Event $event, Lead $contact): LeadEventLog
    {
        $eventLog = new LeadEventLog();
        $eventLog->setCampaign($campaign);
        $eventLog->setEvent($event);
        $eventLog->setLead($contact);
        $eventLog->setRotation(1);
        $eventLog->setDateTriggered(new \DateTime('-1 day'));
        $eventLog->setIsScheduled(false);

        return $eventLog;
    }

    private function createDecisionEvent(Campaign $campaign, string $name): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName($name);
        $event->setType('form.submit');
        $event->setEventType(Event::TYPE_DECISION);
        $event->setProperties([
            'forms' => [],
        ]);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $event->setOrder(2);

        return $event;
    }

    private function createActionEvent(Campaign $campaign, string $name, ?Event $parent = null,
        ?string $decisionPath = null): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName($name);
        $event->setType('lead.changepoints');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setProperties(['points' => 10]);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $event->setOrder($parent ? 3 : 1);

        if ($parent) {
            $event->setParent($parent);
            if ($decisionPath) {
                $event->setDecisionPath($decisionPath);
            }
        }

        return $event;
    }

    private function createConditionEvent(Campaign $campaign, string $name, ?Event $parent = null): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName($name);
        $event->setType('lead.field_value');
        $event->setEventType(Event::TYPE_CONDITION);
        $event->setProperties([
            'field'    => 'email',
            'operator' => 'empty',
            'value'    => '',
        ]);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $event->setOrder($parent ? 3 : 2);

        if ($parent) {
            $event->setParent($parent);
        }

        return $event;
    }
}
