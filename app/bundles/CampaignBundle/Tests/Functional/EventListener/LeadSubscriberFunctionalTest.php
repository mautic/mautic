<?php

declare(strict_types=1);

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventListener\LeadSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadMergeEvent;

final class LeadSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public function testCampaignLeadMerge(): void
    {
        $victor = new Lead();
        $loser  = new Lead();
        $this->em->persist($victor);
        $this->em->persist($loser);

        $campaign = new Campaign();
        $campaign->setName('Campaign');

        $this->em->persist($campaign);
        $this->em->flush();

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($victor);
        $campaignLead->setDateAdded(new DateTime());

        $this->em->persist($campaignLead);

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($loser);
        $campaignLead->setDateAdded(new DateTime());

        $this->em->persist($campaignLead);

        $event = new Event();
        $event->setName('Event');
        $event->setType('email.send');
        $event->setCampaign($campaign);
        $event->setEventType('action');
        $event->setProperties([]);

        $this->em->persist($event);

        $eventLog = new LeadEventLog();
        $eventLog->setLead($victor);
        $eventLog->setCampaign($campaign);
        $eventLog->setEvent($event);
        $this->em->persist($eventLog);

        $eventLog = new LeadEventLog();
        $eventLog->setLead($loser);
        $eventLog->setCampaign($campaign);
        $eventLog->setEvent($event);
        $this->em->persist($eventLog);

        $this->em->flush();

        /** @var CampaignModel $leadModel */
        $leadModel = self::getContainer()->get(CampaignModel::class);

        $this->assertCount(2, $leadModel->getCampaignLeads($campaign));

        $leadMergeEvent = new LeadMergeEvent($victor, $loser);
        /** @var LeadSubscriber $subscriber */
        $subscriber = self::getContainer()->get(LeadSubscriber::class);

        $subscriber->onLeadMerge($leadMergeEvent);

        $this->assertCount(1, $leadModel->getCampaignLeads($campaign));
    }
}
