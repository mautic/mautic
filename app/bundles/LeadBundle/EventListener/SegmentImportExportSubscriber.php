<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Security\Permissions\LeadPermissions;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SegmentImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ListModel $leadListModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private CorePermissions $security,
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
        if (!$this->security->isAdmin() && !$this->security->isGranted([LeadPermissions::LISTS_VIEW_OWN, LeadPermissions::LISTS_VIEW_OTHER], 'MATCH_ONE')) {
            $this->logger->error('Access denied: User lacks permission to read segments.');

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
        $event->addEntity(LeadList::ENTITY_NAME, $segmentData);
    }

    public function onSegmentImport(EntityImportEvent $event): void
    {
        if (LeadList::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        if (!$this->security->isAdmin() && !$this->security->isGranted(LeadPermissions::LISTS_CREATE)) {
            $this->logger->error('Access denied: User lacks permission to create segments.');

            return;
        }
        $output    = new ConsoleOutput();
        $elements  = $event->getEntityData();
        $userId    = $event->getUserId();
        $userName  = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.');
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
            $output->writeln('<info>Imported segment: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
