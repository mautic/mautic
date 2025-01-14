<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Campaign;

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

abstract class AbstractCampaignTest extends MauticMysqlTestCase
{
    protected function saveSomeCampaignLeadEventLogs(bool $emulatePendingCount = false): Campaign
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
        $contactB = new Lead();

        $contactRepo->saveEntities([$contactA, $contactB]);

        $campaign = new Campaign();
        $campaign->setName('Campaign ABC');
        $campaign->setCreatedBy(1);

        $eventA = new Event();
        $eventA->setName('Event A');
        $eventA->setType('type.a');
        $eventA->setEventType('action');
        $eventA->setCampaign($campaign);

        $eventB = new Event();
        $eventB->setName('Event B');
        $eventB->setType('type.b');
        $eventB->setEventType('action');
        $eventB->setCampaign($campaign);

        $campaign->addEvent(0, $eventA);
        $campaign->addEvent(1, $eventB);

        $campaignRepo->saveEntity($campaign);

        $leadEventLogA = new LeadEventLog();
        $leadEventLogA->setCampaign($campaign);
        $leadEventLogA->setEvent($eventA);
        $leadEventLogA->setLead($contactA);
        $leadEventLogA->setDateTriggered(new \DateTime('2020-11-21 16:34:00', new \DateTimeZone('UTC')));
        $leadEventLogA->setRotation(0);

        $leadEventLogB = new LeadEventLog();
        $leadEventLogB->setCampaign($campaign);
        $leadEventLogB->setEvent($eventA);
        $leadEventLogB->setLead($contactB);
        $leadEventLogB->setDateTriggered(new \DateTime('2020-11-21 16:54:00', new \DateTimeZone('UTC')));
        $leadEventLogB->setRotation(0);

        $leadEventLogC = new LeadEventLog();
        $leadEventLogC->setCampaign($campaign);
        $leadEventLogC->setEvent($eventB);
        $leadEventLogC->setLead($contactA);
        $leadEventLogC->setDateTriggered(new \DateTime('2020-11-21 16:55:00', new \DateTimeZone('UTC')));
        $leadEventLogC->setRotation(0);

        $leadEventLogD = new LeadEventLog();
        $leadEventLogD->setCampaign($campaign);
        $leadEventLogD->setEvent($eventB);
        $leadEventLogD->setLead($contactB);
        $leadEventLogD->setDateTriggered(new \DateTime('2020-11-21 17:04:00', new \DateTimeZone('UTC')));
        $leadEventLogD->setRotation(0);

        $leadEventLogRepo->saveEntities([$leadEventLogA, $leadEventLogB, $leadEventLogC, $leadEventLogD]);

        $campaignLeadsA = new CampaignLeads();
        $campaignLeadsA->setLead($contactA);
        $campaignLeadsA->setCampaign($campaign);
        $campaignLeadsA->setDateAdded(new \DateTime('2020-11-21'));
        $campaignLeadsA->setRotation(0);
        $campaignLeadsA->setManuallyRemoved(false);

        $campaignLeadsB = new CampaignLeads();
        $campaignLeadsB->setLead($contactB);
        $campaignLeadsB->setCampaign($campaign);
        $campaignLeadsB->setDateAdded(new \DateTime('2020-11-21'));
        $campaignLeadsB->setRotation(0);
        $campaignLeadsB->setManuallyRemoved(false);

        $campaignLeadsRepo->saveEntities([$campaignLeadsA, $campaignLeadsB]);

        if ($emulatePendingCount) {
            $contactC = new Lead();
            $contactRepo->saveEntity($contactC);

            $leadEventLogD = new LeadEventLog();
            $leadEventLogD->setCampaign($campaign);
            $leadEventLogD->setEvent($eventA);
            $leadEventLogD->setLead($contactC);
            $leadEventLogD->setDateTriggered(new \DateTime('2020-11-21 16:34:00', new \DateTimeZone('UTC')));
            $leadEventLogD->setRotation(0);
            $leadEventLogRepo->saveEntity($leadEventLogD);

            $campaignLeadsC = new CampaignLeads();
            $campaignLeadsC->setLead($contactC);
            $campaignLeadsC->setCampaign($campaign);
            $campaignLeadsC->setDateAdded(new \DateTime('2020-11-21'));
            $campaignLeadsC->setRotation(0);
            $campaignLeadsC->setManuallyRemoved(true);
            $campaignLeadsRepo->saveEntity($campaignLeadsC);
        }

        return $campaign;
    }
}
