<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DynamicContentImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private DynamicContentModel $dynamicContentModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private DenormalizerInterface $serializer,
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
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $object = $this->dynamicContentModel->getEntity($event->getEntityId());
        if (!$object) {
            return;
        }

        $data = [
            'id'                     => $object->getId(),
            'translation_parent_id'  => $object->getTranslationParent(),
            'variant_parent_id'      => $object->getVariantParent(),
            'is_published'           => $object->getIsPublished(),
            'name'                   => $object->getName(),
            'description'            => $object->getDescription(),
            'publish_up'             => $object->getPublishUp(),
            'publish_down'           => $object->getPublishDown(),
            'content'                => $object->getContent(),
            'utm_tags'               => $object->getUtmTags(),
            'lang'                   => $object->getLanguage(),
            'variant_settings'       => $object->getVariantSettings(),
            'variant_start_date'     => $object->getVariantStartDate(),
            'filters'                => $object->getFilters(),
            'is_campaign_based'      => $object->getIsCampaignBased(),
            'slot_name'              => $object->getSlotName(),
            'uuid'                   => $object->getUuid(),
        ];
        $event->addEntity(DynamicContent::ENTITY_NAME, $data);

        $this->logAction('export', $object->getId(), $data);
    }

    public function onImport(EntityImportEvent $event): void
    {
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $object = $this->entityManager->getRepository(DynamicContent::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew  = !$object;

            $object ??= new DynamicContent();
            $this->serializer->denormalize(
                $element,
                DynamicContent::class,
                null,
                ['object_to_populate' => $object]
            );

            $this->dynamicContentModel->saveEntity($object);

            $event->addEntityIdMap((int) $element['id'], $object->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $object->getName();
            $stats[$status]['ids'][]   = $object->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $object->getId(), $element);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [DynamicContent::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(DynamicContent::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => DynamicContent::class]);
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        $this->performDuplicationCheck(
            $event,
            DynamicContent::ENTITY_NAME,
            DynamicContent::class,
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
            'bundle'    => 'dynamicContent',
            'object'    => 'dynamicContent',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
