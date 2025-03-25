<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CustomFieldImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldModel $fieldModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onLeadFieldExport', 0],
            EntityImportEvent::class => ['onLeadFieldImport', 0],
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

        $log = [
            'bundle'    => 'lead',
            'object'    => 'leadField',
            'objectId'  => $leadField->getId(),
            'action'    => 'export',
            'details'   => $leadFieldData,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onLeadFieldImport(EntityImportEvent $event): void
    {
        if (LeadField::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $elements = $event->getEntityData();
        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            }
        }

        if (!$elements) {
            return;
        }

        $updateNames = [];
        $updateIds   = [];
        $newNames    = [];
        $newIds      = [];
        $updateCount = 0;
        $newCount    = 0;

        foreach ($elements as $element) {
            $existingObject = $this->entityManager->getRepository(LeadField::class)->findOneBy(['uuid' => $element['uuid']]);
            if ($existingObject) {
                // Update existing object
                $field = $existingObject;
                $field->setDateModified(new \DateTime());
                $status = EntityImportEvent::UPDATE;
            } else {
                // Create a new object
                $field = new LeadField();

                if ($userId) {
                    $field->setCreatedBy($userId);
                    $field->setCreatedByUser($userName);
                }

                $alias = $this->fieldModel->generateUniqueFieldAlias($element['alias']);
                $field->setAlias($alias);
                $field->setDateAdded(new \DateTime());
                $field->setUuid($element['uuid']);
                $status = EntityImportEvent::NEW;
            }

            $field->setIsPublished((bool) $element['is_published']);
            $field->setLabel($element['label']);
            $field->setType($element['type']);
            $field->setGroup($element['field_group']);
            $field->setDefaultValue($element['default_value'] ?? null);
            $field->setIsRequired((bool) ($element['is_required'] ?? false));
            $field->setIsFixed((bool) ($element['is_fixed'] ?? false));
            $field->setIsVisible((bool) ($element['is_visible'] ?? true));
            $field->setIsShortVisible((bool) ($element['is_short_visible'] ?? false));
            $field->setIsListable((bool) ($element['is_listable'] ?? false));
            $field->setIsPubliclyUpdatable((bool) ($element['is_publicly_updatable'] ?? false));
            $field->setIsUniqueIdentifier((bool) ($element['is_unique_identifier'] ?? false));
            $field->setIsIndex((bool) ($element['is_index'] ?? false));
            $field->setCharLengthLimit($element['char_length_limit'] ?? null);
            $field->setOrder($element['field_order'] ?? 0);
            $field->setObject($element['object']);
            $field->setProperties($element['properties'] ?? []);
            $field->setColumnIsNotCreated();

            $this->entityManager->persist($field);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $field->getId());
            if (EntityImportEvent::UPDATE === $status) {
                $updateNames[] = $field->getName();
                $updateIds[]   = $field->getId();
                ++$updateCount;
            } else {
                $newNames[] = $field->getName();
                $newIds[]   = $field->getId();
                ++$newCount;
            }
            $log = [
                'bundle'    => 'lead',
                'object'    => 'leadField',
                'objectId'  => $field->getId(),
                'action'    => 'import',
                'details'   => $element,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
        if ($newCount > 0) {
            $event->setStatus(EntityImportEvent::NEW, [
                LeadField::ENTITY_NAME => [
                    'names' => $newNames,
                    'ids'   => $newIds,
                    'count' => $newCount,
                ],
            ]);
        }
        if ($updateCount > 0) {
            $event->setStatus(EntityImportEvent::UPDATE, [
                LeadField::ENTITY_NAME => [
                    'names' => $updateNames,
                    'ids'   => $updateIds,
                    'count' => $updateCount,
                ],
            ]);
        }
    }
}
