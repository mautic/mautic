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
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomFieldImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private FieldModel $fieldModel,
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

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $field = $this->entityManager->getRepository(LeadField::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew = !$field;

            $field ??= new LeadField();
            $elementForDenormalize = $element;
            unset($elementForDenormalize['id']);

            $this->serializer->denormalize(
                $elementForDenormalize,
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
        $this->performDuplicationCheck(
            $event,
            LeadField::ENTITY_NAME,
            LeadField::class,
            'label',
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
            'object'    => 'leadField',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
