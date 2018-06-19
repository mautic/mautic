<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Form\Type\CampaignEventJumpToEventType;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var EventRepository
     */
    protected $eventRepo;

    /**
     * @var EventExecutioner
     */
    protected $eventExecutioner;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IpLookupHelper   $ipLookupHelper
     * @param AuditLogModel    $auditLogModel
     * @param EventModel       $eventModel
     * @param EventExecutioner $eventExecutioner
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel, EventModel $eventModel, EventExecutioner $eventExecutioner)
    {
        $this->ipLookupHelper   = $ipLookupHelper;
        $this->auditLogModel    = $auditLogModel;
        $this->eventRepo        = $eventModel->getRepository();
        $this->eventExecutioner = $eventExecutioner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_POST_SAVE     => [
                ['onCampaignPostSave', 0],
                ['processCampaignEventsAfterSave', 1],
            ],
            CampaignEvents::CAMPAIGN_POST_DELETE   => ['onCampaignDelete', 0],
            CampaignEvents::CAMPAIGN_ON_BUILD      => ['onCampaignBuild', 0],
            CampaignEvents::ON_EVENT_JUMP_TO_EVENT => ['onJumpToEvent', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param Events\CampaignEvent $event
     */
    public function onCampaignPostSave(Events\CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        $details  = $event->getChanges();

        //don't set leads
        unset($details['leads']);

        if (!empty($details)) {
            $log = [
                'bundle'    => 'campaign',
                'object'    => 'campaign',
                'objectId'  => $campaign->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param Events\CampaignEvent $event
     */
    public function onCampaignDelete(Events\CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        $log      = [
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $campaign->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $campaign->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add event triggers and actions.
     *
     * @param Events\CampaignBuilderEvent $event
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event)
    {
        // Add action to jump to another event in the campaign flow.
        $event->addAction('campaign.jump_to_event', [
            'label'                  => 'mautic.campaign.event.jump_to_event',
            'description'            => 'mautic.campaign.event.jump_to_event_descr',
            'formType'               => CampaignEventJumpToEventType::class,
            'template'               => 'MauticCampaignBundle:Event:jump.html.php',
            'batchEventName'         => CampaignEvents::ON_EVENT_JUMP_TO_EVENT,
            'connectionRestrictions' => [
                'target' => [
                    Event::TYPE_DECISION  => ['none'],
                    Event::TYPE_ACTION    => ['none'],
                    Event::TYPE_CONDITION => ['none'],
                ],
            ],
        ]);
    }

    /**
     * Process campaign.jump_to_event actions.
     *
     * @param Events\PendingEvent $campaignEvent
     */
    public function onJumpToEvent(Events\PendingEvent $campaignEvent)
    {
        foreach ($campaignEvent->getPending() as $log) {
            $event      = $log->getEvent();
            $jumpTarget = $this->getJumpTargetForEvent($event, 'e.id');

            if ($event->getType() !== 'campaign.jump_to_event' || $jumpTarget === null) {
                continue;
            }

            $this->eventExecutioner->executeForContacts($jumpTarget, $campaignEvent->getContacts());
        }
    }

    /**
     * Update campaign events.
     *
     * This block specifically handles the campaign.jump_to_event properties
     * to ensure that it has the actual ID and not the temp_id as the
     * target for the jump.
     *
     * @param Events\CampaignEvent $campaignEvent
     */
    public function processCampaignEventsAfterSave(Events\CampaignEvent $campaignEvent)
    {
        $campaign = $campaignEvent->getCampaign();
        $events   = $campaign->getEvents();
        $toSave   = [];

        foreach ($events as $event) {
            if ($event->getType() !== 'campaign.jump_to_event') {
                continue;
            }

            $jumpTarget = $this->getJumpTargetForEvent($event);

            if ($jumpTarget !== null) {
                $event->setProperties(array_merge(
                    $event->getProperties(),
                    [
                        'jumpToTarget' => $jumpTarget->getId(),
                    ]
                ));

                $toSave[] = $event;
            }
        }

        if (count($toSave)) {
            $this->eventRepo->saveEntities($toSave);
        }
    }

    /**
     * Inspect a jump event and get its target.
     *
     * @param Event $event
     * @param mixed $column
     *
     * @return null|Event
     */
    private function getJumpTargetForEvent(Event $event, $column = 'e.tempId')
    {
        $properties  = $event->getProperties();
        $jumpToEvent = $this->eventRepo->getEntities([
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => $column,
                        'value'  => $properties['jumpToEvent'],
                        'expr'   => 'eq',
                    ],
                    [
                        'column' => 'e.campaign',
                        'value'  => $event->getCampaign(),
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);

        if (count($jumpToEvent)) {
            return $jumpToEvent[0];
        }

        return null;
    }
}
