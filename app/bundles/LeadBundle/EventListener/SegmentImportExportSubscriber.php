<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SegmentImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ListModel $leadListModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private EventDispatcherInterface $dispatcher,
        private FieldModel $fieldModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onSegmentExport', 0],
            EntityImportEvent::class => ['onSegmentImport', 0],
        ];
    }

    public function onSegmentExport(EntityExportEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $leadListId = $event->getEntityId();
        $leadList   = $this->leadListModel->getEntity($leadListId);
        if (!$leadList) {
            return;
        }
        $segmentData = [
            'id'                   => $leadListId,
            'name'                 => $leadList->getName(),
            'is_published'         => $leadList->getIsPublished(),
            'description'          => $leadList->getDescription(),
            'alias'                => $leadList->getAlias(),
            'public_name'          => $leadList->getPublicName(),
            'filters'              => $leadList->getFilters(),
            'is_global'            => $leadList->getIsGlobal(),
            'is_preference_center' => $leadList->getIsPreferenceCenter(),
        ];
        $customFields   = $this->fieldModel->getLeadFieldCustomFields();
        $filters        = $leadList->getFilters();
        $data           = [];

        foreach ($filters as $filter) {
            if (isset($filter['object']) && in_array($filter['object'], ['lead', 'company'], true)) {
                foreach ($customFields as $field) {
                    if (isset($filter['field']) && $filter['field'] === $field->getAlias()) {
                        $subEvent = new EntityExportEvent(LeadField::ENTITY_NAME, (int) $field->getId());
                        $this->dispatcher->dispatch($subEvent);
                        $this->mergeExportData($data, $subEvent);

                        $event->addDependencyEntity(LeadList::ENTITY_NAME, [
                            LeadList::ENTITY_NAME   => (int) $leadListId,
                            LeadField::ENTITY_NAME  => (int) $field->getId(),
                        ]);
                    }
                }
            }
        }
        foreach ($data as $entityName => $entities) {
            $event->addEntities([$entityName => $entities]);
        }
        $event->addEntity(LeadList::ENTITY_NAME, $segmentData);
        $log = [
            'bundle'    => 'lead',
            'object'    => 'segment',
            'objectId'  => $leadListId,
            'action'    => 'export',
            'details'   => $segmentData,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Merge exported data avoiding duplicate entries.
     *
     * @param array<string, array> $data
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

    public function onSegmentImport(EntityImportEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $elements  = $event->getEntityData();
        $userId    = $event->getUserId();
        $userName  = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $object = new LeadList();
            $object->setName($element['name']);
            $object->setIsPublished((bool) $element['is_published']);
            $object->setDescription($element['description'] ?? '');
            $object->setAlias($element['alias'] ?? '');
            $object->setPublicName($element['public_name'] ?? '');
            $object->setFilters($element['filters'] ?? '');
            $object->setIsGlobal($element['is_global'] ?? false);
            $object->setIsPreferenceCenter($element['is_preference_center'] ?? false);
            $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());
            $object->setCreatedByUser($userName);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $log = [
                'bundle'    => 'lead',
                'object'    => 'segment',
                'objectId'  => $object->getId(),
                'action'    => 'import',
                'details'   => $element,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }
}
