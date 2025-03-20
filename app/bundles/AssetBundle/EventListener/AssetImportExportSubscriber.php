<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AssetImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetModel $assetModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onAssetExport', 0],
            EntityImportEvent::class => ['onAssetImport', 0],
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
            'size'                   => $asset->getSize(),
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
        if (Asset::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $elements  = $event->getEntityData();
        $userId    = $event->getUserId();
        $userName  = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $object = new Asset();
            $object->setTitle($element['title']);
            $object->setIsPublished((bool) $element['is_published']);
            $object->setDescription($element['description'] ?? '');
            $object->setAlias($element['alias'] ?? '');
            $object->setStorageLocation($element['storage_location'] ?? '');
            $object->setPath($element['path'] ?? '');
            $object->setRemotePath($element['remote_path'] ?? '');
            $object->setOriginalFileName($element['original_file_name'] ?? '');
            $object->setMime($element['mime'] ?? '');
            $object->setSize($element['size'] ?? '');
            $object->setDisallow($element['disallow'] ?? true);
            $object->setExtension($element['extension'] ?? '');
            $object->setLanguage($element['lang'] ?? '');
            $object->setPublishUp($element['publish_up']);
            $object->setPublishDown($element['publish_down']);
            $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());
            $object->setCreatedByUser($userName);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $log = [
                'bundle'    => 'asset',
                'object'    => 'asset',
                'objectId'  => $object->getId(),
                'action'    => 'import',
                'details'   => $element,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }
}
