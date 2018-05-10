<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\ChannelTrait;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\Helper\LeadChangeEventDispatcher;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ChannelTimelineInterface;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    use ChannelTrait;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var LeadChangeEventDispatcher
     */
    private $leadEventDispatcher;

    /**
     * LeadSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel, LeadChangeEventDispatcher $eventDispatcher)
    {
        $this->ipLookupHelper      = $ipLookupHelper;
        $this->auditLogModel       = $auditLogModel;
        $this->leadEventDispatcher = $eventDispatcher;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE       => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE     => ['onLeadDelete', 0],
            LeadEvents::LEAD_PRE_MERGE       => ['preLeadMerge', 0],
            LeadEvents::LEAD_POST_MERGE      => ['onLeadMerge', 0],
            LeadEvents::FIELD_POST_SAVE      => ['onFieldPostSave', 0],
            LeadEvents::FIELD_POST_DELETE    => ['onFieldDelete', 0],
            LeadEvents::NOTE_POST_SAVE       => ['onNotePostSave', 0],
            LeadEvents::NOTE_POST_DELETE     => ['onNoteDelete', 0],
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * Add a lead entry to the audit log.
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        //Because there is an event within an event, there is a risk that something will trigger a loop which
        //needs to be prevented
        static $preventLoop = [];

        $lead = $event->getLead();

        if ($details = $event->getChanges()) {
            // Unset dateLastActive and dateModified to prevent un-necessary audit log entries
            unset($details['dateLastActive'], $details['dateModified']);
            if (empty($details)) {
                return;
            }

            $check = base64_encode($lead->getId().md5(json_encode($details)));
            if (!in_array($check, $preventLoop)) {
                $preventLoop[] = $check;

                // Change entry
                $log = [
                    'bundle'    => 'lead',
                    'object'    => 'lead',
                    'objectId'  => $lead->getId(),
                    'action'    => ($event->isNew()) ? 'create' : 'update',
                    'details'   => $details,
                    'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                ];
                $this->auditLogModel->writeToLog($log);

                // Date identified entry
                if (isset($changes['dateIdentified'])) {
                    //log the day lead was identified
                    $log = [
                        'bundle'    => 'lead',
                        'object'    => 'lead',
                        'objectId'  => $lead->getId(),
                        'action'    => 'identified',
                        'details'   => [],
                        'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                    ];
                    $this->auditLogModel->writeToLog($log);
                }

                // IP added entry
                if (isset($details['ipAddresses']) && !empty($details['ipAddresses'][1])) {
                    $log = [
                        'bundle'    => 'lead',
                        'object'    => 'lead',
                        'objectId'  => $lead->getId(),
                        'action'    => 'ipadded',
                        'details'   => $details['ipAddresses'],
                        'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                    ];
                    $this->auditLogModel->writeToLog($log);
                }

                $this->leadEventDispatcher->dispatchEvents($event, $details);
            }
        }
    }

    /**
     * Add a lead delete entry to the audit log.
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadDelete(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        $log  = [
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $lead->getPrimaryIdentifier()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a field entry to the audit log.
     *
     * @param Events\LeadFieldEvent $event
     */
    public function onFieldPostSave(Events\LeadFieldEvent $event)
    {
        $field = $event->getField();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'field',
                'objectId'  => $field->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a field delete entry to the audit log.
     *
     * @param Events\LeadFieldEvent $event
     */
    public function onFieldDelete(Events\LeadFieldEvent $event)
    {
        $field = $event->getField();
        $log   = [
            'bundle'    => 'lead',
            'object'    => 'field',
            'objectId'  => $field->deletedId,
            'action'    => 'delete',
            'details'   => ['name', $field->getLabel()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a note entry to the audit log.
     *
     * @param Events\LeadNoteEvent $event
     */
    public function onNotePostSave(Events\LeadNoteEvent $event)
    {
        $note = $event->getNote();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'note',
                'objectId'  => $note->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a note delete entry to the audit log.
     *
     * @param Events\LeadNoteEvent $event
     */
    public function onNoteDelete(Events\LeadNoteEvent $event)
    {
        $note = $event->getNote();
        $log  = [
            'bundle'    => 'lead',
            'object'    => 'note',
            'objectId'  => $note->deletedId,
            'action'    => 'delete',
            'details'   => ['text', $note->getText()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * @param Events\LeadMergeEvent $event
     */
    public function preLeadMerge(Events\LeadMergeEvent $event)
    {
        $this->em->getRepository('MauticLeadBundle:LeadEventLog')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );
    }

    /**
     * @param Events\LeadMergeEvent $event
     */
    public function onLeadMerge(Events\LeadMergeEvent $event)
    {
        $this->em->getRepository('MauticLeadBundle:PointsChangeLog')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->em->getRepository('MauticLeadBundle:ListLead')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $this->em->getRepository('MauticLeadBundle:LeadNote')->updateLead(
            $event->getLoser()->getId(),
            $event->getVictor()->getId()
        );

        $log = [
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $event->getLoser()->getId(),
            'action'    => 'merge',
            'details'   => ['merged_into' => $event->getVictor()->getId()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param Events\LeadTimelineEvent $event
     */
    public function onTimelineGenerate(Events\LeadTimelineEvent $event)
    {
        $eventTypes = [
            'lead.utmtagsadded' => 'mautic.lead.event.utmtagsadded',
            'lead.donotcontact' => 'mautic.lead.event.donotcontact',
            'lead.imported'     => 'mautic.lead.event.imported',
        ];

        // Following events takes the event from the lead itself, so not applicable for API
        // where we are getting events for all leads.
        if ($event->isForTimeline()) {
            $eventTypes['lead.create']     = 'mautic.lead.event.create';
            $eventTypes['lead.identified'] = 'mautic.lead.event.identified';
            $eventTypes['lead.ipadded']    = 'mautic.lead.event.ipadded';
        }

        $filters = $event->getEventFilters();

        foreach ($eventTypes as $type => $label) {
            $name = $this->translator->trans($label);
            $event->addEventType($type, $name);

            if (!$event->isApplicable($type) || ($type != 'lead.utmtagsadded' && !empty($filters['search']))) {
                continue;
            }

            switch ($type) {
                case 'lead.create':
                    $this->addTimelineDateCreatedEntry($event, $type, $name);
                    break;

                case 'lead.identified':
                    $this->addTimelineDateIdentifiedEntry($event, $type, $name);
                    break;

                case 'lead.ipadded':
                    $this->addTimelineIpAddressEntries($event, $type, $name);
                    break;

                case 'lead.utmtagsadded':
                    $this->addTimelineUtmEntries($event, $type, $name);
                    break;

                case 'lead.donotcontact':
                    $this->addTimelineDoNotContactEntries($event, $type, $name);
                    break;

                case 'lead.imported':
                    $this->addTimelineImportedEntries($event, $type, $name);
                    break;
            }
        }
    }

    /**
     * @param Events\LeadTimelineEvent $event
     * @param                          $eventTypeKey
     * @param                          $eventTypeName
     */
    protected function addTimelineIpAddressEntries(Events\LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        $lead = $event->getLead();
        $rows = $this->auditLogModel->getRepository()->getLeadIpLogs($lead, $event->getQueryOptions());

        if (!$event->isEngagementCount()) {
            // Add to counter
            $event->addToCounter($eventTypeKey, $rows);

            // Add the entries to the event array
            $ipAddresses = ($lead instanceof Lead) ? $lead->getIpAddresses()->toArray() : null;

            foreach ($rows['results'] as $row) {
                if ($ipAddresses !== null && !isset($ipAddresses[$row['ip_address']])) {
                    continue;
                }

                $event->addEvent(
                    [
                        'event'         => $eventTypeKey,
                        'eventId'       => $eventTypeKey.$row['id'],
                        'eventLabel'    => $row['ip_address'],
                        'eventType'     => $eventTypeName,
                        'eventPriority' => -1, // Usually an IP is added after another event
                        'timestamp'     => $row['date_added'],
                        'extra'         => [
                            'ipDetails' => $ipAddresses[$row['ip_address']],
                        ],
                        'contentTemplate' => 'MauticLeadBundle:SubscribedEvents\Timeline:ipadded.html.php',
                        'contactId'       => $row['lead_id'],
                    ]
                );
            }
        } else {
            // Purposively not including this in engagements graph as it's info only
        }
    }

    /**
     * @param Events\LeadTimelineEvent $event
     * @param                          $eventTypeKey
     * @param                          $eventTypeName
     */
    protected function addTimelineDateCreatedEntry(Events\LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        // Do nothing if the lead is not set
        if (!$event->getLead() instanceof Lead) {
            return;
        }

        $dateAdded = $event->getLead()->getDateAdded();
        if (!$event->isEngagementCount()) {
            $event->addToCounter($eventTypeKey, 1);

            $start = $event->getEventLimit()['start'];
            if (empty($start)) {
                $event->addEvent(
                    [
                        'event'         => $eventTypeKey,
                        'eventId'       => $eventTypeKey.$event->getLead()->getId(),
                        'icon'          => 'fa-user-secret',
                        'eventType'     => $eventTypeName,
                        'eventPriority' => -5, // Usually something happened to create the lead so this should display afterward
                        'timestamp'     => $dateAdded,
                    ]
                );
            }
        } else {
            // Purposively not including this in engagements graph as it's info only
        }
    }

    /**
     * @param Events\LeadTimelineEvent $event
     * @param                          $eventTypeKey
     * @param                          $eventTypeName
     */
    protected function addTimelineDateIdentifiedEntry(Events\LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        // Do nothing if the lead is not set
        if (!$event->getLead() instanceof Lead) {
            return;
        }

        if ($dateIdentified = $event->getLead()->getDateIdentified()) {
            if (!$event->isEngagementCount()) {
                $event->addToCounter($eventTypeKey, 1);

                $start = $event->getEventLimit()['start'];
                if (empty($start)) {
                    $event->addEvent(
                        [
                            'event'         => $eventTypeKey,
                            'eventId'       => $eventTypeKey.$event->getLead()->getId(),
                            'icon'          => 'fa-user',
                            'eventType'     => $eventTypeName,
                            'eventPriority' => -4, // A lead is created prior to being identified
                            'timestamp'     => $dateIdentified,
                            'featured'      => true,
                        ]
                    );
                }
            } else {
                // Purposively not including this in engagements graph as it's info only
            }
        }
    }

    /**
     * @param Events\LeadTimelineEvent $event
     * @param                          $eventTypeKey
     * @param                          $eventTypeName
     */
    protected function addTimelineUtmEntries(Events\LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        $utmRepo = $this->em->getRepository('MauticLeadBundle:UtmTag');
        $utmTags = $utmRepo->getUtmTagsByLead($event->getLead(), $event->getQueryOptions());
        // Add to counter
        $event->addToCounter($eventTypeKey, $utmTags);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($utmTags['results'] as $utmTag) {
                $icon = 'fa-tag';
                if (isset($utmTag['utm_medium'])) {
                    switch (strtolower($utmTag['utm_medium'])) {
                            case 'social':
                            case 'socialmedia':
                                $icon = 'fa-'.((isset($utmTag['utm_source'])) ? strtolower($utmTag['utm_source']) : 'share-alt');
                                break;
                            case 'email':
                            case 'newsletter':
                                $icon = 'fa-envelope-o';
                                break;
                            case 'banner':
                            case 'ad':
                                $icon = 'fa-bullseye';
                                break;
                            case 'cpc':
                                $icon = 'fa-money';
                                break;
                            case 'location':
                                $icon = 'fa-map-marker';
                                break;
                            case 'device':
                                $icon = 'fa-'.((isset($utmTag['utm_source'])) ? strtolower($utmTag['utm_source']) : 'tablet');
                                break;
                        }
                }
                $event->addEvent(
                        [
                            'event'      => $eventTypeKey,
                            'eventType'  => $eventTypeName,
                            'eventId'    => $eventTypeKey.$utmTag['id'],
                            'eventLabel' => !empty($utmTag) ? $utmTag['utm_campaign'] : 'UTM Tags',
                            'timestamp'  => $utmTag['date_added'],
                            'icon'       => $icon,
                            'extra'      => [
                                'utmtags' => $utmTag,
                            ],
                            'contentTemplate' => 'MauticLeadBundle:SubscribedEvents\Timeline:utmadded.html.php',
                            'contactId'       => $utmTag['lead_id'],
                        ]
                    );
            }
        } else {
            // Purposively not including this in engagements graph as the engagement is counted by the page hit
        }
    }

    /**
     * @param Events\LeadTimelineEvent $event
     * @param                          $eventTypeKey
     * @param                          $eventTypeName
     */
    protected function addTimelineDoNotContactEntries(Events\LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        /** @var \Mautic\LeadBundle\Entity\DoNotContactRepository $dncRepo */
        $dncRepo = $this->em->getRepository('MauticLeadBundle:DoNotContact');

        /** @var \Mautic\LeadBundle\Entity\DoNotContact[] $entries */
        $rows = $dncRepo->getTimelineStats($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $rows);

        if (!$event->isEngagementCount()) {
            foreach ($rows['results'] as $row) {
                switch ($row['reason']) {
                    case DoNotContact::UNSUBSCRIBED:
                        $row['reason'] = $this->translator->trans('mautic.lead.event.donotcontact_unsubscribed');
                        break;
                    case DoNotContact::BOUNCED:
                        $row['reason'] = $this->translator->trans('mautic.lead.event.donotcontact_bounced');
                        break;
                    case DoNotContact::MANUAL:
                        $row['reason'] = $this->translator->trans('mautic.lead.event.donotcontact_manual');
                        break;
                }

                $template = 'MauticLeadBundle:SubscribedEvents\Timeline:donotcontact.html.php';
                $icon     = 'fa-ban';

                if (!empty($row['channel'])) {
                    if ($channelModel = $this->getChannelModel($row['channel'])) {
                        if ($channelModel instanceof ChannelTimelineInterface) {
                            if ($overrideTemplate = $channelModel->getChannelTimelineTemplate($eventTypeKey, $row)) {
                                $template = $overrideTemplate;
                            }

                            if ($overrideEventTypeName = $channelModel->getChannelTimelineLabel($eventTypeKey, $row)) {
                                $eventTypeName = $overrideEventTypeName;
                            }

                            if ($overrideIcon = $channelModel->getChannelTimelineIcon($eventTypeKey, $row)) {
                                $icon = $overrideIcon;
                            }
                        }

                        /* @deprecated - BC support to be removed in 3.0 */
                        // Allow a custom template if applicable
                        if (method_exists($channelModel, 'getDoNotContactLeadTimelineTemplate')) {
                            $template = $channelModel->getDoNotContactLeadTimelineTemplate($row);
                        }
                        if (method_exists($channelModel, 'getDoNotContactLeadTimelineLabel')) {
                            $eventTypeName = $channelModel->getDoNotContactLeadTimelineLabel($row);
                        }
                        if (method_exists($channelModel, 'getDoNotContactLeadTimelineIcon')) {
                            $icon = $channelModel->getDoNotContactLeadTimelineIcon($row);
                        }
                        /* end deprecation */

                        if (!empty($row['channel_id'])) {
                            if ($item = $this->getChannelEntityName($row['channel'], $row['channel_id'], true)) {
                                $row['itemName']  = $item['name'];
                                $row['itemRoute'] = $item['url'];
                            }
                        }
                    }
                }
                $contactId = $row['lead_id'];
                unset($row['lead_id']);
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$row['id'],
                        'eventLabel' => (isset($row['itemName'])) ?
                            [
                                'label' => ucfirst($row['channel']).' / '.$row['itemName'],
                                'href'  => $row['itemRoute'],
                            ] : ucfirst($row['channel']),
                        'eventType' => $eventTypeName,
                        'timestamp' => $row['date_added'],
                        'extra'     => [
                            'dnc' => $row,
                        ],
                        'contentTemplate' => $template,
                        'icon'            => $icon,
                        'contactId'       => $contactId,
                    ]
                );
            }
        }
    }

    /**
     * @param Events\LeadTimelineEvent $event
     * @param                          $eventTypeKey
     * @param                          $eventTypeName
     */
    protected function addTimelineImportedEntries(Events\LeadTimelineEvent $event, $eventTypeKey, $eventTypeName)
    {
        $eventLogRepo = $this->em->getRepository('MauticLeadBundle:LeadEventLog');
        $imports      = $eventLogRepo->getEventsByLead('lead', 'import', $event->getLead(), $event->getQueryOptions());
        // Add to counter
        $event->addToCounter($eventTypeKey, $imports);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($imports['results'] as $import) {
                if (is_string($import['properties'])) {
                    $import['properties'] = json_decode($import['properties'], true);
                }
                $eventLabel = 'N/A';
                if (!empty($import['properties']['file'])) {
                    $eventLabel = $import['properties']['file'];
                } elseif ($import['object_id']) {
                    $eventLabel = $import['object_id'];
                }
                $eventLabel = $this->translator->trans('mautic.lead.import.contact.action.'.$import['action'], ['%name%' => $eventLabel]);
                $event->addEvent(
                        [
                            'event'      => $eventTypeKey,
                            'eventId'    => $eventTypeKey.$import['id'],
                            'eventType'  => $eventTypeName,
                            'eventLabel' => !empty($import['object_id']) ? [
                                'label' => $eventLabel,
                                'href'  => $this->router->generate(
                                    'mautic_contact_import_action',
                                    ['objectAction' => 'view', 'objectId' => $import['object_id']]
                                ),
                            ] : $eventLabel,
                            'timestamp'       => $import['date_added'],
                            'icon'            => 'fa-download',
                            'extra'           => $import,
                            'contentTemplate' => 'MauticLeadBundle:SubscribedEvents\Timeline:import.html.php',
                            'contactId'       => $import['lead_id'],
                        ]
                    );
            }
        } else {
            // Purposively not including this
        }
    }
}
