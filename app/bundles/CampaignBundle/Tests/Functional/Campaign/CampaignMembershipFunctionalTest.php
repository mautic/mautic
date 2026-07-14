<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

final class CampaignMembershipFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configParams['campaign_republish_behavior'] = 'restart_on_publish';
    }

    public function testCampaignDoesNotResendImmediateEmailAfterContactExitsAndRejoinsWhenRestartIsDisabled(): void
    {
        $contact = new Lead();
        $contact->setEmail('test-natural-exit@example.com');
        $this->em->persist($contact);

        $campaign = new Campaign();
        $campaign->setName('Test Restart Disabled Campaign');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(false); // User explicitly disabled
        $this->em->persist($campaign);

        $event = new Event();
        $event->setName('Immediate Email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setCampaign($campaign);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $this->em->persist($event);

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($contact);
        $campaignLead->setDateAdded(new \DateTime('-1 day'));
        $campaignLead->setManuallyAdded(false);
        $campaignLead->setManuallyRemoved(false);
        $campaignLead->setRotation(1);
        $this->em->persist($campaignLead);

        $this->em->flush();

        $campaignId = $campaign->getId();
        $eventId    = $event->getId();
        $contactId  = $contact->getId();
        $db         = $this->em->getConnection();
        $prefix     = static::getContainer()->getParameter('mautic.db_table_prefix');

        $this->em->clear();

        // Phase 1: Initial trigger - action executes at rotation=1
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaignId]);
        $this->em->clear();

        $logsPhase1 = $db->createQueryBuilder()
            ->select('rotation, is_scheduled')
            ->from($prefix.'campaign_lead_event_log', 'log')
            ->where('log.event_id = :eventId AND log.lead_id = :leadId')
            ->setParameters(['eventId' => $eventId, 'leadId' => $contactId])
            ->executeQuery()->fetchAllAssociative();

        $this->assertCount(1, $logsPhase1, 'Phase 1: First trigger creates exactly 1 log');
        $this->assertEquals(1, $logsPhase1[0]['rotation'], 'Phase 1: Log at rotation=1');

        $db->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'campaign_leads SET manually_removed = 1, date_last_exited = NOW() WHERE campaign_id = ? AND lead_id = ?',
            [$campaignId, $contactId]
        );
        $this->em->clear();

        $adder              = static::getContainer()->get(Adder::class);
        $campaignLeadEntity = $this->em->getRepository(CampaignLead::class)->findOneBy([
            'lead'     => $contactId,
            'campaign' => $campaignId,
        ]);

        $didThrow = false;
        try {
            // Regression path: campaign action add-to-campaign re-adds with manual=true.
            $adder->updateExistingMembership($campaignLeadEntity, true);
        } catch (ContactCannotBeAddedToCampaignException $e) {
            $didThrow = true;
            $this->assertStringContainsString(
                'cannot restart',
                $e->getMessage()
            );
        }

        $this->assertTrue($didThrow, 'Exited contacts must not be re-added when allowRestart=false.');

        $rotationAfterAttempt = (int) $db->createQueryBuilder()
            ->select('rotation')
            ->from($prefix.'campaign_leads', 'cl')
            ->where('cl.campaign_id = :campaignId AND cl.lead_id = :leadId')
            ->setParameters(['campaignId' => $campaignId, 'leadId' => $contactId])
            ->executeQuery()
            ->fetchOne();

        $this->assertSame(1, $rotationAfterAttempt, 'Rotation must stay unchanged when re-entry is blocked.');

        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaignId]);
        $this->em->clear();

        $logsFinal = $db->createQueryBuilder()
            ->select('rotation, is_scheduled')
            ->from($prefix.'campaign_lead_event_log', 'log')
            ->where('log.event_id = :eventId AND log.lead_id = :leadId')
            ->orderBy('log.id', 'ASC')
            ->setParameters(['eventId' => $eventId, 'leadId' => $contactId])
            ->executeQuery()->fetchAllAssociative();

        $this->assertCount(
            1,
            $logsFinal,
            'Forum issue #16133 fixed: Natural exit with allowRestart=false blocks automatic re-entry. '.
            'Action does not re-execute on subsequent trigger runs.'
        );

        $this->assertEquals(1, $logsFinal[0]['rotation'], 'Single log remains at rotation=1');
    }
}
