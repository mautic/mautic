<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AssetImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetModel $assetModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_ASSET => ['onAssetExport', 0],
        ];
    }

    public function onAssetExport(EntityExportEvent $event): void
    {
        $assetId = $event->getEntityId();
        $asset   = $this->assetModel->getEntity($assetId);
        if (!$asset) {
            return;
        }

        $assetData = [
            'id'                     => $asset->getId(),
            // 'category_id'            => $asset->getCategory() ? $asset->getCategory()->getId() : null,
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
            'download_count'         => $asset->getDownloadCount(),
            'extension'              => $asset->getExtension(),
            'mime'                   => $asset->getMime(),
            'size'                   => $asset->getSize(),
            'disallow'               => $asset->getDisallow(),
        ];

        $event->addEntity(EntityExportEvent::EXPORT_ASSET, $assetData);
    }
}
