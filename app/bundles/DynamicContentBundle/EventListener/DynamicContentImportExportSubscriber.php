<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
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

        $userName = '';
        if ($event->getUserId()) {
            $user     = $this->userModel->getEntity($event->getUserId());
            $userName = $user ? $user->getFirstName().' '.$user->getLastName() : '';
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $object = $this->entityManager->getRepository(DynamicContent::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew  = !$object;

            $object ??= new DynamicContent();
            $isNew && $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());
            $object->setUuid($element['uuid']);
            $isNew ? $object->setCreatedByUser($userName) : $object->setModifiedByUser($userName);

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

            $this->entityManager->persist($object);
            $this->entityManager->flush();

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
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $item) {
            $existing = $this->entityManager->getRepository(DynamicContent::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][] = $existing->getName();
                $summary[EntityImportEvent::UPDATE]['ids'][]   = $existing->getId();
                ++$summary[EntityImportEvent::UPDATE]['count'];
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['name'];
                ++$summary[EntityImportEvent::NEW]['count'];
            }
        }

        foreach ($summary as $type => $info) {
            if ($info['count'] > 0) {
                $event->setSummary($type, [DynamicContent::ENTITY_NAME => $info]);
            }
        }
    }

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
