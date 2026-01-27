<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
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

    public function createCampaignWithScheduledEvent(Campaign $campaign, int $interval = 1, string $intervalUnit = 'd', ?\DateTimeInterface $hour = null): Event
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

    /**
     * Creates campaign with email sent action.
     *
     * Campaign diagram:
     * -------------------
     * -  Start segment  -
     * -------------------
     *         |
     * -------------------
     * -   Send email    -
     * -------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCampaignWithEmailSent(int $emailId): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test send email');

        $this->em->persist($campaign);
        $this->em->flush();

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Send email');
        $event1->setType('email.send');
        $event1->setChannel('email');
        $event1->setChannelId($emailId);
        $event1->setEventType('action');
        $event1->setTriggerMode('immediate');
        $event1->setOrder(1);
        $event1->setProperties(
            [
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
                    'email'      => $emailId,
                    'email_type' => 'transactional',
                    'priority'   => '2',
                    'attempts'   => '3',
                ],
                'type'            => 'email.send',
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'email'      => $emailId,
                'email_type' => 'transactional',
                'priority'   => 2,
                'attempts'   => 3.0,
            ]
        );
        $this->em->persist($event1);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event1->getId(),
                        'positionX' => '549',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );
        $campaign->addEvent($event1->getId(), $event1);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * Creates a campaign with conditional email sent action.
     *
     * Campaign diagram:
     * -------------------
     *    Start segment
     * -------------------
     *          |
     * -------------------
     *    Has x points? (default x=1)
     * -------------------
     *         | Yes
     * -------------------
     *     Send email
     * -------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCampaignWithConditionalEmail(int $emailId, int $points = 1, bool $allowRestart = false): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test conditional email');
        $campaign->setAllowRestart($allowRestart);

        $this->em->persist($campaign);
        $this->em->flush();

        // Create point condition event
        $eventCondition = new Event();
        $eventCondition->setCampaign($campaign);
        $eventCondition->setName('Check if the contact has points');
        $eventCondition->setType('lead.points');
        $eventCondition->setEventType('condition');
        $eventCondition->setTriggerMode('immediate');
        $eventCondition->setOrder(1);
        $eventCondition->setProperties([
            'canvasSettings' => [
                'droppedX' => '549',
                'droppedY' => '155',
            ],
            'name'                       => 'Lead points',
            'triggerMode'                => 'immediate',
            'triggerDate'                => null,
            'triggerInterval'            => '1',
            'triggerIntervalUnit'        => 'd',
            'triggerHour'                => '',
            'triggerRestrictedStartHour' => '',
            'triggerRestrictedStopHour'  => '',
            'order'                      => 1,
            'anchor'                     => 'leadsource',
            'properties'                 => [
                'operator' => 'gte',
                'score'    => $points,
            ],
            'type'            => 'lead.points',
            'eventType'       => 'condition',
            'anchorEventType' => 'source',
            'operator'        => 'gte',
            'score'           => $points,
        ]);

        $this->em->persist($eventCondition);
        $this->em->flush();

        // Create email send event
        $eventEmail = new Event();
        $eventEmail->setCampaign($campaign);
        $eventEmail->setName('Send email');
        $eventEmail->setType('email.send');
        $eventEmail->setChannel('email');
        $eventEmail->setChannelId($emailId);
        $eventEmail->setEventType('action');
        $eventEmail->setTriggerMode('immediate');
        $eventEmail->setOrder(2);
        $eventEmail->setDecisionPath('yes');
        $eventEmail->setParent($eventCondition);
        $eventEmail->setProperties([
            'canvasSettings' => [
                'droppedX' => '549',
                'droppedY' => '300',
            ],
            'name'                       => '',
            'triggerMode'                => 'immediate',
            'triggerDate'                => null,
            'triggerInterval'            => '1',
            'triggerIntervalUnit'        => 'd',
            'triggerHour'                => '',
            'triggerRestrictedStartHour' => '',
            'triggerRestrictedStopHour'  => '',
            'anchor'                     => 'yes',
            'properties'                 => [
                'email'      => $emailId,
                'email_type' => 'transactional',
                'priority'   => '2',
                'attempts'   => '3',
            ],
            'type'            => 'email.send',
            'eventType'       => 'action',
            'anchorEventType' => 'condition',
            'email'           => $emailId,
            'email_type'      => 'transactional',
            'priority'        => 2,
            'attempts'        => 3.0,
        ]);
        $this->em->persist($eventEmail);
        $this->em->flush();

        // Set up canvas with connections
        $campaign->setCanvasSettings([
            'nodes' => [
                [
                    'id'        => $eventCondition->getId(),
                    'positionX' => '549',
                    'positionY' => '155',
                ],
                [
                    'id'        => $eventEmail->getId(),
                    'positionX' => '549',
                    'positionY' => '300',
                ],
                [
                    'id'        => 'lists',
                    'positionX' => '596',
                    'positionY' => '50',
                ],
            ],
            'connections' => [
                [
                    'sourceId' => 'lists',
                    'targetId' => $eventCondition->getId(),
                    'anchors'  => [
                        'source' => 'leadsource',
                        'target' => 'top',
                    ],
                ],
                [
                    'sourceId' => $eventCondition->getId(),
                    'targetId' => $eventEmail->getId(),
                    'anchors'  => [
                        'source' => 'yes',
                        'target' => 'top',
                    ],
                ],
            ],
        ]);

        $campaign->addEvent($eventCondition->getId(), $eventCondition);
        $campaign->addEvent($eventEmail->getId(), $eventEmail);
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /** @return array<int, array<string, mixed>> */
    public static function getPayload(): array
    {
        $fileContents = file_get_contents(__DIR__.'/entity_data.json');

        return json_decode($fileContents, true);
    }
}
