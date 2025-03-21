<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DynamicContentImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DynamicContentModel $dynamicContentModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
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

        $log = [
            'bundle'    => 'dynamicContent',
            'object'    => 'dynamicContent',
            'objectId'  => $object->getId(),
            'action'    => 'export',
            'details'   => $data,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onImport(EntityImportEvent $event): void
    {
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $elements = $event->getEntityData();
        if (!$elements) {
            return;
        }

        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            }
        }

        $updateNames = [];
        $updateIds   = [];
        $newNames    = [];
        $newIds      = [];
        $updateCount = 0;
        $newCount    = 0;

        foreach ($elements as $element) {
            $existingObject = $this->entityManager->getRepository(DynamicContent::class)->findOneBy(['uuid' => $element['uuid']]);
            if ($existingObject) {
                // Update existing object
                $object = $existingObject;
                $object->setModifiedByUser($userName);
                $status = EntityImportEvent::UPDATE;
            } else {
                // Create a new object
                $object = new DynamicContent();
                $object->setDateAdded(new \DateTime());
                $object->setCreatedByUser($userName);
                $status = EntityImportEvent::NEW;
            }

            $object->setTranslationParent($element['translation_parent_id'] ?? null);
            $object->setVariantParent($element['variant_parent_id'] ?? null);
            $object->setIsPublished((bool) ($element['is_published'] ?? false));
            $object->setName($element['name'] ?? '');
            $object->setDescription($element['description'] ?? '');
            $object->setPublishUp($element['publish_up'] ?? null);
            $object->setPublishDown($element['publish_down'] ?? null);
            $object->setSentCount($element['sent_count'] ?? 0);
            $object->setContent($element['content'] ?? '');
            $object->setUtmTags($element['utm_tags'] ?? '');
            $object->setLanguage($element['lang'] ?? '');
            $object->setVariantSettings($element['variant_settings'] ?? '');
            $object->setVariantStartDate($element['variant_start_date'] ?? null);
            $object->setFilters($element['filters'] ?? '');
            $object->setIsCampaignBased((bool) ($element['is_campaign_based'] ?? false));
            $object->setSlotName($element['slot_name'] ?? '');

            $object->setDateModified(new \DateTime());

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());

            if (EntityImportEvent::UPDATE === $status) {
                $updateNames[] = $object->getName();
                $updateIds[]   = $object->getId();
                ++$updateCount;
            } else {
                $newNames[] = $object->getName();
                $newIds[]   = $object->getId();
                ++$newCount;
            }

            $log = [
                'bundle'    => 'dynamicContent',
                'object'    => 'dynamicContent',
                'objectId'  => $object->getId(),
                'action'    => 'import',
                'details'   => $element,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }

        if ($newCount > 0) {
            $event->setStatus(EntityImportEvent::NEW, [
                DynamicContent::ENTITY_NAME => [
                    'names' => $newNames,
                    'ids'   => $newIds,
                    'count' => $newCount,
                ],
            ]);
        }
        if ($updateCount > 0) {
            $event->setStatus(EntityImportEvent::UPDATE, [
                DynamicContent::ENTITY_NAME => [
                    'names' => $updateNames,
                    'ids'   => $updateIds,
                    'count' => $updateCount,
                ],
            ]);
        }
    }
}
