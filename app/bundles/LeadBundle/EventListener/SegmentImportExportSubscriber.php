<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SegmentImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private ListModel $leadListModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private PluginModel $pluginModel,
        private EventDispatcherInterface $dispatcher,
        private FieldModel $fieldModel,
        private IpLookupHelper $ipLookupHelper,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onSegmentExport', 0],
            EntityImportEvent::class        => ['onSegmentImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onSegmentExport(EntityExportEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $leadListId = $event->getEntityId();
        $leadList   = $this->leadListModel->getEntity($leadListId);
        if (!$leadList) {
            return;
        }
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

        foreach ($filters as $filter) {
            if (isset($filter['object']) && in_array($filter['object'], ['lead', 'company'], true)) {
                foreach ($customFields as $field) {
                    if (isset($filter['field']) && $filter['field'] === $field->getAlias()) {
                        $subEvent = new EntityExportEvent(LeadField::ENTITY_NAME, (int) $field->getId());
                        $this->dispatcher->dispatch($subEvent);
                        $this->mergeExportData($data, $subEvent);

                        $event->addDependencyEntity(LeadList::ENTITY_NAME, [
                            LeadList::ENTITY_NAME   => (int) $leadListId,
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
            $segment = $this->entityManager->getRepository(LeadList::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew   = !$segment;

            $segment ??= new LeadList();

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
            $entity = $this->entityManager->getRepository(LeadList::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => LeadList::class]);
            }
        }

        $this->entityManager->flush();
    }

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
