<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class           => ['onCampaignExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN => ['onCampaignImport', 0],
        ];
    }

    public function onCampaignExport(EntityExportEvent $event): void
    {
        try {
            if (Campaign::ENTITY_NAME !== $event->getEntityName()) {
                return;
            }

            $campaignId   = $event->getEntityId();
            $campaignData = $this->fetchCampaignData($campaignId);

            if (!$campaignData) {
                $this->logger->warning("Campaign data not found for ID: $campaignId");

                return;
            }

            $event->addEntity(Campaign::ENTITY_NAME, $campaignData);

            // $rawEvents = $this->campaignModel->getEventRepository()->getCampaignEvents($campaignId);
            // print_r($rawEvents);

            $campaignEvent = new EntityExportEvent('campaign_event', $campaignId);
            $campaignEvent = $this->dispatcher->dispatch($campaignEvent);
            $event->addEntities($campaignEvent->getEntities());
            $event->addDependencies($campaignEvent->getDependencies());

            $this->exportRelatedEntities($event, $campaignId);
            $event->addEntity('dependencies', $event->getDependencies());
        } catch (\Exception $e) {
            $this->logger->error('Error during campaign export: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    public function onCampaignImport(EntityImportEvent $event): void
    {
        try {
            $userId   = $event->getUserId();
            $userName = $this->getUserName($userId);

            $entityData = $event->getEntityData();
            if (!$entityData) {
                $this->logger->warning('No entity data provided for import.');

                return;
            }

            $this->importCampaigns($event, $entityData, $userName);
            $this->importDependentEntities($event, $entityData, $userId);
        } catch (\Exception $e) {
            $this->logger->error('Error during campaign import: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function fetchCampaignData(int $campaignId): array
    {
        try {
            $campaign = $this->campaignModel->getEntity($campaignId);
            if (!$campaign) {
                $this->logger->warning("Campaign not found for ID: $campaignId");

                return [];
            }

            return [
                'id'              => $campaign->getId(),
                'name'            => $campaign->getName(),
                'description'     => $campaign->getDescription(),
                'is_published'    => $campaign->getIsPublished(),
                'canvas_settings' => $campaign->getCanvasSettings(),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching campaign data: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function exportRelatedEntities(EntityExportEvent $event, int $campaignId): void
    {
        try {
            $campaignSources = $this->campaignModel->getLeadSources($campaignId);

            foreach ($campaignSources as $entityName => $entities) {
                foreach ($entities as $entityId => $entityLabel) {
                    $this->dispatchAndAddEntity($event, $entityName, (int) $entityId, [
                        Campaign::ENTITY_NAME => $campaignId,
                        $entityName           => (int) $entityId,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error exporting related entities: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function dispatchAndAddEntity(EntityExportEvent $event, string $type, int $entityId, array $dependency): void
    {
        try {
            $entityEvent = new EntityExportEvent($type, $entityId);
            $entityEvent = $this->dispatcher->dispatch($entityEvent);

            $event->addEntities($entityEvent->getEntities());
            $event->addDependencyEntity($type, $dependency);
        } catch (\Exception $e) {
            $this->logger->error('Error dispatching and adding entity: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function importCampaigns(EntityImportEvent $event, array $entityData, string $user): void
    {
        try {
            foreach ($entityData[EntityExportEvent::EXPORT_CAMPAIGN] as $campaignData) {
                $campaign = new Campaign();
                $campaign->setName($campaignData['name']);
                $campaign->setDescription($campaignData['description'] ?? '');
                $campaign->setIsPublished($campaignData['is_published'] ?? false);
                $campaign->setCanvasSettings($campaignData['canvas_settings'] ?? '');
                $campaign->setDateAdded(new \DateTime());
                $campaign->setDateModified(new \DateTime());
                $campaign->setCreatedByUser($user);

                $this->entityManager->persist($campaign);
                $this->entityManager->flush();

                $event->addEntityIdMap($campaignData['id'], $campaign->getId());
                $this->logger->info('Imported campaign: '.$campaign->getName().' with ID: '.$campaign->getId());
            }
        } catch (\Exception $e) {
            $this->logger->error('Error importing campaigns: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function importDependentEntities(EntityImportEvent $event, array $entityData, ?int $userId): void
    {
        try {
            $this->updateDependencies($entityData['dependencies'], $event->getEntityIdMap(), EntityExportEvent::EXPORT_CAMPAIGN);

            $dependentEntities = [
                EntityExportEvent::EXPORT_FORM_EVENT,
                EntityExportEvent::EXPORT_SEGMENT_EVENT,
                EntityExportEvent::EXPORT_ASSET_EVENT,
                EntityExportEvent::EXPORT_PAGE_EVENT,
            ];

            foreach ($dependentEntities as $entity) {
                $subEvent = new EntityImportEvent($entity, $entityData[$entity], $userId);
                $subEvent = $this->dispatcher->dispatch($subEvent);
                $this->logger->info('Imported dependent entity: '.$entity, ['entityIdMap' => $subEvent->getEntityIdMap()]);

                $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), $entity);
            }

            $this->processDependencies($entityData['dependencies']);
            $this->updateEvents($entityData, $entityData['dependencies']);

            $campaignEvent = new EntityImportEvent(EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT, $entityData[EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT], $userId);
            $campaignEvent = $this->dispatcher->dispatch($campaignEvent);

            $this->updateCampaignCanvasSettings($entityData, $campaignEvent->getEntityIdMap(), $event->getEntityIdMap());

            $this->logger->info('Final entity ID map after import: ', ['entityIdMap' => $event->getEntityIdMap()]);
        } catch (\Exception $e) {
            $this->logger->error('Error importing dependent entities: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function getUserName(?int $userId): string
    {
        if (!$userId) {
            return '';
        }

        $user = $this->userModel->getEntity($userId);
        if (!$user) {
            $this->logger->warning("User ID $userId not found. Campaigns will not have a created_by_user field set.");

            return '';
        }

        return $user->getFirstName().' '.$user->getLastName();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, int>      $eventIdMap
     * @param array<int, int>      $campaignIdMap
     */
    private function updateCampaignCanvasSettings(array &$data, array $eventIdMap, array $campaignIdMap): void
    {
        foreach ($data[EntityExportEvent::EXPORT_CAMPAIGN] as &$campaignData) {
            if (!empty($campaignData['canvas_settings'])) {
                $canvasSettings = &$campaignData['canvas_settings'];

                $this->updateCanvasNodes($canvasSettings, $eventIdMap);
                $this->updateCanvasConnections($canvasSettings, $eventIdMap);
            }
        }

        $this->persistUpdatedCanvasSettings($data, $campaignIdMap);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, int>      $campaignIdMap
     */
    private function persistUpdatedCanvasSettings(array &$data, array $campaignIdMap): void
    {
        foreach ($data[EntityExportEvent::EXPORT_CAMPAIGN] as $campaignData) {
            $campaign = $this->entityManager->getRepository(Campaign::class)->find($campaignIdMap[$campaignData['id']] ?? null);

            if ($campaign) {
                $campaign->setCanvasSettings($campaignData['canvas_settings'] ?? '');
                $this->entityManager->persist($campaign);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param array<string, mixed> $canvasSettings
     * @param array<int, int>      $eventIdMap
     */
    private function updateCanvasNodes(array &$canvasSettings, array $eventIdMap): void
    {
        if (!isset($canvasSettings['nodes'])) {
            return;
        }

        foreach ($canvasSettings['nodes'] as &$node) {
            if (isset($node['id']) && isset($eventIdMap[$node['id']])) {
                $node['id'] = $eventIdMap[$node['id']];
            }
        }
    }

    /**
     * @param array<string, mixed> $canvasSettings
     * @param array<int, int>      $eventIdMap
     */
    private function updateCanvasConnections(array &$canvasSettings, array $eventIdMap): void
    {
        if (!isset($canvasSettings['connections'])) {
            return;
        }

        foreach ($canvasSettings['connections'] as &$connection) {
            if (isset($connection['sourceId']) && isset($eventIdMap[$connection['sourceId']])) {
                $connection['sourceId'] = $eventIdMap[$connection['sourceId']];
            }
            if (isset($connection['targetId']) && isset($eventIdMap[$connection['targetId']])) {
                $connection['targetId'] = $eventIdMap[$connection['targetId']];
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>>     $dependencies
     * @param array<int, array<string, mixed>|int> $idMap
     */
    private function updateDependencies(array &$dependencies, array $idMap, string $key): void
    {
        foreach ($dependencies as &$dependencyGroup) {
            foreach ($dependencyGroup as &$items) {
                foreach ($items as &$dependency) {
                    if (isset($dependency[$key]) && isset($idMap[$dependency[$key]])) {
                        $dependency[$key] = $idMap[$dependency[$key]];
                    }
                }
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     */
    private function processDependencies(array $dependencies): void
    {
        foreach ($dependencies as &$dependencyGroup) {
            foreach ($dependencyGroup as $key => $items) {
                if (EntityExportEvent::EXPORT_FORM_EVENT === $key) {
                    foreach ($items as &$dependency) {
                        $this->insertCampaignFormXref($dependency[EntityExportEvent::EXPORT_CAMPAIGN], $dependency[EntityExportEvent::EXPORT_FORM_EVENT]);
                    }
                }
                if (EntityExportEvent::EXPORT_SEGMENT_EVENT === $key) {
                    foreach ($items as &$dependency) {
                        $this->insertCampaignSegmentXref($dependency[EntityExportEvent::EXPORT_CAMPAIGN], $dependency[EntityExportEvent::EXPORT_SEGMENT_EVENT]);
                    }
                }
            }
        }
    }

    private function insertCampaignFormXref(int $campaignId, int $formId): void
    {
        try {
            $this->entityManager->getConnection()->insert('campaign_form_xref', [
                'campaign_id' => $campaignId,
                'form_id'     => $formId,
            ]);

            $this->logger->info("Inserted campaign_form_xref: campaign_id={$campaignId}, form_id={$formId}");
        } catch (\Exception $e) {
            $this->logger->error('Failed to insert into campaign_form_xref: '.$e->getMessage());
        }
    }

    private function insertCampaignSegmentXref(int $campaignId, int $segmentId): void
    {
        try {
            $this->entityManager->getConnection()->insert('campaign_leadlist_xref', [
                'campaign_id' => $campaignId,
                'leadlist_id' => $segmentId,
            ]);

            $this->logger->info("Inserted campaign_leadlist_xref: campaign_id={$campaignId}, leadlist_id={$segmentId}");
        } catch (\Exception $e) {
            $this->logger->error('Failed to insert into campaign_leadlist_xref: '.$e->getMessage());
        }
    }

    /**
     * @param array<string, mixed>             $data
     * @param array<int, array<string, mixed>> $dependencies
     */
    private function updateEvents(array &$data, array $dependencies): void
    {
        if (empty($data[EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT])) {
            return;
        }

        $eventDependencies = $this->getEventDependencies($dependencies);
        if (empty($eventDependencies)) {
            return;
        }

        foreach ($data[EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT] as &$event) {
            foreach ($eventDependencies as $eventDependency) {
                if (isset($event['id']) && $event['id'] === $eventDependency[EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT]) {
                    $event['campaign_id'] = $eventDependency[EntityExportEvent::EXPORT_CAMPAIGN];
                    $this->updateEventChannel($event, $eventDependency);
                }
            }
        }
    }

    private function updateEventChannel(array &$event, array $eventDependency): void
    {
        if (isset($event['channel']) && isset($eventDependency[$event['channel']])) {
            $event['channel_id']                                      = $eventDependency[$event['channel']];
            $event['properties'][$event['channel'].'s']               = [$eventDependency[$event['channel']]];
            $event['properties']['properties'][$event['channel'].'s'] = [$eventDependency[$event['channel']]];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     *
     * @return array<int, array<string, mixed>>
     */
    private function getEventDependencies(array $dependencies): array
    {
        foreach ($dependencies as $dependencyGroup) {
            if (isset($dependencyGroup[EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT])) {
                return $dependencyGroup[EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT];
            }
        }

        return [];
    }
}
