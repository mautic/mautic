<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\LeadBundle\Entity\Lead;

final class FixtureHelper
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);

        $this->em->persist($contact);

        return $contact;
    }

    public function addContactToCampaign(Lead $contact, Campaign $campaign): CampaignLead
    {
        $ref = new CampaignLead();
        $ref->setCampaign($campaign);
        $ref->setLead($contact);
        $ref->setDateAdded(new \DateTime());

        $this->em->persist($ref);

        return $ref;
    }

    public function createCampaign(string $name): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name);
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);

        return $campaign;
    }

    public function createCampaignWithScheduledEvent(Campaign $campaign, int $interval = 1, string $intervalUnit = 'd', \DateTimeInterface $hour = null): Event
    {
        if (!$campaign->getId()) {
            $this->em->flush();
        }

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Adjust contact points');
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setTriggerInterval($interval);
        $event->setTriggerIntervalUnit($intervalUnit);
        $event->setTriggerMode('interval');
        if ($hour) {
            $event->setTriggerHour($hour->format('H:i'));
        }
        $event->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '1080',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'interval',
                'triggerDate'                => null,
                'triggerInterval'            => $interval,
                'triggerIntervalUnit'        => $intervalUnit,
                'triggerHour'                => $hour,
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => ['points' => '5'],
                'type'                       => 'lead.changepoints',
                'eventType'                  => 'action',
                'anchorEventType'            => 'source',
                'campaignId'                 => $campaign->getId(),
                'buttons'                    => ['save' => ''],
                'points'                     => 5,
            ]
        );

        $this->em->persist($event);
        $this->em->flush();

        $campaign->addEvent(0, $event);
        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => $event->getId(),
                        'positionX' => '1080',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '1180',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        return $event;
    }
}
