<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Model\PointGroupModel;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GroupImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PointGroupModel $pointGroupModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
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
        ];

        $event->addEntity(Group::ENTITY_NAME, $pointGroupData);
    }

    public function onPointGroupImport(EntityImportEvent $event): void
    {
        if (Group::ENTITY_NAME !== $event->getEntityName()) {
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
                $output->writeln('User ID '.$userId.' not found. Point groups will not have a created_by_user field set.');
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
            $output->writeln('<info>Imported point group: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
