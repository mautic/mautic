<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Model\PointModel;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PointImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PointModel $pointModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onPointExport', 0],
            EntityImportEvent::class => ['onPointImport', 0],
        ];
    }

    public function onPointExport(EntityExportEvent $event): void
    {
        if (Point::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $pointId = $event->getEntityId();
        $point   = $this->pointModel->getEntity($pointId);
        if (!$point) {
            return;
        }

        $pointData = [
            'id'                => $point->getId(),
            'group_id'          => $point->getGroup()?->getId(),
            'is_published'      => $point->isPublished(),
            'name'              => $point->getName(),
            'description'       => $point->getDescription(),
            'type'              => $point->getType(),
            'repeatable'        => $point->getRepeatable(),
            'delta'             => $point->getDelta(),
            'properties'        => $point->getProperties(),
            // 'uuid'              => $point->getUuid(),
        ];

        $event->addEntity(Point::ENTITY_NAME, $pointData);
    }

    public function onPointImport(EntityImportEvent $event): void
    {
        if (Point::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();
        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('User ID '.$userId.' not found. Points will not have a created_by_user field set.');
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $object = new Point();
            $object->setName($element['name']);
            $object->setIsPublished($element['is_published']);
            $object->setDescription($element['description'] ?? '');
            $object->setGroupId($element['group_id'] ?? null);
            $object->setCreatedByUser($userName);
            $object->setType($element['type'] ?? '');
            $object->setRepeatable($element['repeatable'] ?? false);
            $object->setDelta($element['delta'] ?? 0);
            $object->setProperties($element['properties'] ?? []);
            $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $output->writeln('<info>Imported point: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
