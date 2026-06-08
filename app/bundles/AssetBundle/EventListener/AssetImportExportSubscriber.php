<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AssetImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private AssetModel $assetModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onAssetExport', 0],
            EntityImportEvent::class        => ['onAssetImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onAssetExport(EntityExportEvent $event): void
    {
        if (Asset::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $assetId = $event->getEntityId();
        $asset   = $this->assetModel->getEntity($assetId);
        if (!$asset) {
            return;
        }

        $assetData = [
            'id'                     => $asset->getId(),
            'is_published'           => $asset->isPublished(),
            'title'                  => $asset->getTitle(),
            'description'            => $asset->getDescription(),
            'alias'                  => $asset->getAlias(),
            'storage_location'       => $asset->getStorageLocation(),
            'path'                   => $asset->getPath(),
            'remote_path'            => $asset->getRemotePath(),
            'original_file_name'     => $asset->getOriginalFileName(),
            'lang'                   => $asset->getLanguage(),
            'publish_up'             => $asset->getPublishUp() ? $asset->getPublishUp()->format(DATE_ATOM) : null,
            'publish_down'           => $asset->getPublishDown() ? $asset->getPublishDown()->format(DATE_ATOM) : null,
            'extension'              => $asset->getExtension(),
            'mime'                   => $asset->getMime(),
            'size'                   => (int) $asset->getSize(),
            'disallow'               => $asset->getDisallow(),
            'uuid'                   => $asset->getUuid(),
        ];

        $event->addEntity(Asset::ENTITY_NAME, $assetData);
        $log = [
            'bundle'    => 'asset',
            'object'    => 'asset',
            'objectId'  => $asset->getId(),
            'action'    => 'export',
            'details'   => $assetData,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onAssetImport(EntityImportEvent $event): void
    {
        if (Asset::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $object = $this->entityManager->getRepository(Asset::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew  = !$object;

            $object ??= new Asset();
            if (isset($element['size'])) {
                $element['size'] = (int) $element['size'];
            }
            $this->serializer->denormalize(
                $element,
                Asset::class,
                null,
                ['object_to_populate' => $object]
            );
            $this->assetModel->saveEntity($object);

            $event->addEntityIdMap((int) $element['id'], $object->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $object->getTitle();
            $stats[$status]['ids'][]   = $object->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $object->getId(), $element);
        }
        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Asset::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Asset::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Asset::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Asset::class]);
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        $this->performDuplicationCheck(
            $event,
            Asset::ENTITY_NAME,
            Asset::class,
            'title',
            $this->entityManager
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'asset',
            'object'    => 'asset',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
