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
    protected function saveSomeCampaignLeadEventLogs(bool $withPendingAction = false, bool $withActionOfRemovedLead = false): Campaign
    {
        $relativeDate = date('Y-m-d', strtotime('-1 month'));

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
        $leadEventLogA->setDateTriggered(new \DateTime($relativeDate.' 16:34:00', new \DateTimeZone('UTC')));
        $leadEventLogA->setRotation(0);

        $leadEventLogB = new LeadEventLog();
        $leadEventLogB->setCampaign($campaign);
        $leadEventLogB->setEvent($eventA);
        $leadEventLogB->setLead($contactB);
        $leadEventLogB->setDateTriggered(new \DateTime($relativeDate.' 16:54:00', new \DateTimeZone('UTC')));
        $leadEventLogB->setRotation(0);

        $leadEventLogC = new LeadEventLog();
        $leadEventLogC->setCampaign($campaign);
        $leadEventLogC->setEvent($eventB);
        $leadEventLogC->setLead($contactA);
        $leadEventLogC->setDateTriggered(new \DateTime($relativeDate.' 16:55:00', new \DateTimeZone('UTC')));
        $leadEventLogC->setRotation(0);

        $leadEventLogD = new LeadEventLog();
        $leadEventLogD->setCampaign($campaign);
        $leadEventLogD->setEvent($eventB);
        $leadEventLogD->setLead($contactB);
        $leadEventLogD->setDateTriggered(new \DateTime($relativeDate.' 17:04:00', new \DateTimeZone('UTC')));
        $leadEventLogD->setRotation(0);

        $leadEventLogRepo->saveEntities([$leadEventLogA, $leadEventLogB, $leadEventLogC, $leadEventLogD]);

        $campaignLeadsA = new CampaignLeads();
        $campaignLeadsA->setLead($contactA);
        $campaignLeadsA->setCampaign($campaign);
        $campaignLeadsA->setDateAdded(new \DateTime($relativeDate));
        $campaignLeadsA->setRotation(0);
        $campaignLeadsA->setManuallyRemoved(false);

        $campaignLeadsB = new CampaignLeads();
        $campaignLeadsB->setLead($contactB);
        $campaignLeadsB->setCampaign($campaign);
        $campaignLeadsB->setDateAdded(new \DateTime($relativeDate));
        $campaignLeadsB->setRotation(0);
        $campaignLeadsB->setManuallyRemoved(false);

        $campaignLeadsRepo->saveEntities([$campaignLeadsA, $campaignLeadsB]);

        if ($withPendingAction) {
            $contactC = new Lead();
            $contactRepo->saveEntity($contactC);

            $leadEventLogE = new LeadEventLog();
            $leadEventLogE->setCampaign($campaign);
            $leadEventLogE->setEvent($eventA);
            $leadEventLogE->setLead($contactC);
            $leadEventLogE->setDateTriggered(new \DateTime($relativeDate.' 16:34:00', new \DateTimeZone('UTC')));
            $leadEventLogE->setRotation(0);
            $leadEventLogRepo->saveEntity($leadEventLogE);

            $leadEventLogF = new LeadEventLog();
            $leadEventLogF->setCampaign($campaign);
            $leadEventLogF->setEvent($eventB);
            $leadEventLogF->setLead($contactC);
            $leadEventLogF->setDateTriggered(new \DateTime($relativeDate.' 16:34:00', new \DateTimeZone('UTC')));
            $leadEventLogF->setTriggerDate(new \DateTime($relativeDate.' 16:49:00', new \DateTimeZone('UTC')));
            $leadEventLogF->setIsScheduled(true);
            $leadEventLogF->setRotation(0);
            $leadEventLogRepo->saveEntity($leadEventLogF);

            $campaignLeadsC = new CampaignLeads();
            $campaignLeadsC->setLead($contactC);
            $campaignLeadsC->setCampaign($campaign);
            $campaignLeadsC->setDateAdded(new \DateTime($relativeDate));
            $campaignLeadsC->setRotation(0);
            $campaignLeadsC->setManuallyRemoved(false);
            $campaignLeadsRepo->saveEntity($campaignLeadsC);
        }

        if ($withActionOfRemovedLead) {
            $contactD = new Lead();
            $contactRepo->saveEntity($contactD);

            $leadEventLogG = new LeadEventLog();
            $leadEventLogG->setCampaign($campaign);
            $leadEventLogG->setEvent($eventA);
            $leadEventLogG->setLead($contactD);
            $leadEventLogG->setDateTriggered(new \DateTime($relativeDate.' 16:34:00', new \DateTimeZone('UTC')));
            $leadEventLogG->setRotation(0);
            $leadEventLogRepo->saveEntity($leadEventLogG);

            $campaignLeadsD = new CampaignLeads();
            $campaignLeadsD->setLead($contactD);
            $campaignLeadsD->setCampaign($campaign);
            $campaignLeadsD->setDateAdded(new \DateTime($relativeDate));
            $campaignLeadsD->setRotation(0);
            $campaignLeadsD->setManuallyRemoved(true);
            $campaignLeadsRepo->saveEntity($campaignLeadsD);
        }

        return $campaign;
    }
}
