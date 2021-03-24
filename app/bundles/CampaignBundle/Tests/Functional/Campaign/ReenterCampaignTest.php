<?php

declare(strict_types=1);

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLeads;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignLeadsRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

class ReenterCampaignTest extends MauticMysqlTestCase
{
    public function testContactReenteringCampaign(): void
    {
        /** @var LeadEventLogRepository $leadEventLogRepo */
        $leadEventLogRepo = $this->em->getRepository(LeadEventLog::class);

        /** @var CampaignRepository $campaignRepo */
        $campaignRepo = $this->em->getRepository(Campaign::class);

        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->em->getRepository(Lead::class);

        /** @var CampaignLeadsRepository $campaignLeadsRepo */
        $campaignLeadsRepo = $this->em->getRepository(CampaignLeads::class);

        $contactA = new Lead();

        $contactRepo->saveEntity($contactA);

        $campaign = new Campaign();
        $campaign->setName('Campaign ABC');
        $campaign->setCreatedBy(1);

        $eventA = new Event();
        $eventA->setName('Event A');
        $eventA->setType('lead.removednc');
        $eventA->setEventType('action');
        $eventA->setCampaign($campaign);

        $eventB = new Event();
        $eventB->setName('Event B');
        $eventB->setType('lead.removednc');
        $eventB->setEventType('action');
        $eventB->setCampaign($campaign);
        $eventB->setParent($eventA);
        $eventB->setTriggerInterval(1);
        $eventB->setTriggerIntervalUnit('h');
        $eventB->setTriggerMode('interval');
        $eventA->addChild($eventB);

        $campaign->addEvent(0, $eventA);
        $campaign->addEvent(1, $eventB);

        $campaignRepo->saveEntity($campaign);

        $campaignLeadsA = new CampaignLeads();
        $campaignLeadsA->setLead($contactA);
        $campaignLeadsA->setCampaign($campaign);
        $campaignLeadsA->setDateAdded(new \DateTime('2020-11-21'));
        $campaignLeadsA->setRotation(0);
        $campaignLeadsA->setManuallyRemoved(false);

        $campaignLeadsRepo->saveEntity($campaignLeadsA);

        $output = $this->runCommand('mautic:campaigns:trigger', []);
        echo "trigger output:\n$output\n";

        $campaignLeadsB = new CampaignLeads();
        $campaignLeadsB->setLead($contactA);
        $campaignLeadsB->setCampaign($campaign);
        $campaignLeadsB->setDateAdded(new \DateTime('2020-11-21'));
        $campaignLeadsB->setRotation(1);
        $campaignLeadsB->setManuallyRemoved(false);

        $campaignLeadsRepo->saveEntity($campaignLeadsB);
    }
}
