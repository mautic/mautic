<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
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
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onExport', 0],
            EntityImportEvent::class => ['onImport', 0],
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

            $log = [
                'bundle'    => 'campaign',
                'object'    => 'campaignEvent',
                'objectId'  => $campaignEvent->getId(),
                'action'    => 'export',
                'details'   => $eventData,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }

        foreach ($data as $entityName => $entities) {
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
            'id'                    => $campaignEvent->getId(),
            'campaign_id'           => $campaign->getId(),
            'name'                  => $campaignEvent->getName(),
            'description'           => $campaignEvent->getDescription(),
            'type'                  => $campaignEvent->getType(),
            'event_type'            => $campaignEvent->getEventType(),
            'event_order'           => $campaignEvent->getOrder(),
            'properties'            => $campaignEvent->getProperties(),
            'trigger_interval'      => $campaignEvent->getTriggerInterval(),
            'trigger_interval_unit' => $campaignEvent->getTriggerIntervalUnit(),
            'trigger_mode'          => $campaignEvent->getTriggerMode(),
            'triggerDate'           => $campaignEvent->getTriggerDate()?->format(DATE_ATOM),
            'channel'               => $campaignEvent->getChannel(),
            'channel_id'            => $campaignEvent->getChannelId(),
            'parent_id'             => $parentId,
            'uuid'                  => $campaignEvent->getUuid(),
        ];
    }

    /**
     * @param array<array<string, mixed>> $data
     */
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
            $subEvent               = new EntityExportEvent($channel, $channelId);
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
            }
        }

        $event->addDependencyEntity(Event::ENTITY_NAME, $dependencies);
    }

    /**
     * Merge exported data avoiding duplicate entries.
     *
     * @param array<string, array> $data
     */
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

    /**
     * Handle exporting an entity based on its ID.
     *
     * @param array<string, array> $data
     */
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
        if (Event::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();

        if (empty($elements)) {
            return;
        }

        $updateNames = [];
        $updateIds   = [];
        $newNames    = [];
        $newIds      = [];
        $updateCount = 0;
        $newCount    = 0;

        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            $existingObject = $this->entityManager->getRepository(Event::class)->findOneBy(['uuid' => $element['uuid'] ?? null]);

            if ($existingObject) {
                // Update existing object
                $campaignEvent = $existingObject;
                $status        = EntityImportEvent::UPDATE;
            } else {
                // Create new
                $campaignEvent = new Event();
                $status        = EntityImportEvent::NEW;
            }

            $campaignEvent->setName($element['name'] ?? '');
            $campaignEvent->setDescription($element['description'] ?? '');
            $campaignEvent->setType($element['type'] ?? '');
            $campaignEvent->setEventType($element['event_type'] ?? '');
            $campaignEvent->setOrder($element['event_order'] ?? 0);
            $campaignEvent->setProperties($element['properties'] ?? []);
            $campaignEvent->setTriggerInterval($element['trigger_interval'] ?? 0);
            $campaignEvent->setTriggerIntervalUnit($element['trigger_interval_unit'] ?? '');
            $campaignEvent->setTriggerMode($element['trigger_mode'] ?? '');
            $campaignEvent->setTriggerDate(isset($element['triggerDate']) ? new \DateTime($element['triggerDate']) : null);
            $campaignEvent->setChannel($element['channel'] ?? '');
            $campaignEvent->setChannelId($element['channel_id'] ?? 0);

            $campaign = $this->campaignModel->getEntity($element['campaign_id']);
            if ($campaign instanceof Campaign) {
                $campaignEvent->setCampaign($campaign);
            }

            $this->entityManager->persist($campaignEvent);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $campaignEvent->getId());

            if (EntityImportEvent::UPDATE === $status) {
                $updateNames[] = $campaignEvent->getName();
                $updateIds[]   = $campaignEvent->getId();
                ++$updateCount;
            } else {
                $newNames[] = $campaignEvent->getName();
                $newIds[]   = $campaignEvent->getId();
                ++$newCount;
            }

            $log = [
                'bundle'    => 'campaign',
                'object'    => 'campaignEvent',
                'objectId'  => $campaignEvent->getId(),
                'action'    => 'import',
                'details'   => $element,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);

            $output->writeln('<info>Imported campaign event: '.$campaignEvent->getName().' with ID: '.$campaignEvent->getId().'</info>');
        }

        if ($newCount > 0) {
            $event->setStatus(EntityImportEvent::NEW, [
                Event::ENTITY_NAME => [
                    'names' => $newNames,
                    'ids'   => $newIds,
                    'count' => $newCount,
                ],
            ]);
        }

        if ($updateCount > 0) {
            $event->setStatus(EntityImportEvent::UPDATE, [
                Event::ENTITY_NAME => [
                    'names' => $updateNames,
                    'ids'   => $updateIds,
                    'count' => $updateCount,
                ],
            ]);
        }

        $this->updateParentEvents($event);
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
        }

        $this->entityManager->flush();
    }
}
