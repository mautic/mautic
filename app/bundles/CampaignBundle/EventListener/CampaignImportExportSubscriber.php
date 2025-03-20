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
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
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
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
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
        $log = [
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $campaignId,
            'action'    => 'export',
            'details'   => $campaignData,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);

        $campaignEvent = new EntityExportEvent('campaign_event', $campaignId);
        $campaignEvent = $this->dispatcher->dispatch($campaignEvent);
        $event->addEntities($campaignEvent->getEntities());
        $event->addDependencies($campaignEvent->getDependencies());

        $this->exportRelatedEntities($event, $campaignId);
        $event->addEntity('dependencies', $event->getDependencies());
    }

    public function onCampaignImport(EntityImportEvent $event): void
    {
        $importSummary = []; // Dynamic tracking of imported entities
        $errors        = [];

        if (Campaign::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $userId   = $event->getUserId();
        $userName = $this->getUserName($userId);

        $entityData = $event->getEntityData();
        if (!$entityData) {
            $this->logger->warning('No entity data provided for import.');
            $event->setArgument('import_status', ['error' => 'No entity data provided.']);

            return;
        }

        $this->importCampaigns($event, $entityData, $userName, $importSummary, $errors);
        $this->importDependentEntities($event, $entityData, $userId, $importSummary, $errors);

        $event->setArgument('import_status', [
            'summary' => $importSummary,
            'errors'  => $errors,
        ]);
        // $event->setArgument('import_status', ['error' => $e->getMessage()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCampaignData(int $campaignId): array
    {
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
            'uuid'            => $campaign->getUuid(),
        ];
    }

    private function exportRelatedEntities(EntityExportEvent $event, int $campaignId): void
    {
        $campaignSources = $this->campaignModel->getLeadSources($campaignId);

        foreach ($campaignSources as $entityName => $entities) {
            foreach ($entities as $entityId => $entityLabel) {
                $this->dispatchAndAddEntity($event, $entityName, (int) $entityId, [
                    Campaign::ENTITY_NAME => $campaignId,
                    $entityName           => (int) $entityId,
                ]);
            }
        }
    }

    /**
     * @param array<string, int> $dependency
     */
    private function dispatchAndAddEntity(EntityExportEvent $event, string $type, int $entityId, array $dependency): void
    {
        $entityEvent = new EntityExportEvent($type, $entityId);
        $entityEvent = $this->dispatcher->dispatch($entityEvent);

        $eventData = $event->getEntities();
        $event->addDependencies($entityEvent->getDependencies());

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
    }

    /**
     * Import Campaigns and track progress.
     *
     * @param array<string, array<string, mixed>> $entityData
     * @param array<string, int>                  &$importSummary
     * @param array<string>                       &$errors
     */
    private function importCampaigns(EntityImportEvent $event, array $entityData, string $user, array &$importSummary, array &$errors): void
    {
        foreach ($entityData[Campaign::ENTITY_NAME] as $campaignData) {
            $campaign = new Campaign();
            $campaign->setName($campaignData['name']);
            $campaign->setDescription($campaignData['description'] ?? '');
            $campaign->setIsPublished(false);
            $campaign->setCanvasSettings($campaignData['canvas_settings'] ?? '');
            $campaign->setDateAdded(new \DateTime());
            $campaign->setDateModified(new \DateTime());
            $campaign->setCreatedByUser($user);

            $this->entityManager->persist($campaign);
            $this->entityManager->flush();

            $event->addEntityIdMap($campaignData['id'], $campaign->getId());

            // Update import summary
            $importSummary[Campaign::ENTITY_NAME]['count']  = ($importSummary[Campaign::ENTITY_NAME]['count'] ?? 0) + 1;
            $importSummary[Campaign::ENTITY_NAME]['name'][] = $campaign->getName();
            $importSummary[Campaign::ENTITY_NAME]['id'][]   = $campaign->getId();

            $log = [
                'bundle'    => 'campaign',
                'object'    => 'campaign',
                'objectId'  => $campaign->getId(),
                'action'    => 'import',
                'details'   => $campaignData,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Import Dependent Entities Dynamically and Track Progress.
     *
     * @param array<string, mixed> $entityData
     * @param array<string, int>   &$importSummary
     * @param array<string>        &$errors
     */
    private function importDependentEntities(EntityImportEvent $event, array $entityData, ?int $userId, array &$importSummary, array &$errors): void
    {
        $this->updateDependencies($entityData['dependencies'], $event->getEntityIdMap(), Campaign::ENTITY_NAME);

        $dependentEntities = [
            Form::ENTITY_NAME,
            LeadList::ENTITY_NAME,
            Asset::ENTITY_NAME,
            Page::ENTITY_NAME,
            DynamicContent::ENTITY_NAME,
            Group::ENTITY_NAME,
            LeadField::ENTITY_NAME,
        ];

        foreach ($dependentEntities as $entity) {
            if (!isset($entityData[$entity])) {
                continue;
            }
            $subEvent = new EntityImportEvent($entity, $entityData[$entity], $userId);
            $subEvent = $this->dispatcher->dispatch($subEvent);
            $this->logger->info('Imported dependent entity: '.$entity, ['entityIdMap' => $subEvent->getEntityIdMap()]);
            $importSummary[$entity]['count'] = ($importSummary[$entity]['count'] ?? 0) + count($entityData[$entity]);
            foreach ($entityData[$entity] as $data) {
                $importSummary[$entity]['name'][] = $data['name'] ?? 'Unknown';
                $importSummary[$entity]['id'][]   = $data['id'] ?? null;
            }
            $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), $entity);
        }

        if (isset($entityData[Email::ENTITY_NAME])) {
            $this->updateEmails($entityData, $entityData['dependencies']);

            $emailEvent = new EntityImportEvent(Email::ENTITY_NAME, $entityData[Email::ENTITY_NAME], $userId);
            $emailEvent = $this->dispatcher->dispatch($emailEvent);
            $this->logger->info('Imported dependent entity: '.Email::ENTITY_NAME, ['entityIdMap' => $emailEvent->getEntityIdMap()]);
            $importSummary[Email::ENTITY_NAME]['count'] = ($importSummary[Email::ENTITY_NAME]['count'] ?? 0) + count($entityData[Email::ENTITY_NAME]);
            foreach ($entityData[Email::ENTITY_NAME] as $data) {
                $importSummary[Email::ENTITY_NAME]['name'][] = $data['name'] ?? 'Unknown';
                $importSummary[Email::ENTITY_NAME]['id'][]   = $data['id'] ?? null;
            }
            $this->updateDependencies($entityData['dependencies'], $emailEvent->getEntityIdMap(), Email::ENTITY_NAME);
        }

        $this->processDependencies($entityData['dependencies']);
        if (isset($entityData[Event::ENTITY_NAME])) {
            $this->updateEvents($entityData, $entityData['dependencies']);

            $campaignEvent                              = new EntityImportEvent(Event::ENTITY_NAME, $entityData[Event::ENTITY_NAME], $userId);
            $campaignEvent                              = $this->dispatcher->dispatch($campaignEvent);
            $importSummary[Event::ENTITY_NAME]['count'] = ($importSummary[Event::ENTITY_NAME]['count'] ?? 0) + count($entityData[Event::ENTITY_NAME]);
            foreach ($entityData[Event::ENTITY_NAME] as $data) {
                $importSummary[Event::ENTITY_NAME]['name'][] = $data['name'] ?? 'Unknown';
                $importSummary[Event::ENTITY_NAME]['id'][]   = $data['id'] ?? null;
            }

            $this->updateCampaignCanvasSettings($entityData, $campaignEvent->getEntityIdMap(), $event->getEntityIdMap());
        }

        $this->logger->info('Final entity ID map after import: ', ['entityIdMap' => $event->getEntityIdMap()]);
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
                    if (isset($dependency[$key])) {
                        // If the value is an array, update each element inside it
                        if (is_array($dependency[$key])) {
                            foreach ($dependency[$key] as &$subKey) {
                                if (isset($idMap[$subKey])) {
                                    $subKey = $idMap[$subKey];
                                }
                            }
                        } else {
                            // If it's a single value, update it normally
                            if (isset($idMap[$dependency[$key]])) {
                                $dependency[$key] = $idMap[$dependency[$key]];
                            }
                        }
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
                        if (isset($dependency[Campaign::ENTITY_NAME])) {
                            $this->insertCampaignFormXref($dependency[Campaign::ENTITY_NAME], $dependency[Form::ENTITY_NAME]);
                        } else {
                            // print_r($dependency[LeadField::ENTITY_NAME]);
                        }
                    }
                }
                if (LeadList::ENTITY_NAME === $key) {
                    foreach ($items as &$dependency) {
                        if (isset($dependency[Campaign::ENTITY_NAME])) {
                            $this->insertCampaignSegmentXref($dependency[Campaign::ENTITY_NAME], $dependency[LeadList::ENTITY_NAME]);
                        } else {
                            // print_r($dependency[LeadField::ENTITY_NAME]);
                        }
                    }
                }
            }
        }
    }

    private function insertCampaignFormXref(int $campaignId, int $formId): void
    {
        $this->entityManager->getConnection()->insert('campaign_form_xref', [
            'campaign_id' => $campaignId,
            'form_id'     => $formId,
        ]);

        $this->logger->info("Inserted campaign_form_xref: campaign_id={$campaignId}, form_id={$formId}");
    }

    private function insertCampaignSegmentXref(int $campaignId, int $segmentId): void
    {
        $this->entityManager->getConnection()->insert('campaign_leadlist_xref', [
            'campaign_id' => $campaignId,
            'leadlist_id' => $segmentId,
        ]);

        $this->logger->info("Inserted campaign_leadlist_xref: campaign_id={$campaignId}, leadlist_id={$segmentId}");
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
        if (!empty($event['channel']) && isset($eventDependency[$event['channel']])) {
            $channelKey = $event['channel'];
            $channelId  = $eventDependency[$channelKey];

            $event['channel_id'] = $channelId;
            $this->updateChannelProperties($event, $channelKey, $channelId);
        } else {
            $this->processNonChannelEvent($event, $eventDependency);
        }
    }

    /**
     * Correctly updates channel properties, considering both array and non-array values.
     *
     * @param array<string, mixed> $event
     */
    private function updateChannelProperties(array &$event, string $channelKey, int $channelId): void
    {
        // Define the possible locations where the channel ID may be stored
        $propertyPaths = [
            "properties.$channelKey",
            "properties.{$channelKey}s",
            "properties.properties.$channelKey",
            "properties.properties.{$channelKey}s",
        ];

        foreach ($propertyPaths as $path) {
            $existingValue = $this->getNestedValue($event, $path);

            if (!is_null($existingValue)) {
                if (is_array($existingValue)) {
                    // If the existing value is an array, replace it with a single-element array
                    $this->setNestedValue($event, $path, [$channelId]);
                } else {
                    // If it's a single value, replace it directly
                    $this->setNestedValue($event, $path, $channelId);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $event
     * @param array<string, mixed> $eventDependency
     */
    private function processNonChannelEvent(array &$event, array $eventDependency): void
    {
        $eventType  = $event['type'] ?? null;
        // $properties = $event['properties'] ?? [];

        switch ($eventType) {
            case 'lead.pageHit':
                $this->updateProperty($event, 'properties.page', $eventDependency, Page::ENTITY_NAME);
                break;
            case 'lead.changelist':
                $this->updateArrayProperty($event, 'properties.addToLists', $eventDependency, LeadList::ENTITY_NAME);
                $this->updateArrayProperty($event, 'properties.removeFromLists', $eventDependency, LeadList::ENTITY_NAME);
                break;
            case 'lead.segments':
                $this->updateArrayProperty($event, 'properties.segments', $eventDependency, LeadList::ENTITY_NAME);
                break;
            case 'form.submit':
                $this->updateArrayProperty($event, 'properties.forms', $eventDependency, Form::ENTITY_NAME);
                break;
            case 'lead.changepoints':
            case 'lead.points':
                $this->updateProperty($event, 'properties.group', $eventDependency, Group::ENTITY_NAME);
                break;
        }
    }

    /**
     * Update a single property if it exists and is a valid reference.
     *
     * @param array<string, mixed> $event
     * @param array<string, mixed> $eventDependency
     */
    private function updateProperty(array &$event, string $propertyPath, array $eventDependency, string $entityName): void
    {
        $propertyValue = $this->getNestedValue($event, $propertyPath);
        if (!empty($propertyValue) && isset($eventDependency[$entityName][$propertyValue])) {
            $this->setNestedValue($event, $propertyPath, $eventDependency[$entityName][$propertyValue]);
        }
    }

    /**
     * Update an array property if it exists and contains valid references.
     *
     * @param array<string, mixed> $event
     * @param array<string, mixed> $eventDependency
     */
    private function updateArrayProperty(array &$event, string $propertyPath, array $eventDependency, string $entityName): void
    {
        $propertyValue = $this->getNestedValue($event, $propertyPath);
        if (!empty($propertyValue) && is_array($propertyValue)) {
            foreach ($propertyValue as &$id) {
                if (isset($eventDependency[$entityName][$id])) {
                    $id = $eventDependency[$entityName][$id];
                }
            }
            $this->setNestedValue($event, $propertyPath, $propertyValue);
        }
    }

    /**
     * Retrieve a nested array value using dot notation.
     *
     * @param array<string, mixed> $array
     */
    private function getNestedValue(array &$array, string $path): mixed
    {
        $keys = explode('.', $path);
        $temp = &$array;

        foreach ($keys as $key) {
            if (!isset($temp[$key])) {
                return null;
            }
            $temp = &$temp[$key];
        }

        return $temp;
    }

    /**
     * Set a nested array value using dot notation.
     *
     * @param array<string, mixed> &$array
     */
    private function setNestedValue(array &$array, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $temp = &$array;

        foreach ($keys as $key) {
            if (!isset($temp[$key]) || !is_array($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }

        $temp = $value;
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
