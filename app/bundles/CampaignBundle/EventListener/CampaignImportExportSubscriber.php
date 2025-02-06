<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_CAMPAIGN => ['onCampaignExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN => ['onCampaignImport', 0],
        ];
    }

    public function onCampaignExport(EntityExportEvent $event): void
    {
        $campaignId = $event->getEntityId();
        $campaignData = $this->fetchCampaignData($campaignId);

        if (!$campaignData) {
            return;
        }

        $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN, $campaignData);

        $dependentEntities = [
            EntityExportEvent::EXPORT_CAMPAIGN_EVENT,
            EntityExportEvent::EXPORT_CAMPAIGN_SEGMENT,
            EntityExportEvent::EXPORT_CAMPAIGN_FORM,
        ];

        foreach ($dependentEntities as $entity) {
            $subEvent = new EntityExportEvent($entity, $campaignId);
            $subEvent = $this->dispatcher->dispatch($subEvent, $entity);

            $event->addEntities($subEvent->getEntities());
            $event->addDependencies($subEvent->getDependencies());
        }

        $event->addEntity('dependencies', $event->getDependencies());
    }

    public function onCampaignImport(EntityImportEvent $event): void
    {
        $userId = $event->getUserId();
        $user = $userId ? $this->userModel->getEntity($userId) : null;

        if ($userId && !$user) {
            print_r('<error>User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.</error>');
        }

        $entityData = $event->getEntityData();
        if (!$entityData) {
            return;
        }

        foreach ($entityData[EntityExportEvent::EXPORT_CAMPAIGN] as $campaignData) {
            $campaign = new Campaign();
            $campaign->setName($campaignData['name']);
            $campaign->setDescription($campaignData['description'] ?? '');
            $campaign->setIsPublished($campaignData['is_published'] ?? false);
            $campaign->setCanvasSettings($campaignData['canvas_settings'] ?? '');
            $campaign->setDateAdded(new \DateTime());
            $campaign->setDateModified(new \DateTime());

            if ($user) {
                $campaign->setCreatedByUser($user->getFirstName().' '.$user->getLastName());
            }

            $this->entityManager->persist($campaign);
            $this->entityManager->flush();

            $event->addEntityIdMap($campaignData['id'], $campaign->getId());
            print_r('<info>Imported campaign: '.$campaign->getName().' with ID: '.$campaign->getId().'</info>');
        }

        $dependentEntities = [
            EntityExportEvent::EXPORT_CAMPAIGN_FORM,
        ];

        foreach ($dependentEntities as $entity) {
            $subEvent = new EntityImportEvent($entity, $entityData[$entity], $userId);
            $subEvent = $this->dispatcher->dispatch($subEvent, 'import_' . $entity);
            print_r($subEvent->getEntityIdMap());
        }

        print_r($event->getEntityIdMap());
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCampaignData(int $campaignId): array
    {
        $campaign = $this->campaignModel->getEntity($campaignId);

        return [
            'id'              => $campaign->getId(),
            'name'            => $campaign->getName(),
            'description'     => $campaign->getDescription(),
            'is_published'    => $campaign->getIsPublished(),
            'canvas_settings' => $campaign->getCanvasSettings(),
        ];
    }
}
