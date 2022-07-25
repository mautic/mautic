<?php

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Lead;

class EventLogModel extends AbstractCommonModel
{
    protected EventModel $eventModel;

    protected CampaignModel $campaignModel;

    protected IpLookupHelper $ipLookupHelper;

    protected EventScheduler $eventScheduler;

    public function __construct(
        EventModel $eventModel,
        CampaignModel $campaignModel,
        IpLookupHelper $ipLookupHelper,
        EventScheduler $eventScheduler
    ) {
        $this->eventModel     = $eventModel;
        $this->campaignModel  = $campaignModel;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->eventScheduler = $eventScheduler;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\LeadEventLogRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'campaign:campaigns';
    }

    public function getEntities(array $args = [])
    {
        /** @var LeadEventLog[] $logs */
        $logs = parent::getEntities($args);

        if (!empty($args['campaign_id']) && !empty($args['contact_id'])) {
            /** @var Event[] $events */
            $events = $this->eventModel->getEntities(
                [
                    'campaign_id'      => $args['campaign_id'],
                    'ignore_children'  => true,
                    'index_by'         => 'id',
                    'ignore_paginator' => true,
                ]
            );

            foreach ($logs as $log) {
                $event = $log->getEvent()->getId();
                $events[$event]->addContactLog($log);
            }

            return array_values($events);
        }

        return $logs;
    }

    /**
     * @return string|LeadEventLog
     */
    public function updateContactEvent(Event $event, Lead $contact, array $parameters)
    {
        $campaign = $event->getCampaign();

        // Check that contact is part of the campaign
        $membership = $campaign->getContactMembership($contact);
        if (0 === count($membership)) {
            return $this->translator->trans(
                'mautic.campaign.error.contact_not_in_campaign',
                ['%campaign%' => $campaign->getId(), '%contact%' => $contact->getId()],
                'flashes'
            );
        }

        /** @var \Mautic\CampaignBundle\Entity\Lead $m */
        foreach ($membership as $m) {
            if ($m->getManuallyRemoved()) {
                return $this->translator->trans(
                    'mautic.campaign.error.contact_not_in_campaign',
                    ['%campaign%' => $campaign->getId(), '%contact%' => $contact->getId()],
                    'flashes'
                );
            }
        }

        // Check that contact has not executed the event already
        $logs    = $event->getContactLog($contact);
        $created = false;
        if (count($logs)) {
            $log = $logs[0];
            if ($log->getDateTriggered()) {
                return $this->translator->trans(
                    'mautic.campaign.error.event_already_executed',
                    [
                        '%campaign%'      => $campaign->getId(),
                        '%event%'         => $event->getId(),
                        '%contact%'       => $contact->getId(),
                        '%dateTriggered%' => $log->getDateTriggered()->format(\DateTimeInterface::ATOM),
                    ],
                    'flashes'
                );
            }
        } else {
            if (!isset($parameters['triggerDate']) && !isset($parameters['dateTriggered'])) {
                return $this->translator->trans(
                    'mautic.campaign.error.event_must_be_scheduled',
                    [
                        '%campaign%' => $campaign->getId(),
                        '%event%'    => $event->getId(),
                        '%contact%'  => $contact->getId(),
                    ],
                    'flashes'
                );
            }

            $log = (new LeadEventLog())
                ->setLead($contact)
                ->setEvent($event);
            $created = true;
        }

        foreach ($parameters as $property => $value) {
            switch ($property) {
                case 'dateTriggered':
                    $log->setDateTriggered(
                        new \DateTime($value)
                    );
                    break;
                case 'triggerDate':
                    if (Event::TYPE_DECISION === $event->getEventType()) {
                        return $this->translator->trans(
                            'mautic.campaign.error.decision_cannot_be_scheduled',
                            [
                                '%campaign%' => $campaign->getId(),
                                '%event%'    => $event->getId(),
                                '%contact%'  => $contact->getId(),
                            ],
                            'flashes'
                        );
                    }
                    $log->setTriggerDate(
                        new \DateTime($value)
                    );
                    break;
                case 'ipAddress':
                    if (!defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED')) {
                        $log->setIpAddress(
                            $this->ipLookupHelper->getIpAddress($value)
                        );
                    }
                    break;
                case 'metadata':
                    $metadata = $log->getMetadata();
                    if (is_array($value)) {
                        $newMetadata = $value;
                    } elseif ($jsonDecoded = json_decode($value, true)) {
                        $newMetadata = $jsonDecoded;
                    } else {
                        $newMetadata = (array) $value;
                    }

                    $newMetadata = InputHelper::cleanArray($newMetadata);
                    $log->setMetadata(array_merge($metadata, $newMetadata));
                    break;
                case 'nonActionPathTaken':
                    $log->setNonActionPathTaken((bool) $value);
                    break;
                case 'channel':
                    $log->setChannel(InputHelper::clean($value));
                    break;
                case 'channelId':
                    $log->setChannel(intval($value));
                    break;
            }
        }

        $this->saveEntity($log);

        return [$log, $created];
    }

    public function saveEntity(LeadEventLog $entity)
    {
        $triggerDate = $entity->getTriggerDate();
        if (null === $triggerDate) {
            // Reschedule for now
            $triggerDate = new \DateTime();
        }

        $this->eventScheduler->reschedule($entity, $triggerDate);
    }
}
