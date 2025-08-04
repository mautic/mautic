<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FieldImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private FieldModel $fieldModel,
        private LeadFieldModel $leadFieldModel,
        private EventDispatcherInterface $dispatcher,
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
        if (Field::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $fieldId = $event->getEntityId();
        $field   = $this->fieldModel->getEntity($fieldId);

        if (!$field instanceof Field) {
            return;
        }

        $fieldData = [
            'id'                        => $field->getId(),
            'uuid'                      => $field->getUuid(),
            'label'                     => $field->getLabel(),
            'show_label'                => $field->getShowLabel(),
            'alias'                     => $field->getAlias(),
            'type'                      => $field->getType(),
            'is_custom'                 => $field->isCustom(),
            'custom_parameters'         => $field->getCustomParameters(),
            'default_value'             => $field->getDefaultValue(),
            'is_required'               => $field->isRequired(),
            'validation_message'        => $field->getValidationMessage(),
            'help_message'              => $field->getHelpMessage(),
            'field_order'               => $field->getOrder(),
            'properties'                => $field->getProperties(),
            'validation'                => $field->getValidation(),
            'parent_id'                 => $field->getParent(),
            'conditions'                => $field->getConditions(),
            'label_attr'                => $field->getLabelAttributes(),
            'input_attr'                => $field->getInputAttributes(),
            'container_attr'            => $field->getContainerAttributes(),
            'save_result'               => $field->getSaveResult(),
            'is_auto_fill'              => $field->getIsAutoFill(),
            'show_when_value_exists'    => $field->getShowWhenValueExists(),
            'show_after_x_submissions'  => $field->getShowAfterXSubmissions(),
            'mapped_object'             => $field->getMappedObject(),
            'mapped_field'              => $field->getMappedField(),
            'form'                      => $field->getForm()->getId(),
        ];
        $event->addEntity(Field::ENTITY_NAME, $fieldData);
        $this->logAction('export', $fieldId, $fieldData, 'formField');

        $data = [];

        if (isset($fieldData['mapped_object']) && isset($fieldData['mapped_field']) && in_array($fieldData['mapped_object'], ['contact', 'company'], true)) {
            $customFields   = $this->leadFieldModel->getLeadFieldCustomFields();
            foreach ($customFields as $object) {
                if (isset($fieldData['mapped_field']) && $fieldData['mapped_field'] === $object->getAlias()) {
                    $subEvent = new EntityExportEvent(LeadField::ENTITY_NAME, (int) $object->getId());
                    $this->dispatcher->dispatch($subEvent);
                    $this->mergeExportData($data, $subEvent);

                    $event->addDependencyEntity(Form::ENTITY_NAME, [
                        Field::ENTITY_NAME       => (int) $fieldId,
                        LeadField::ENTITY_NAME   => (int) $object->getId(),
                    ]);
                }
            }
        }

        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }
    }

    public function onImport(EntityImportEvent $event): void
    {
        if (Field::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $fieldData) {
            $field = $this->entityManager->getRepository(Field::class)->findOneBy(['uuid' => $fieldData['uuid']]);
            $isNew = !$field;
            $field ??= new Field();

            foreach (['properties', 'validation', 'custom_parameters', 'conditions', 'label_attr', 'input_attr', 'container_attr'] as $jsonField) {
                if (isset($fieldData[$jsonField]) && is_string($fieldData[$jsonField])) {
                    $decoded               = json_decode($fieldData[$jsonField], true);
                    $fieldData[$jsonField] = is_array($decoded) ? $decoded : [];
                }
            }

            // Form mapping
            if (!empty($fieldData['form'])) {
                $form = $this->entityManager->getRepository(Form::class)->find($fieldData['form']);
                if ($form instanceof Form) {
                    $field->setForm($form);
                    unset($fieldData['form']);
                }
            }

            $this->serializer->denormalize(
                $fieldData,
                Field::class,
                null,
                ['object_to_populate' => $field]
            );

            $this->fieldModel->saveEntity($field);
            $event->addEntityIdMap((int) $fieldData['id'], $field->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $field->getLabel() ?? $field->getAlias();
            $stats[$status]['ids'][]   = $field->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $field->getId(), $fieldData, 'formField');
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Field::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Field::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }

        foreach ($summary['ids'] as $id) {
            $field = $this->entityManager->getRepository(Field::class)->find($id);

            if ($field) {
                $this->entityManager->remove($field);
                $this->logAction('undo_import', $id, ['deletedEntity' => Field::class], 'formField');
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (Field::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => []],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => []],
            'errors'                  => [],
        ];

        foreach ($event->getEntityData() as $item) {
            if (!empty($item['uuid']) && !UuidTrait::isValidUuid($item['uuid'])) {
                $summary['errors'][] = sprintf('Invalid UUID format for %s', $event->getEntityName());
                break;
            }

            $existing = $this->entityManager->getRepository(Field::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][] = $existing->getLabel() ?? $existing->getAlias();
                $summary[EntityImportEvent::UPDATE]['uuids'][] = $existing->getUuid();
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['label'] ?? $item['alias'] ?? 'Unnamed field';
            }
        }

        foreach ($summary as $type => $data) {
            if ('errors' === $type) {
                if (count($data) > 0) {
                    $event->setSummary('errors', ['messages' => $data]);
                }
                break;
            }

            if (isset($data['names']) && count($data['names']) > 0) {
                $event->setSummary($type, [Field::ENTITY_NAME => $data]);
            }
        }
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details, string $object): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'form',
            'object'    => $object,
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
