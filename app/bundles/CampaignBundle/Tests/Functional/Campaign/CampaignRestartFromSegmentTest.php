<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;

/**
 * @see https://github.com/mautic/mautic/issues/16026
 */
final class CampaignRestartFromSegmentTest extends MauticMysqlTestCase
{
    use CampaignEntitiesTrait;

    protected $useCleanupRollback = false;

    public function testCampaignRestartsWhenContactReturnsToSegment(): void
    {
        // Arrange: create a segment and a campaign with "Allow contact to restart campaign" enabled
        $segment = $this->createSegment('restart-test-segment', []);

        $campaign = new Campaign();
        $campaign->setName('Restart Test Campaign');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(true);
        $campaign->addList($segment);
        $this->em->persist($campaign);

        // Create a contact and add them to the segment
        $contact  = $this->createLead('RestartTestContact');
        $listLead = new ListLead();
        $listLead->setLead($contact);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);

        // Create a "change points" action in the campaign (simple, observable side-effect)
        $event = $this->createEvent(
            'Add 10 points',
            $campaign,
            'lead.changepoints',
            'action',
            ['points' => 10]
        );

        $this->em->flush();
        $this->em->clear();

        // Act (first run): rebuild campaign membership, then trigger campaign events
        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        // Assert: contact is in the campaign at rotation 1
        $this->em->clear();

        $campaignLead = $this->em->getRepository(CampaignLead::class)->findOneBy([
            'lead'     => $contact->getId(),
            'campaign' => $campaign->getId(),
        ]);

        Assert::assertNotNull($campaignLead, 'Contact should be in the campaign after first rebuild.');
        Assert::assertFalse($campaignLead->wasManuallyRemoved(), 'Contact should not be removed after first rebuild.');
        Assert::assertSame(1, $campaignLead->getRotation(), 'Rotation should be 1 after first run.');

        $logsAfterFirstRun = $this->em->getRepository(LeadEventLog::class)->findBy([
            'lead'     => $contact->getId(),
            'campaign' => $campaign->getId(),
        ]);
        Assert::assertCount(1, $logsAfterFirstRun, 'Campaign event should have run once after first trigger.');

        // Simulate contact leaving the segment (e.g. campaign removes their tag).
        // Use DBAL directly to avoid ORM caching complications with composite-PK entities.
        $connection = $this->em->getConnection();
        $affected   = $connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'lead_lists_leads SET manually_removed = 1 WHERE lead_id = ? AND leadlist_id = ?',
            [$contact->getId(), $segment->getId()]
        );
        Assert::assertSame(1, $affected, 'Expected exactly one ListLead row to be updated.');
        $this->em->clear();

        // Rebuild: contact is now orphaned in the campaign (in campaign_leads but no longer in segment)
        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);

        $this->em->clear();

        $campaignLead = $this->em->getRepository(CampaignLead::class)->findOneBy([
            'lead'     => $contact->getId(),
            'campaign' => $campaign->getId(),
        ]);

        Assert::assertNotNull($campaignLead, 'Campaign lead record should still exist after soft-removal.');
        Assert::assertTrue($campaignLead->wasManuallyRemoved(), 'Contact should be removed from campaign after leaving segment.');
        Assert::assertNotNull($campaignLead->getDateLastExited(), 'date_last_exited should be set when removed via segment departure.');

        // Simulate contact returning to the segment (e.g. tag is re-applied)
        $connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'lead_lists_leads SET manually_removed = 0 WHERE lead_id = ? AND leadlist_id = ?',
            [$contact->getId(), $segment->getId()]
        );
        $this->em->clear();

        // Act (second run): rebuild campaign membership — BUG: contact is not re-added
        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);

        $this->em->clear();

        $campaignLead = $this->em->getRepository(CampaignLead::class)->findOneBy([
            'lead'     => $contact->getId(),
            'campaign' => $campaign->getId(),
        ]);

        Assert::assertNotNull($campaignLead, 'Campaign lead record should exist.');
        Assert::assertFalse(
            $campaignLead->wasManuallyRemoved(),
            'Contact should be re-added to campaign when returning to segment with allowRestart=true.'
        );
        Assert::assertSame(2, $campaignLead->getRotation(), 'Rotation should be incremented to 2 for the second run.');

        // Trigger campaign events for the second rotation
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        $this->em->clear();

        $allEventLogs = $this->em->getRepository(LeadEventLog::class)->findBy([
            'lead'     => $contact->getId(),
            'campaign' => $campaign->getId(),
        ]);

        Assert::assertCount(
            2,
            $allEventLogs,
            'Campaign event should have run twice in total (once per rotation) when the contact returns to the segment.'
        );
    }
}
