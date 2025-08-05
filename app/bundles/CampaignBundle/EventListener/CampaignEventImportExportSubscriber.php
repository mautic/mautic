<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignEventImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private EventDispatcherInterface $dispatcher,
        private EventModel $eventModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onExport', 0],
            EntityImportEvent::class        => ['onImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onExport(EntityExportEvent $event): void
    {
        if (Event::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $campaignId = (int) $event->getEntityId();
        $campaign   = $this->campaignModel->getEntity($campaignId);

        if (!$campaign instanceof Campaign) {
            return;
        }

        $campaignEvents = $campaign->getEvents();
        $data           = [];

        foreach ($campaignEvents as $campaignEvent) {
            if (!$campaignEvent instanceof Event) {
                continue;
            }

            $eventData  = $this->createEventData($campaign, $campaignEvent);

            $this->handleChannelExport($campaignEvent, $data, $event);

            $event->addEntity(Event::ENTITY_NAME, $eventData);

            $this->logAction('export', $campaignEvent->getId(), $eventData);
        }

        /** @var array<string, array<int, array<string, mixed>>> $data */
        foreach ($data as $entityName => $entities) {
            /** @var array<string, mixed> $entities */
            $event->addEntities([$entityName => $entities]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createEventData(Campaign $campaign, Event $campaignEvent): array
    {
        $parentId = $campaignEvent->getParent()?->getId();

        return [
            'id'                           => $campaignEvent->getId(),
            'campaign_id'                  => $campaign->getId(),
            'name'                         => $campaignEvent->getName(),
            'description'                  => $campaignEvent->getDescription(),
            'type'                         => $campaignEvent->getType(),
            'event_type'                   => $campaignEvent->getEventType(),
            'event_order'                  => $campaignEvent->getOrder(),
            'properties'                   => $campaignEvent->getProperties(),
            'trigger_interval'             => $campaignEvent->getTriggerInterval(),
            'trigger_interval_unit'        => $campaignEvent->getTriggerIntervalUnit(),
            'trigger_mode'                 => $campaignEvent->getTriggerMode(),
            'trigger_date'                 => $campaignEvent->getTriggerDate()?->format(DATE_ATOM),
            'trigger_hour'                 => $campaignEvent->getTriggerHour()?->format('H:i:s'),
            'triggerRestrictedStartHour'   => $campaignEvent->getTriggerRestrictedStartHour()?->format('H:i:s'),
            'triggerRestrictedStopHour'    => $campaignEvent->getTriggerRestrictedStopHour()?->format('H:i:s'),
            'triggerRestrictedDaysOfWeek'  => $campaignEvent->getTriggerRestrictedDaysOfWeek(),
            'triggerWindow'                => $campaignEvent->getTriggerWindow(),
            'decisionPath'                 => $campaignEvent->getDecisionPath(),
            'channel'                      => $campaignEvent->getChannel(),
            'channel_id'                   => $campaignEvent->getChannelId(),
            'parent_id'                    => $parentId,
            'uuid'                         => $campaignEvent->getUuid(),
        ];
    }

    /** @phpstan-ignore-next-line */
    private function handleChannelExport(Event $campaignEvent, array &$data, EntityExportEvent $event): void
    {
        $channel      = $campaignEvent->getChannel();
        $channelId    = $campaignEvent->getChannelId();
        $dependencies = [
            Campaign::ENTITY_NAME => (int) $campaignEvent->getCampaign()->getId(),
            Event::ENTITY_NAME    => (int) $campaignEvent->getId(),
        ];

        if ($channel && $channelId) {
            $dependencies[$channel] = (int) $channelId;
            $subEvent               = new EntityExportEvent($channel, (int) $channelId);
            $this->dispatcher->dispatch($subEvent);
            $event->addDependencies($subEvent->getDependencies());
            $this->mergeExportData($data, $subEvent);
        } else {
            $eventType  = $campaignEvent->getType();
            $properties = $campaignEvent->getProperties();

            switch ($eventType) {
                case 'lead.pageHit':
                    if (!empty($properties['page'])) {
                        $dependencies[Page::ENTITY_NAME] = (int) $properties['page'];
                        $this->exportEntity(Page::ENTITY_NAME, (int) $properties['page'], $data, $event);
                    }
                    break;
                case 'lead.changelist':
                    if (!empty($properties['addToLists']) && is_array($properties['addToLists'])) {
                        foreach ($properties['addToLists'] as $segmentId) {
                            $dependencies[LeadList::ENTITY_NAME][] = (int) $segmentId;
                            $this->exportEntity(LeadList::ENTITY_NAME, (int) $segmentId, $data, $event);
                        }
                    }
                    if (!empty($properties['removeFromLists']) && is_array($properties['removeFromLists'])) {
                        foreach ($properties['removeFromLists'] as $segmentId) {
                            $dependencies[LeadList::ENTITY_NAME][] = (int) $segmentId;
                            $this->exportEntity(LeadList::ENTITY_NAME, (int) $segmentId, $data, $event);
                        }
                    }
                    break;
                case 'lead.segments':
                    if (!empty($properties['segments']) && is_array($properties['segments'])) {
                        foreach ($properties['segments'] as $segmentId) {
                            $dependencies[LeadList::ENTITY_NAME][] = (int) $segmentId;
                            $this->exportEntity(LeadList::ENTITY_NAME, (int) $segmentId, $data, $event);
                        }
                    }
                    break;
                case 'form.submit':
                    if (!empty($properties['forms']) && is_array($properties['forms'])) {
                        foreach ($properties['forms'] as $formId) {
                            $dependencies[Form::ENTITY_NAME][] = (int) $formId;
                            $this->exportEntity(Form::ENTITY_NAME, (int) $formId, $data, $event);
                        }
                    }
                    break;
                case 'lead.changepoints':
                case 'lead.points':
                    if (!empty($properties['group'])) {
                        $dependencies[Group::ENTITY_NAME] = (int) $properties['group'];
                        $this->exportEntity(Group::ENTITY_NAME, (int) $properties['group'], $data, $event);
                    }
                    break;
                case 'email.send.to.user':
                    if (!empty($properties['useremail'])) {
                        $dependencies[Email::ENTITY_NAME] = (int) $properties['useremail']['email'];
                        $this->exportEntity(Email::ENTITY_NAME, (int) $properties['useremail']['email'], $data, $event);
                    }
                    break;
            }
        }

        $event->addDependencyEntity(Event::ENTITY_NAME, $dependencies);
    }

    /** @phpstan-ignore-next-line */
    private function mergeExportData(array &$data, EntityExportEvent $subEvent): void
    {
        foreach ($subEvent->getEntities() as $key => $values) {
            if (!isset($data[$key])) {
                $data[$key] = $values;
            } else {
                $existingIds = array_column($data[$key], 'id');
                $data[$key]  = array_merge($data[$key], array_filter($values, function ($value) use ($existingIds) {
                    return !in_array($value['id'], $existingIds);
                }));
            }
        }
    }

    /** @phpstan-ignore-next-line */
    private function exportEntity(string $entityName, ?int $entityId, array &$data, EntityExportEvent $event): void
    {
        if ($entityId) {
            $subEvent = new EntityExportEvent($entityName, $entityId);
            $this->dispatcher->dispatch($subEvent);
            $event->addDependencies($subEvent->getDependencies());
            $this->mergeExportData($data, $subEvent);
        }
    }

    public function onImport(EntityImportEvent $event): void
    {
        if (Event::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            if (!is_array($element)) {
                continue;
            }

            $campaignEvent = $this->entityManager->getRepository(Event::class)->findOneBy(['uuid' => $element['uuid'] ?? null]);
            $isNew         = !$campaignEvent;

            $campaignEvent ??= new Event();
            $campaignEvent->setUuid($element['uuid'] ?? null);

            $campaignEvent->setName($element['name'] ?? '');
            $campaignEvent->setDescription($element['description'] ?? '');
            $campaignEvent->setType($element['type'] ?? '');
            $campaignEvent->setEventType($element['event_type'] ?? '');
            $campaignEvent->setOrder($element['event_order'] ?? 0);
            $campaignEvent->setProperties($element['properties'] ?? []);
            $campaignEvent->setTriggerInterval($element['trigger_interval'] ?? 0);
            $campaignEvent->setTriggerIntervalUnit($element['trigger_interval_unit'] ?? '');
            $campaignEvent->setTriggerMode($element['trigger_mode'] ?? '');
            $campaignEvent->setTriggerDate(isset($element['trigger_date']) ? new \DateTime($element['triggerDate']) : null);
            $campaignEvent->setTriggerHour($element['trigger_hour'] ?? null);
            $campaignEvent->setDecisionPath($element['decisionPath'] ?? '');
            $campaignEvent->setTriggerWindow($element['triggerWindow'] ?? null);
            $campaignEvent->setTriggerRestrictedDaysOfWeek($element['triggerRestrictedDaysOfWeek'] ?? null);
            $campaignEvent->setTriggerRestrictedStopHour($element['triggerRestrictedStopHour'] ?? null);
            $campaignEvent->setTriggerRestrictedStartHour($element['triggerRestrictedStartHour'] ?? null);
            $campaignEvent->setChannel($element['channel'] ?? '');
            $campaignEvent->setChannelId($element['channel_id'] ?? 0);

            $campaign = $this->campaignModel->getEntity($element['campaign_id']);
            if ($campaign instanceof Campaign) {
                $campaignEvent->setCampaign($campaign);
            }

            $this->eventModel->saveEntity($campaignEvent);

            $event->addEntityIdMap((int) $element['id'], $campaignEvent->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $campaignEvent->getName();
            $stats[$status]['ids'][]   = $campaignEvent->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $campaignEvent->getId(), $element);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Event::ENTITY_NAME => $info]);
            }
        }

        $this->updateParentEvents($event);
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (Event::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => []],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => []],
            'errors'                  => [],
        ];

        foreach ($event->getEntityData() as $element) {
            if (!empty($element['uuid']) && !UuidTrait::isValidUuid($element['uuid'])) {
                $summary['errors'][] = sprintf('Invalid UUID format for %s', $event->getEntityName());
                break;
            }

            $existing = $this->entityManager->getRepository(Event::class)->findOneBy(['uuid' => $element['uuid'] ?? null]);

            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][]   = $existing->getName();
                $summary[EntityImportEvent::UPDATE]['uuids'][]   = $existing->getUuid();
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $element['name'] ?? '';
            }
        }

        foreach ($summary as $type => $data) {
            if ('errors' === $type) {
                if (count($data) > 0) {
                    $event->setSummary('errors', ['messages' => $data]);
                }
                break;
            }

            if (isset($data['names']) && count($data['names']) > 0) {
                $event->setSummary($type, [Event::ENTITY_NAME => $data]);
            }
        }
    }

    private function updateParentEvents(EntityImportEvent $event): void
    {
        $idMap = $event->getEntityIdMap();

        foreach ($event->getEntityData() as $element) {
            if (isset($element['parent_id'])) {
                $originalParentId = (int) $element['parent_id'];
                $newParentId      = $idMap[$originalParentId] ?? null;

                if ($newParentId) {
                    $campaignEventId = $idMap[(int) $element['id']];
                    $campaignEvent   = $this->entityManager->getRepository(Event::class)->find($campaignEventId);
                    $parentEvent     = $this->entityManager->getRepository(Event::class)->find($newParentId);

                    if ($campaignEvent && $parentEvent) {
                        $campaignEvent->setParent($parentEvent);
                        $this->entityManager->persist($campaignEvent);
                    }
                }
            }
            if ('campaign.jump_to_event' === $element['type']) {
                $originalJumpToEventId = (int) $element['properties']['jumpToEvent'];
                $newJumpToEventId      = $idMap[$originalJumpToEventId] ?? null;

                if ($newJumpToEventId) {
                    $campaignEventId = $idMap[(int) $element['id']];
                    $campaignEvent   = $this->entityManager->getRepository(Event::class)->find($campaignEventId);

                    $element['properties']['jumpToEvent'] = $newJumpToEventId;
                    if ($campaignEvent) {
                        $campaignEvent->setProperties($element['properties'] ?? []);
                        $this->entityManager->persist($campaignEvent);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Event::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Event::class)->find($id);

            if ($entity) {
                $dependentEvents = $this->entityManager->getRepository(Event::class)->findBy(['parent' => $id]);

                foreach ($dependentEvents as $dependentEvent) {
                    // Set parent_id to null
                    $dependentEvent->setParent(null);
                    $this->entityManager->persist($dependentEvent);
                }

                // Make sure changes are saved
                $this->entityManager->flush();

                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Event::class]);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'campaign',
            'object'    => 'campaignEvent',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
