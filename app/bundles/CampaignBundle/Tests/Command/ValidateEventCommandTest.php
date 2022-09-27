<?php

namespace Mautic\CampaignBundle\Tests\Command;

class ValidateEventCommandTest extends AbstractCampaignCommand
{
    public function testEventsAreExecutedForInactiveEventWithSingleContact()
    {
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-id' => 1]);

        // Wait 6 seconds then execute the campaign again to send scheduled events
        sleep(6);
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-id' => 1]);

        // No open email decisions should be recorded yet
        $byEvent = $this->getCampaignEventLogs([3]);
        $this->assertCount(0, $byEvent[3]);

        // Wait 6 seconds to go beyond the inaction timeframe
        sleep(6);

        // Now they should be inactive
        $this->runCommand('mautic:campaigns:validate', ['--decision-id' => 3, '--contact-id' => 1]);

        $byEvent = $this->getCampaignEventLogs([3, 7, 10]);
        $this->assertCount(1, $byEvent[3]); // decision recorded
        $this->assertCount(1, $byEvent[7]); // inactive event executed
        $this->assertCount(0, $byEvent[10]); // the positive path should be 0
    }

    public function testEventsAreExecutedForInactiveEventWithMultipleContact()
    {
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3']);

        // Wait 6 seconds then execute the campaign again to send scheduled events
        sleep(6);
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3']);

        // No open email decisions should be recorded yet
        $byEvent = $this->getCampaignEventLogs([3]);
        $this->assertCount(0, $byEvent[3]);

        // Wait 6 seconds to go beyond the inaction timeframe
        sleep(6);

        // Now they should be inactive
        $this->runCommand('mautic:campaigns:validate', ['--decision-id' => 3, '--contact-ids' => '1,2,3']);

        $byEvent = $this->getCampaignEventLogs([3, 7, 10]);
        $this->assertCount(3, $byEvent[3]); // decision recorded
        $this->assertCount(3, $byEvent[7]); // inactive event executed
        $this->assertCount(0, $byEvent[10]); // the positive path should be 0
    }

    public function testContactsRemovedFromTheCampaignAreNotExecutedForInactiveEvents()
    {
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3']);

        // Wait 6 seconds then execute the campaign again to send scheduled events
        sleep(6);
        $this->runCommand('mautic:campaigns:trigger', ['-i' => 1, '--contact-ids' => '1,2,3']);

        // No open email decisions should be recorded yet
        $byEvent = $this->getCampaignEventLogs([3]);
        $this->assertCount(0, $byEvent[3]);

        // Wait 6 seconds to go beyond the inaction timeframe
        sleep(6);

        // Remove a contact from the campaign
        $this->db->createQueryBuilder()->update(MAUTIC_TABLE_PREFIX.'campaign_leads')
            ->set('manually_removed', 1)
            ->where('lead_id = 1')
            ->execute();

        // Now they should be inactive
        $this->runCommand('mautic:campaigns:validate', ['--decision-id' => 3, '--contact-ids' => '1,2,3']);

        // Only two contacts should have been considered inactive because one was marked as manually removed
        $byEvent = $this->getCampaignEventLogs([3, 7, 10]);
        $this->assertCount(2, $byEvent[3]); // decision recorded
        $this->assertCount(2, $byEvent[7]); // inactive event executed
        $this->assertCount(0, $byEvent[10]); // the positive path should be 0
    }
}
