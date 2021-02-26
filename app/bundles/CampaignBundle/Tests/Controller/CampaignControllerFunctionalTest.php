<?php

declare(strict_types=1);

/*
 * @copyright 2021 Mautic Contributors. All rights reserved
 * @author Mautic
 *
 * @link http://mautic.org
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\AllydeBundle\Entity\Job;
use Mautic\CampaignBundle\Command\CampaignDeleteEventLogsCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp()
    {
        $this->configParams['delete_campaign_event_log_in_background'] = false;
        parent::setUp();
    }

    public function testDeleteCampaign(): void
    {
        $lead              = $this->createLead();
        $campaign          = $this->createCampaign();
        $event             = $this->createEvent('Event 1', $campaign);
        $this->createEventLog($lead, $event, $campaign);

        $this->client->request(Request::METHOD_POST, '/s/campaigns/delete/'.$campaign->getId());

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $eventLogs = $this->em->getRepository(LeadEventLog::class)->findAll();
        Assert::assertCount(0, $eventLogs);

        $jobs    = $this->em->getRepository(Job::class)->findBy(['task' => 'delete_event_logs'], ['id' => 'DESC']);
        Assert::assertCount(0, $jobs);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(string $name, Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType('email.send');
        $event->setEventType('action');
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createEventLog(Lead $lead, Event $event, Campaign $campaign): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setCampaign($campaign);
        $this->em->persist($leadEventLog);
        $this->em->flush();

        return $leadEventLog;
    }
}
