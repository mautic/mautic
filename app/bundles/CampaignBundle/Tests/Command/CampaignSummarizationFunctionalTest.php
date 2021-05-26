<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class CampaignSummarizationFunctionalTest extends AbstractCampaignCommand
{
    protected function setUp(): void
    {
        $this->configParams['campaign_use_summary'] = 'testExecuteCampaignEventWithSummarization' === $this->getName();
        parent::setUp();
    }

    public function testExecuteCampaignEventWithoutSummarization(): void
    {
        $this->createDataAndExecuteCommand();
        $campaignSummary = $this->em->getRepository(Summary::class)->findAll();
        Assert::assertCount(0, $campaignSummary);
    }

    public function testExecuteCampaignEventWithSummarization(): void
    {
        $this->createDataAndExecuteCommand();
        $campaignSummary = $this->em->getRepository(Summary::class)->findAll();
        Assert::assertCount(1, $campaignSummary);
    }

    private function createDataAndExecuteCommand(): void
    {
        $lead              = $this->createLead('Test');
        $campaign          = $this->createCampaign('My campaign');
        $event             = $this->createEvent('Event 1', $campaign, 'email.send', 'action');
        $this->createCampaignLead($campaign, $lead);
        $this->createEventLog($lead, $event, $campaign);
        $this->em->flush();
        $this->em->clear();

        $this->runCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId(), '--kickoff-only' => true]);
    }
}
