<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use PHPUnit\Framework\Assert;

final class CampaignActionDNCSubscriberTest extends MauticMysqlTestCase
{
    public function testDncActions(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $this->em->persist($contact);
        $this->em->flush();

        $campaign = $this->createCampaign();
        $this->addContactToCampaign($contact, $campaign);

        $this->em->clear();

        $commandTester = $this->testSymfonyCommand(
            'mautic:campaigns:trigger',
            ['--campaign-id' => $campaign->getId()]
        );

        Assert::assertSame(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        // The DNC record was added by the campaign but also removed right after. So there should be none.
        Assert::assertNull($this->em->getRepository(DoNotContact::class)->findOneBy(['lead' => $contact]));

        $logs = $this->em->getRepository(LeadEventLog::class)->findBy(['object' => 'dnc'], ['id' => 'ASC']);
        Assert::assertCount(2, $logs);

        Assert::assertSame('unsubscribed', $logs[0]->getAction());
        Assert::assertSame('email', $logs[0]->getProperties()['channel']);
        Assert::assertSame(DoNotContact::MANUAL, $logs[0]->getProperties()['reason']);
        Assert::assertSame('Test reason', $logs[0]->getProperties()['comments']);

        Assert::assertSame('subscribed', $logs[1]->getAction());
        Assert::assertSame('email', $logs[1]->getProperties()['channel']);
        Assert::assertSame(DoNotContact::MANUAL, $logs[1]->getProperties()['reason'], 'The reason of the removed DNC is stored.');
        Assert::assertSame('', $logs[1]->getProperties()['comments']);
    }

    private function addContactToCampaign(Lead $contact, Campaign $campaign): void
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($this->em->getReference(Campaign::class, $campaign->getId()));
        $campaignLead->setLead($this->em->getReference(Lead::class, $contact->getId()));
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);
        $campaign->addLead(0, $campaignLead);
        $this->em->flush();
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test');

        $this->em->persist($campaign);
        $this->em->flush();

        $addDncAction    = $this->createEvent($campaign, 'lead.adddnc');
        $removeDncAction = $this->createEvent($campaign, 'lead.removednc');

        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => $removeDncAction->getId(),
                        'positionX' => '848',
                        'positionY' => '139',
                    ],
                    [
                        'id'        => $addDncAction->getId(),
                        'positionX' => '848',
                        'positionY' => '139',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '860',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $addDncAction->getId(),
                        'anchors'  => [
                            [
                                'endpoint' => 'leadsource',
                                'eventId'  => 'lists',
                            ],
                            [
                                'endpoint' => 'top',
                                'eventId'  => $addDncAction->getId(),
                            ],
                            [
                                'endpoint' => $addDncAction->getId(),
                                'eventId'  => $removeDncAction->getId(),
                            ],
                        ],
                    ],
                ],
            ]
        );

        $campaign->addEvent(0, $addDncAction);
        $campaign->addEvent(1, $removeDncAction);

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(Campaign $campaign, string $action): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Remove DNC');
        $event->setType($action);
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $event->setProperties(
            [
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
                    'channels' => ['email'],
                    'reason'   => 'Test reason',
                ],
                'type'            => $action,
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => $campaign->getId(),
                'buttons'         => ['save' => ''],
                'channels'        => ['email'],
                'reason'          => 'Test reason',
            ]
        );

        $this->em->persist($event);

        return $event;
    }
}
