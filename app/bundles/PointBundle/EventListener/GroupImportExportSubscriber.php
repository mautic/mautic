<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Model\PointGroupModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GroupImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PointGroupModel $pointGroupModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class     => ['onPointGroupExport', 0],
            EntityImportEvent::class     => ['onPointGroupImport', 0],
            EntityImportUndoEvent::class => ['onUndoImport', 0],
        ];
    }

    public function onPointGroupExport(EntityExportEvent $event): void
    {
        if (Group::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $pointGroupId = $event->getEntityId();
        $pointGroup   = $this->pointGroupModel->getEntity($pointGroupId);
        if (!$pointGroup) {
            return;
        }

        $pointGroupData = [
            'id'          => $pointGroup->getId(),
            'name'        => $pointGroup->getName(),
            'description' => $pointGroup->getDescription(),
            'is_published'=> $pointGroup->isPublished(),
            'uuid'        => $pointGroup->getUuid(),
        ];

        $event->addEntity(Group::ENTITY_NAME, $pointGroupData);
        $log = [
            'bundle'    => 'point',
            'object'    => 'pointGroup',
            'objectId'  => $pointGroup->getId(),
            'action'    => 'export',
            'details'   => $pointGroupData,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onPointGroupImport(EntityImportEvent $event): void
    {
        if (Group::ENTITY_NAME !== $event->getEntityName()) {
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
            $existingObject = $this->entityManager->getRepository(Group::class)->findOneBy(['uuid' => $element['uuid']]);
            if ($existingObject) {
                // Update existing object
                $object = $existingObject;
                $object->setModifiedByUser($userName);
                $status = EntityImportEvent::UPDATE;
            } else {
                // Create a new object
                $object = new Group();
                $object->setDateAdded(new \DateTime());
                $object->setUuid($element['uuid']);
                $object->setCreatedByUser($userName);
                $status = EntityImportEvent::NEW;
            }

            $object->setName($element['name']);
            $object->setDescription($element['description'] ?? '');
            $object->setIsPublished((bool) $element['is_published']);
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
                'bundle'    => 'point',
                'object'    => 'pointGroup',
                'objectId'  => $object->getId(),
                'action'    => 'import',
                'details'   => $element,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
        if ($newCount > 0) {
            $event->setStatus(EntityImportEvent::NEW, [
                Group::ENTITY_NAME => [
                    'names' => $newNames,
                    'ids'   => $newIds,
                    'count' => $newCount,
                ],
            ]);
        }
        if ($updateCount > 0) {
            $event->setStatus(EntityImportEvent::UPDATE, [
                Group::ENTITY_NAME => [
                    'names' => $updateNames,
                    'ids'   => $updateIds,
                    'count' => $updateCount,
                ],
            ]);
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Group::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Group::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                // Log the deletion
                $log = [
                    'bundle'    => 'point',
                    'object'    => 'group',
                    'objectId'  => $id,
                    'action'    => 'undo_import',
                    'details'   => ['deletedEntity' => Group::class],
                    'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                ];

                $this->auditLogModel->writeToLog($log);
            }
        }

        $this->entityManager->flush();
    }
}
