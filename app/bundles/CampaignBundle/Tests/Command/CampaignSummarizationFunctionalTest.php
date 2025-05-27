<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class CampaignSummarizationFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['campaign_use_summary'] = 'testExecuteCampaignEventWithSummarization' === $this->name();
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
        $lead              = $this->createLead();
        $campaign          = $this->createCampaign();
        $email             = $this->createEmail('Email 1');
        $properties        = [
            'canvasSettings' => [
                'droppedX' => '549',
                'droppedY' => '155',
            ],
            'name'                       => '',
            'triggerMode'                => 'immediate',
            'triggerDate'                => null,
            'triggerInterval'            => '1',
            'triggerIntervalUnit'        => 'd',
            'triggerHour'                => '',
            'triggerRestrictedStartHour' => '',
            'triggerRestrictedStopHour'  => '',
            'anchor'                     => 'leadsource',
            'properties'                 => [
                'email'      => $email->getId(),
                'email_type' => 'transactional',
                'priority'   => '2',
                'attempts'   => '3',
            ],
            'type'            => 'email.send',
            'eventType'       => 'action',
            'anchorEventType' => 'source',
            'buttons'         => [
                'save' => '',
            ],
            'email'      => $email->getId(),
            'email_type' => 'transactional',
            'priority'   => 2,
            'attempts'   => 3.0,
        ];
        $event             = $this->createEvent('Event 1', $campaign, $properties);
        $this->createCampaignLead($campaign, $lead);
        $this->createEventLog($lead, $event, $campaign);
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId(), '--kickoff-only' => true]);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createCampaignLead(Campaign $campaign, Lead $lead): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);

        return $campaignLead;
    }

    /**
     * @param mixed[] $properties
     */
    private function createEvent(string $name, Campaign $campaign, array $properties = []): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType('email.send');
        $event->setProperties($properties);
        $event->setEventType('action');
        $event->setTriggerInterval(1);
        $event->setTriggerMode('immediate');
        $this->em->persist($event);

        return $event;
    }

    private function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject('Test Subject');
        $email->setIsPublished(true);

        $this->em->persist($email);

        return $email;
    }

    private function createEventLog(Lead $lead, Event $event, Campaign $campaign): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setCampaign($campaign);
        $leadEventLog->setRotation(0);
        $this->em->persist($leadEventLog);

        return $leadEventLog;
    }
}
