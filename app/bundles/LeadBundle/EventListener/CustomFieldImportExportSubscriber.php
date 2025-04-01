<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomFieldImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldModel $fieldModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onLeadFieldExport', 0],
            EntityImportEvent::class        => ['onLeadFieldImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onLeadFieldExport(EntityExportEvent $event): void
    {
        if (LeadField::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $leadFieldId = $event->getEntityId();
        $leadField   = $this->fieldModel->getEntity($leadFieldId);

        if (!$leadField) {
            return;
        }

        $leadFieldData = [
            'id'                          => $leadField->getId(),
            'is_published'                => $leadField->getIsPublished(),
            'label'                       => $leadField->getLabel(),
            'alias'                       => $leadField->getAlias(),
            'type'                        => $leadField->getType(),
            'field_group'                 => $leadField->getGroup(),
            'default_value'               => $leadField->getDefaultValue(),
            'is_required'                 => $leadField->getIsRequired(),
            'is_fixed'                    => $leadField->getIsFixed(),
            'is_visible'                  => $leadField->getIsVisible(),
            'is_short_visible'            => $leadField->getIsShortVisible(),
            'is_listable'                 => $leadField->getIsListable(),
            'is_publicly_updatable'       => $leadField->getIsPubliclyUpdatable(),
            'is_unique_identifier'        => $leadField->getIsUniqueIdentifier(),
            'char_length_limit'           => $leadField->getCharLengthLimit(),
            'field_order'                 => $leadField->getOrder(),
            'object'                      => $leadField->getObject(),
            'properties'                  => $leadField->getProperties(),
            'column_is_not_created'       => $leadField->getColumnIsNotCreated(),
            'original_is_published_value' => $leadField->getOriginalIsPublishedValue(),
            'uuid'                        => $leadField->getUuid(),
        ];

        $event->addEntity(LeadField::ENTITY_NAME, $leadFieldData);
        $this->logAction('export', $leadField->getId(), $leadFieldData);
    }

    public function onLeadFieldImport(EntityImportEvent $event): void
    {
        if (LeadField::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
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
            $field = $this->entityManager->getRepository(LeadField::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew = !$field;

            $field ??= new LeadField();

            $this->serializer->denormalize(
                $element,
                LeadField::class,
                null,
                ['object_to_populate' => $field]
            );
            if ($isNew) {
                $alias       = $element['alias'] ?? $field->getAlias() ?? '';
                $uniqueAlias = $this->fieldModel->generateUniqueFieldAlias($alias);
                $field->setAlias($uniqueAlias);
            }

            $this->fieldModel->saveEntity($field);

            $event->addEntityIdMap((int) $element['id'], $field->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $field->getLabel();
            $stats[$status]['ids'][]   = $field->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $field->getId(), $element);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [LeadField::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (LeadField::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(LeadField::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => LeadField::class]);
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (LeadField::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $item) {
            $existing = $this->entityManager->getRepository(LeadField::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][]   = $existing->getLabel();
                $summary[EntityImportEvent::UPDATE]['uuids'][]   = $existing->getUuid();
                ++$summary[EntityImportEvent::UPDATE]['count'];
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['label'];
                ++$summary[EntityImportEvent::NEW]['count'];
            }
        }

        foreach ($summary as $type => $data) {
            if ($data['count'] > 0) {
                $event->setSummary($type, [LeadField::ENTITY_NAME => $data]);
            }
        }
    }

    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'leadField',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
