<?php

namespace Mautic\CampaignBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractCommonModel<LeadEventLog>
 */
class EventLogModel extends AbstractCommonModel
{
    public function __construct(
        protected EventModel $eventModel,
        protected CampaignModel $campaignModel,
        protected IpLookupHelper $ipLookupHelper,
        protected EventScheduler $eventScheduler,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): LeadEventLogRepository
    {
        return $this->em->getRepository(LeadEventLog::class);
    }

    public function getPermissionBase(): string
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
     * @return string|mixed[]
     */
    public function updateContactEvent(Event $event, Lead $contact, array $parameters): string|array
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

    public function saveEntity(LeadEventLog $entity): void
    {
        $triggerDate = $entity->getTriggerDate();
        if (null === $triggerDate) {
            // Reschedule for now
            $triggerDate = new \DateTime();
        }

        $this->eventScheduler->reschedule($entity, $triggerDate);
    }
}
