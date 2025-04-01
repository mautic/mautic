<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FormImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormModel $formModel,
        private UserModel $userModel,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private FieldModel $fieldModel,
        private EventDispatcherInterface $dispatcher,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onFormExport', 0],
            EntityImportEvent::class        => ['onFormImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onFormExport(EntityExportEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $formId       = $event->getEntityId();
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $form = $this->formModel->getEntity($formId);
        if (!$form) {
            return;
        }

        $formActions = $queryBuilder
            ->select('action.name, action.description, action.type, action.action_order, action.properties, action.uuid')
            ->from('form_actions', 'action')
            ->where('action.form_id = :formId')
            ->setParameter('formId', $formId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $formFields = $queryBuilder
            ->select('field.id, field.label, field.show_label, field.alias, field.type, field.is_custom, field.custom_parameters, field.default_value, field.is_required, field.validation_message, field.help_message, field.field_order, field.properties, field.validation, field.parent_id, field.conditions, field.label_attr, field.input_attr, field.container_attr, field.lead_field, field.save_result, field.is_auto_fill, field.is_read_only, field.show_when_value_exists, field.show_after_x_submissions, field.always_display, field.mapped_object, field.mapped_field, field.uuid')
            ->from('form_fields', 'field')
            ->where('field.form_id = :formId')
            ->setParameter('formId', $formId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $formData = [
            'id'                   => $formId,
            'name'                 => $form->getName(),
            'is_published'         => $form->isPublished(),
            'description'          => $form->getDescription(),
            'alias'                => $form->getAlias(),
            'lang'                 => $form->getLanguage(),
            'cached_html'          => $form->getCachedHtml(),
            'post_action'          => $form->getPostAction(),
            'template'             => $form->getTemplate(),
            'form_type'            => $form->getFormType(),
            'render_style'         => $form->getRenderStyle(),
            'post_action_property' => $form->getPostActionProperty(),
            'form_attr'            => $form->getFormAttributes(),
            'uuid'                 => $form->getUuid(),
            'form_actions'         => $formActions,
            'form_fields'          => $formFields,
        ];
        $customFields   = $this->fieldModel->getLeadFieldCustomFields();
        $data           = [];

        foreach ($formFields as $formField) {
            if (isset($formField['mapped_object']) && in_array($formField['mapped_object'], ['contact', 'company'], true)) {
                foreach ($customFields as $field) {
                    if (isset($formField['mapped_field']) && $formField['mapped_field'] === $field->getAlias()) {
                        $subEvent = new EntityExportEvent(LeadField::ENTITY_NAME, (int) $field->getId());
                        $this->dispatcher->dispatch($subEvent);
                        $this->mergeExportData($data, $subEvent);

                        $event->addDependencyEntity(Form::ENTITY_NAME, [
                            Form::ENTITY_NAME       => (int) $formId,
                            LeadField::ENTITY_NAME  => (int) $field->getId(),
                        ]);
                    }
                }
            }
        }
        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }

        $event->addEntity(Form::ENTITY_NAME, $formData);
        $this->logAction('export', $formId, $formData, 'form');
    }

    /**
     * Merge exported data avoiding duplicate entries.
     *
     * @param array<string, array<mixed>> $data
     */
    private function mergeExportData(array &$data, EntityExportEvent $subEvent): void
    {
        foreach ($subEvent->getEntities() as $key => $values) {
            if (!isset($data[$key])) {
                $data[$key] = $values;
            } else {
                $existingIds = array_column($data[$key], 'id');
                $data[$key]  = array_merge($data[$key], array_filter($values, function ($value) use ($existingIds) {
                    return !in_array($value['id'], $existingIds);
                }));
            }
        }
    }

    public function onFormImport(EntityImportEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $formData) {
            $form  = $this->entityManager->getRepository(Form::class)->findOneBy(['uuid' => $formData['uuid']]);
            $isNew = !$form;

            $form ??= new Form();
            $this->serializer->denormalize(
                $formData,
                Form::class,
                null,
                ['object_to_populate' => $form]
            );
            $this->entityManager->persist($form);
            $this->entityManager->flush();

            if (!$isNew) {
                $this->removeExistingFormActions($form);
                $this->removeExistingFormFields($form);
            }

            // Import actions
            foreach ($formData['form_actions'] ?? [] as $actionData) {
                $action = new Action();
                $action->setForm($form);
                $action->setName($actionData['name']);
                $action->setDescription($actionData['description'] ?? '');
                $action->setType($actionData['type']);
                $action->setOrder($actionData['action_order'] ?? 0);
                $action->setProperties($actionData['properties']);
                $this->entityManager->persist($action);
                $this->entityManager->flush();
                $this->logAction('import', $action->getId(), $actionData, 'formAction');
            }

            // Import fields
            foreach ($formData['form_fields'] ?? [] as $fieldData) {
                $field = new Field();
                $field->setForm($form);
                $field->setLabel($fieldData['label'] ?? '');
                $field->setShowLabel((bool) $fieldData['show_label']);
                $field->setAlias($fieldData['alias'] ?? '');
                $field->setType($fieldData['type'] ?? '');
                $field->setIsCustom((bool) $fieldData['is_custom']);
                $field->setCustomParameters($fieldData['custom_parameters']);
                $field->setDefaultValue($fieldData['default_value'] ?? '');
                $field->setIsRequired((bool) $fieldData['is_required']);
                $field->setValidationMessage($fieldData['validation_message'] ?? '');
                $field->setHelpMessage($fieldData['help_message'] ?? '');
                $field->setOrder($fieldData['field_order'] ?? 0);
                $field->setProperties($fieldData['properties']);
                $field->setValidation($fieldData['validation']);
                $field->setConditions($fieldData['conditions']);
                $field->setLabelAttributes($fieldData['label_attr']);
                $field->setInputAttributes($fieldData['input_attr']);
                $field->setContainerAttributes($fieldData['container_attr']);
                $field->setSaveResult((bool) $fieldData['save_result']);
                $field->setIsAutoFill((bool) $fieldData['is_auto_fill']);
                $field->setIsReadOnly((bool) $fieldData['is_read_only']);
                $field->setShowWhenValueExists((bool) $fieldData['show_when_value_exists']);
                $field->setShowAfterXSubmissions($fieldData['show_after_x_submissions']);
                $field->setAlwaysDisplay((bool) $fieldData['always_display']);
                $field->setMappedObject($fieldData['mapped_object']);
                $field->setMappedField($fieldData['mapped_field']);
                $this->entityManager->persist($field);
                $this->entityManager->flush();
                $this->logAction('import', $field->getId(), $fieldData, 'formField');
            }

            $event->addEntityIdMap((int) $formData['id'], $form->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $form->getName();
            $stats[$status]['ids'][]   = $form->getId();
            ++$stats[$status]['count'];
            $this->logAction('import', $form->getId(), $formData, 'form');
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Form::ENTITY_NAME => $info]);
            }
        }
    }

    private function removeExistingFormActions(Form $form): void
    {
        $actions = $this->entityManager
            ->getRepository(Action::class)
            ->findBy(['form' => $form]);

        foreach ($actions as $action) {
            $this->entityManager->remove($action);
        }

        $this->entityManager->flush();
    }

    private function removeExistingFormFields(Form $form): void
    {
        $fields = $this->entityManager
            ->getRepository(Field::class)
            ->findBy(['form' => $form]);

        foreach ($fields as $field) {
            $this->entityManager->remove($field);
        }

        $this->entityManager->flush();
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Form::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Form::class], 'form');
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'uuids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $item) {
            $existing = $this->entityManager->getRepository(Form::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][]   = $existing->getName();
                $summary[EntityImportEvent::UPDATE]['uuids'][]   = $existing->getUuid();
                ++$summary[EntityImportEvent::UPDATE]['count'];
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['name'];
                ++$summary[EntityImportEvent::NEW]['count'];
            }
        }

        foreach ($summary as $type => $data) {
            if ($data['count'] > 0) {
                $event->setSummary($type, [Form::ENTITY_NAME => $data]);
            }
        }
    }

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
