<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
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
        private CorePermissions $security,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onCampaignExport', 0],
            EntityImportEvent::class => ['onCampaignImport', 0],
        ];
    }

    public function onCampaignExport(EntityExportEvent $event): void
    {
        try {
            if (Campaign::ENTITY_NAME !== $event->getEntityName()) {
                return;
            }

            if (!$this->security->isAdmin() && !$this->security->isGranted('campaign:campaigns:view')) {
                $this->logger->error('Access denied: User lacks permission to read campaigns.');

                return;
            }

            $campaignId   = $event->getEntityId();
            $campaignData = $this->fetchCampaignData($campaignId);

            if (!$campaignData) {
                $this->logger->warning("Campaign data not found for ID: $campaignId");

                return;
            }

            $event->addEntity(Campaign::ENTITY_NAME, $campaignData);

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
            if (Campaign::ENTITY_NAME !== $event->getEntityName()) {
                return;
            }

            if (!$this->security->isAdmin() && !$this->security->isGranted('campaign:campaigns:create')) {
                $this->logger->error('Access denied: User lacks permission to create campaigns.');

                return;
            }
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param array<string, int> $dependency
     */
    private function dispatchAndAddEntity(EntityExportEvent $event, string $type, int $entityId, array $dependency): void
    {
        try {
            $entityEvent = new EntityExportEvent($type, $entityId);
            $entityEvent = $this->dispatcher->dispatch($entityEvent);

            $eventData = $event->getEntities();

            foreach ($entityEvent->getEntities() as $key => $values) {
                if (!isset($eventData[$key])) {
                    $event->addEntities($entityEvent->getEntities());
                } else {
                    $existingIds = array_column($values, 'id');

                    foreach ($eventData[$key] as $dataValue) {
                        if (!in_array($dataValue['id'], $existingIds)) {
                            $values[] = $dataValue;
                        }
                    }

                    $event->addEntities([$key => $values]);
                }
            }
            $event->addDependencyEntity($type, $dependency);
        } catch (\Exception $e) {
            $this->logger->error('Error dispatching and adding entity: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param array<string, array<string, mixed>> $entityData
     */
    private function importCampaigns(EntityImportEvent $event, array $entityData, string $user): void
    {
        try {
            foreach ($entityData[Campaign::ENTITY_NAME] as $campaignData) {
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

    /**
     * @param array<string, mixed> $entityData
     */
    private function importDependentEntities(EntityImportEvent $event, array $entityData, ?int $userId): void
    {
        try {
            $this->updateDependencies($entityData['dependencies'], $event->getEntityIdMap(), Campaign::ENTITY_NAME);

            $dependentEntities = [
                Form::ENTITY_NAME,
                LeadList::ENTITY_NAME,
                Asset::ENTITY_NAME,
                Page::ENTITY_NAME,
                DynamicContent::ENTITY_NAME,
            ];

            foreach ($dependentEntities as $entity) {
                if (!isset($entityData[$entity])) {
                    continue;
                }
                $subEvent = new EntityImportEvent($entity, $entityData[$entity], $userId);
                $subEvent = $this->dispatcher->dispatch($subEvent);
                $this->logger->info('Imported dependent entity: '.$entity, ['entityIdMap' => $subEvent->getEntityIdMap()]);

                $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), $entity);
            }

            if (isset($entityData[Email::ENTITY_NAME])) {
                $this->updateEmails($entityData, $entityData['dependencies']);

                $emailEvent = new EntityImportEvent(Email::ENTITY_NAME, $entityData[Email::ENTITY_NAME], $userId);
                $emailEvent = $this->dispatcher->dispatch($emailEvent);
                $this->logger->info('Imported dependent entity: '.Email::ENTITY_NAME, ['entityIdMap' => $emailEvent->getEntityIdMap()]);

                $this->updateDependencies($entityData['dependencies'], $emailEvent->getEntityIdMap(), Email::ENTITY_NAME);
            }

            $this->processDependencies($entityData['dependencies']);

            if (isset($entityData[Event::ENTITY_NAME])) {
                $this->updateEvents($entityData, $entityData['dependencies']);

                $campaignEvent = new EntityImportEvent(Event::ENTITY_NAME, $entityData[Event::ENTITY_NAME], $userId);
                $campaignEvent = $this->dispatcher->dispatch($campaignEvent);

                $this->updateCampaignCanvasSettings($entityData, $campaignEvent->getEntityIdMap(), $event->getEntityIdMap());
            }

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
        foreach ($data[Campaign::ENTITY_NAME] as &$campaignData) {
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
        foreach ($data[Campaign::ENTITY_NAME] as $campaignData) {
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
                if (Form::ENTITY_NAME === $key) {
                    foreach ($items as &$dependency) {
                        $this->insertCampaignFormXref($dependency[Campaign::ENTITY_NAME], $dependency[Form::ENTITY_NAME]);
                    }
                }
                if (LeadList::ENTITY_NAME === $key) {
                    foreach ($items as &$dependency) {
                        $this->insertCampaignSegmentXref($dependency[Campaign::ENTITY_NAME], $dependency[LeadList::ENTITY_NAME]);
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
        if (empty($data[Event::ENTITY_NAME])) {
            return;
        }

        $eventDependencies = $this->getSubDependencies($dependencies, Event::ENTITY_NAME);
        if (empty($eventDependencies)) {
            return;
        }

        foreach ($data[Event::ENTITY_NAME] as &$event) {
            foreach ($eventDependencies as $eventDependency) {
                if (isset($event['id']) && $event['id'] === $eventDependency[Event::ENTITY_NAME]) {
                    $event['campaign_id'] = $eventDependency[Campaign::ENTITY_NAME];
                    $this->updateEventChannel($event, $eventDependency);
                }
            }
        }
    }

    /**
     * @param array<string, mixed>             $data
     * @param array<int, array<string, mixed>> $dependencies
     */
    private function updateEmails(array &$data, array $dependencies): void
    {
        if (empty($data[Email::ENTITY_NAME])) {
            return;
        }

        $emailDependencies = $this->getSubDependencies($dependencies, Email::ENTITY_NAME);
        if (empty($emailDependencies)) {
            return;
        }

        foreach ($data[Email::ENTITY_NAME] as &$email) {
            foreach ($emailDependencies as $dependency) {
                if (isset($email['id']) && $email['id'] === $dependency[Email::ENTITY_NAME]) {
                    if (isset($email['unsubscribeform_id']) && isset($dependency[Form::ENTITY_NAME])) {
                        $email['unsubscribeform_id'] = $dependency[Form::ENTITY_NAME];
                    }
                    if (isset($email['preference_center_id']) && isset($dependency[Page::ENTITY_NAME])) {
                        $email['preference_center_id'] = $dependency[Page::ENTITY_NAME];
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $event
     * @param array<string, mixed> $eventDependency
     */
    private function updateEventChannel(array &$event, array $eventDependency): void
    {
        if (!isset($event['channel']) || !isset($eventDependency[$event['channel']])) {
            return;
        }

        $channelKey = $event['channel'];
        $channelId  = $eventDependency[$channelKey];

        $event['channel_id'] = $channelId;

        if (isset($event['properties'][$channelKey.'s'])) {
            $event['properties'][$channelKey.'s'] = [$channelId];
        }

        if (isset($event['properties'][$channelKey])) {
            $event['properties'][$channelKey] = $channelId;
        }

        if (isset($event['properties']['properties'][$channelKey.'s'])) {
            $event['properties']['properties'][$channelKey.'s'] = [$channelId];
        }

        if (isset($event['properties']['properties'][$channelKey])) {
            $event['properties']['properties'][$channelKey] = $channelId;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $dependencies
     * @param string                           $entity
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSubDependencies(array $dependencies, $entity): array
    {
        foreach ($dependencies as $dependencyGroup) {
            if (isset($dependencyGroup[$entity])) {
                return $dependencyGroup[$entity];
            }
        }

        return [];
    }
}
