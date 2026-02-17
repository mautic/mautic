<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class EventRepositoryFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<string, array{?\DateTime, ?\DateTime, int}>
     */
    public function dataGetContactPendingEventsConsidersCampaignPublishUpAndDown(): iterable
    {
        yield 'Publish Up and Down not set' => [null, null, 1];
        yield 'Publish Up and Down set' => [new \DateTime('-1 day'), new \DateTime('+1 day'), 1];
        yield 'Publish Up and Down set with Publish Up in the future' => [new \DateTime('+1 day'), new \DateTime('+2 day'), 0];
        yield 'Publish Up and Down set with Publish Down in the past' => [new \DateTime('-2 day'), new \DateTime('-1 day'), 0];
        yield 'Publish Up in the past' => [new \DateTime('-1 day'), null, 1];
        yield 'Publish Up in the future' => [new \DateTime('+1 day'), null, 0];
        yield 'Publish Down in the past' => [null, new \DateTime('-1 day'), 0];
        yield 'Publish Down in the future' => [null, new \DateTime('+1 day'), 1];
    }

    /**
     * @dataProvider dataGetContactPendingEventsConsidersCampaignPublishUpAndDown
     */
    public function testGetContactPendingEventsConsidersCampaignPublishUpAndDown(?\DateTime $publishUp, ?\DateTime $publishDown, int $expectedCount): void
    {
        $repository = static::getContainer()->get('mautic.campaign.repository.event');
        \assert($repository instanceof EventRepository);

        $campaign = $this->createCampaign();
        $event    = $this->createEvent($campaign);
        $lead     = $this->createLead();
        $this->createCampaignMember($lead, $campaign);

        $campaign->setPublishUp($publishUp);
        $campaign->setPublishDown($publishDown);
        $this->em->persist($campaign);
        $this->em->flush();

        Assert::assertCount($expectedCount, $repository->getContactPendingEvents($lead->getId(), $event->getType()));
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
        $campaign->setName('Test');
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName('test');
        $event->setCampaign($campaign);
        $event->setType('test.type');
        $event->setEventType('action');
        $this->em->persist($event);

        return $event;
    }

    private function createCampaignMember(Lead $lead, Campaign $campaign): void
    {
        $member = new CampaignMember();
        $member->setLead($lead);
        $member->setCampaign($campaign);
        $member->setDateAdded(new \DateTime());
        $this->em->persist($member);
    }
}
