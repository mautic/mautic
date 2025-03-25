<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class  => ['onFormExport', 0],
            EntityImportEvent::class  => ['onFormImport', 0],
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
        $log = [
            'bundle'    => 'form',
            'object'    => 'form',
            'objectId'  => $formId,
            'action'    => 'export',
            'details'   => $formData,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
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
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $forms    = $event->getEntityData();
        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            }
        }

        if (!$forms) {
            return;
        }
        $updateNames = [];
        $updateIds   = [];
        $newNames    = [];
        $newIds      = [];
        $updateCount = 0;
        $newCount    = 0;
        foreach ($forms as $formData) {
            $existingObject = $this->entityManager->getRepository(Form::class)->findOneBy(['uuid' => $formData['uuid']]);
            if ($existingObject) {
                // Update existing object
                $form = $existingObject;
                $form->setModifiedByUser($userName);
                $status = EntityImportEvent::UPDATE;
            } else {
                // Create a new object
                $form = new Form();
                $form->setDateAdded(new \DateTime());
                $form->setCreatedByUser($userName);
                $form->setUuid($formData['uuid']);
                $status = EntityImportEvent::NEW;
            }

            $form->setName($formData['name']);
            $form->setIsPublished((bool) $formData['is_published']);
            $form->setDescription($formData['description'] ?? '');
            $form->setAlias($formData['alias'] ?? '');
            $form->setLanguage($formData['lang'] ?? null);
            $form->setCachedHtml($formData['cached_html'] ?? '');
            $form->setPostAction($formData['post_action'] ?? '');
            $form->setPostActionProperty($formData['post_action_property'] ?? '');
            $form->setTemplate($formData['template'] ?? '');
            $form->setFormType($formData['form_type'] ?? '');
            $form->setRenderStyle($formData['render_style'] ?? '');
            $form->setFormAttributes($formData['form_attr'] ?? '');
            $form->setDateModified(new \DateTime());

            $this->entityManager->persist($form);
            $this->entityManager->flush();

            // Import form actions
            if (!empty($formData['form_actions'])) {
                foreach ($formData['form_actions'] as $actionData) {
                    $action = new \Mautic\FormBundle\Entity\Action();
                    $action->setForm($form);
                    $action->setName($actionData['name']);
                    $action->setDescription($actionData['description'] ?? '');
                    $action->setType($actionData['type']);
                    $action->setOrder($actionData['action_order'] ?? 0);
                    $action->setProperties($actionData['properties']);

                    $this->entityManager->persist($action);
                    $this->entityManager->flush();

                    $log = [
                        'bundle'    => 'form',
                        'object'    => 'formAction',
                        'objectId'  => $action->getId(),
                        'action'    => 'import',
                        'details'   => $actionData,
                        'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                    ];
                    $this->auditLogModel->writeToLog($log);
                }
            }

            // Import form fields
            if (!empty($formData['form_fields'])) {
                foreach ($formData['form_fields'] as $fieldData) {
                    $field = new \Mautic\FormBundle\Entity\Field();
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
                    // $field->setLeadField($fieldData['lead_field'] ?? '');
                    $field->setSaveResult((bool) $fieldData['save_result']);
                    $field->setIsAutoFill((bool) $fieldData['is_auto_fill'] ?? false);
                    $field->setIsReadOnly((bool) $fieldData['is_read_only']);
                    $field->setShowWhenValueExists((bool) $fieldData['show_when_value_exists']);
                    $field->setShowAfterXSubmissions($fieldData['show_after_x_submissions']);
                    $field->setAlwaysDisplay((bool) $fieldData['always_display']);
                    $field->setMappedObject($fieldData['mapped_object']);
                    $field->setMappedField($fieldData['mapped_field']);

                    $this->entityManager->persist($field);
                    $this->entityManager->flush();

                    $log = [
                        'bundle'    => 'form',
                        'object'    => 'formField',
                        'objectId'  => $field->getId(),
                        'action'    => 'import',
                        'details'   => $fieldData,
                        'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                    ];
                    $this->auditLogModel->writeToLog($log);
                }
            }

            $event->addEntityIdMap((int) $formData['id'], (int) $form->getId());
            if (EntityImportEvent::UPDATE === $status) {
                $updateNames[] = $form->getName();
                $updateIds[]   = $form->getId();
                ++$updateCount;
            } else {
                $newNames[] = $form->getName();
                $newIds[]   = $form->getId();
                ++$newCount;
            }
            $log = [
                'bundle'    => 'form',
                'object'    => 'form',
                'objectId'  => $form->getId(),
                'action'    => 'import',
                'details'   => $formData,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }

        if ($newCount > 0) {
            $event->setStatus(EntityImportEvent::NEW, [
                Form::ENTITY_NAME => [
                    'names' => $newNames,
                    'ids'   => $newIds,
                    'count' => $newCount,
                ],
            ]);
        }
        if ($updateCount > 0) {
            $event->setStatus(EntityImportEvent::UPDATE, [
                Form::ENTITY_NAME => [
                    'names' => $updateNames,
                    'ids'   => $updateIds,
                    'count' => $updateCount,
                ],
            ]);
        }
    }
}
