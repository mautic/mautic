<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Helper\EventEntityReferences;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Serializer\ImportEntityDenormalizer;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SegmentImportExportSubscriber
{
    use ImportExportTrait;

    /**
     * The segments being exported, as segment filters can refer to each other.
     *
     * @var array<int, true>
     */
    private array $exporting = [];

    public function __construct(
        private readonly ListModel $leadListModel,
        private readonly EntityManagerInterface $entityManager,
        private readonly LeadListRepository $leadListRepository,
        private readonly AuditLogModel $auditLogModel,
        private readonly PluginModel $pluginModel,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly FieldModel $fieldModel,
        private readonly IpLookupHelper $ipLookupHelper,
        private readonly ImportEntityDenormalizer $serializer,
        private readonly EventEntityReferences $eventEntityReferences,
    ) {
    }

    #[AsEventListener(
        event: EntityExportEvent::class,
    )]
    public function onSegmentExport(EntityExportEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $leadListId = $event->getEntityId();
        $leadList   = $this->leadListModel->getEntity($leadListId);
        if (!$leadList instanceof LeadList || isset($this->exporting[$leadListId])) {
            return;
        }

        $this->exporting[$leadListId] = true;

        try {
            $this->exportSegment($event, $leadList);
        } finally {
            unset($this->exporting[$leadListId]);
        }
    }

    private function exportSegment(EntityExportEvent $event, LeadList $leadList): void
    {
        $leadListId  = (int) $leadList->getId();
        $segmentData = [
            'id'                   => $leadListId,
            'name'                 => $leadList->getName(),
            'is_published'         => $leadList->getIsPublished(),
            'description'          => $leadList->getDescription(),
            'alias'                => $leadList->getAlias(),
            'public_name'          => $leadList->getPublicName(),
            'filters'              => $leadList->getFilters(),
            'is_global'            => $leadList->getIsGlobal(),
            'is_preference_center' => $leadList->getIsPreferenceCenter(),
            'uuid'                 => $leadList->getUuid(),
        ];
        $customFields   = $this->fieldModel->getLeadFieldCustomFields();
        $filters        = $leadList->getFilters();
        $data           = [];

        if ($linkedUuids = $this->getLinkedUuids($filters)) {
            $segmentData['linked_uuids'] = $linkedUuids;
        }

        if ($tagNames = $this->getTagNames($filters)) {
            $segmentData['linked_names'][EventEntityReferences::TAG_ID] = $tagNames;
        }

        // Takes along the segments, emails and assets the filters refer to, so the import can point the filters to them
        foreach ($this->eventEntityReferences->getSegmentFilterExportedIds($filters) as $entityName => $entityIds) {
            foreach ($entityIds as $entityId) {
                $subEvent = new EntityExportEvent($entityName, $entityId);
                $this->dispatcher->dispatch($subEvent);
                $this->mergeExportData($data, $subEvent);
                $event->addDependencies($subEvent->getDependencies());

                $event->addDependencyEntity(LeadList::ENTITY_NAME, [
                    LeadList::ENTITY_NAME => $leadListId,
                    $entityName           => $entityId,
                ]);
            }
        }

        foreach ($filters as $filter) {
            if (isset($filter['object']) && in_array($filter['object'], ['lead', 'company'], true)) {
                foreach ($customFields as $field) {
                    if (isset($filter['field']) && $filter['field'] === $field->getAlias()) {
                        $subEvent = new EntityExportEvent(LeadField::ENTITY_NAME, (int) $field->getId());
                        $this->dispatcher->dispatch($subEvent);
                        $this->mergeExportData($data, $subEvent);

                        $event->addDependencyEntity(LeadList::ENTITY_NAME, [
                            LeadList::ENTITY_NAME   => $leadListId,
                            LeadField::ENTITY_NAME  => (int) $field->getId(),
                        ]);
                    }
                }
            }
        }
        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }
        $event->addEntity(LeadList::ENTITY_NAME, $segmentData);
        $this->logAction('export', $leadListId, $segmentData);
    }

    /**
     * The UUIDs of the campaigns and stages the filters refer to, so the import can find them on the target instance.
     *
     * @param array<int, array<string, mixed>> $filters
     *
     * @return array<string, array<int, string>>
     */
    private function getLinkedUuids(array $filters): array
    {
        $uuids = [];

        foreach ($this->eventEntityReferences->getSegmentFilterLinkedIds($filters) as $kind => $ids) {
            foreach ($ids as $id) {
                $entity = $this->entityManager->find(EventEntityReferences::LINKED[$kind], $id);

                if ($entity instanceof UuidInterface && $entity->getUuid()) {
                    $uuids[$kind][$id] = $entity->getUuid();
                }
            }
        }

        return $uuids;
    }

    /**
     * Filters refer to tags by ID, so the import finds or creates them by name.
     *
     * @param array<int, array<string, mixed>> $filters
     *
     * @return array<int, string>
     */
    private function getTagNames(array $filters): array
    {
        $names = [];

        foreach ($this->eventEntityReferences->getSegmentFilterTagIds($filters) as $id) {
            $tag = $this->entityManager->find(Tag::class, $id);

            $name = $tag instanceof Tag ? $tag->getTag() : null;

            // A tag can be named "0"
            if (is_string($name) && '' !== $name) {
                $names[$id] = $name;
            }
        }

        return $names;
    }

    #[AsEventListener(
        event: EntityImportEvent::class,
    )]
    public function onSegmentImport(EntityImportEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $segment = $this->leadListRepository->findOneBy(['uuid' => $element['uuid']]);
            $isNew   = !$segment;

            $segment ??= new LeadList();

            // The campaign import points the filters to the entities on this instance once they are all imported
            unset($element['linked_uuids'], $element['linked_names']);

            $this->serializer->denormalize(
                $element,
                LeadList::class,
                null,
                ['object_to_populate' => $segment]
            );
            $this->leadListModel->saveEntity($segment);

            $event->addEntityIdMap((int) $element['id'], $segment->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $segment->getName();
            $stats[$status]['ids'][]   = $segment->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $segment->getId(), $element);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [LeadList::ENTITY_NAME => $info]);
            }
        }
    }

    #[AsEventListener(
        event: EntityImportUndoEvent::class,
    )]
    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->leadListRepository->find($id);

            if ($entity instanceof LeadList) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => LeadList::class]);
            }
        }

        $this->entityManager->flush();
    }

    #[AsEventListener(
        event: EntityImportAnalyzeEvent::class,
    )]
    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        // Check for custom object dependencies first
        foreach ($event->getEntityData() as $item) {
            if (!empty($item['filters'])) {
                foreach ($item['filters'] as $filter) {
                    if (isset($filter['object']) && 'custom_object' === $filter['object']) {
                        $plugins = $this->pluginModel->getAllPluginsConfig();
                        if (!isset($plugins['CustomObjectsBundle'])) {
                            $event->setSummary('errors', ['messages' => ['Segment filter uses Custom Objects but the plugin CustomObjectBundle is not installed.']]);

                            return;
                        }
                    }
                }
            }
        }

        // Use the trait for standard duplication check
        $this->performDuplicationCheck(
            $event,
            LeadList::ENTITY_NAME,
            LeadList::class,
            'name',
            $this->entityManager
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'segment',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
