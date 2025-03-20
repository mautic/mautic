<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
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
            EntityExportEvent::class => ['onPointGroupExport', 0],
            EntityImportEvent::class => ['onPointGroupImport', 0],
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

        foreach ($elements as $element) {
            $object = new Group();
            $object->setName($element['name']);
            $object->setDescription($element['description'] ?? '');
            $object->setIsPublished((bool) $element['is_published']);
            $object->setDateAdded(new \DateTime());
            $object->setCreatedByUser($userName);
            $object->setDateModified(new \DateTime());

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
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
    }
}
