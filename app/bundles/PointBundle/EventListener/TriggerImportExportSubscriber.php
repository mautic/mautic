<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Model\TriggerModel;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TriggerImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TriggerModel $pointTriggerModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private CorePermissions $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onPointTriggerExport', 0],
            EntityImportEvent::class => ['onPointTriggerImport', 0],
        ];
    }

    public function onPointTriggerExport(EntityExportEvent $event): void
    {
        if (Trigger::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }
        if (!$this->security->isAdmin() && !$this->security->isGranted('point:triggers:view')) {
            $this->logger->error('Access denied: User lacks permission to read point triggers.');

            return;
        }

        $triggerId = $event->getEntityId();
        $trigger   = $this->pointTriggerModel->getEntity($triggerId);
        if (!$trigger) {
            return;
        }

        $triggerData = [
            'id'          => $trigger->getId(),
            'group_id'    => $trigger->getGroup()?->getID(),
            'is_published'=> $trigger->isPublished(),
            'name'        => $trigger->getName(),
            'description' => $trigger->getDescription(),
            'points'      => $trigger->getPoints(),
            'color'       => $trigger->getColor(),
            // 'uuid'        => $trigger->getUuid(),
        ];

        $event->addEntity(Trigger::ENTITY_NAME, $triggerData);
    }

    public function onPointTriggerImport(EntityImportEvent $event): void
    {
        if (Trigger::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }
        if (!$this->security->isAdmin() && !$this->security->isGranted('point:triggers:create')) {
            $this->logger->error('Access denied: User lacks permission to create point triggers.');

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
            $object = new Trigger();
            $object->setGroupId($element['group_id'] ?? null);
            $object->setIsPublished((bool) $element['is_published']);
            $object->setName($element['name']);
            $object->setDescription($element['description'] ?? '');
            $object->setPoints($element['points'] ?? 0);
            $object->setColor($element['color'] ?? '');
            $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());
            $object->setCreatedByUser($userName);

            // $object->setUuid($element['uuid'] ?? '');

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $output->writeln('<info>Imported point trigger: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
