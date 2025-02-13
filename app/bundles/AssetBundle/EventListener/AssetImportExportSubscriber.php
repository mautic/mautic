<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AssetImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetModel $assetModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class              => ['onAssetExport', 0],
            EntityImportEvent::IMPORT_ASSET_EVENT => ['onAssetImport', 0],
        ];
    }

    public function onAssetExport(EntityExportEvent $event): void
    {
        if (EntityExportEvent::EXPORT_ASSET_EVENT !== $event->getEntityName()) {
            return;
        }

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
            'extension'              => $asset->getExtension(),
            'mime'                   => $asset->getMime(),
            'size'                   => $asset->getSize(),
            'disallow'               => $asset->getDisallow(),
        ];

        $event->addEntity(EntityExportEvent::EXPORT_ASSET_EVENT, $assetData);
    }

    public function onAssetImport(EntityImportEvent $event): void
    {
        $output    = new ConsoleOutput();
        $elements  = $event->getEntityData();
        $userId    = $event->getUserId();
        $userName  = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.');
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $object = new \Mautic\AssetBundle\Entity\Asset();
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
            $output->writeln('<info>Imported asset: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
