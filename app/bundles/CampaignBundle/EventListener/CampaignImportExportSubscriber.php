<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

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
            EntityExportEvent::class        => ['onCampaignExport', 0],
            EntityImportEvent::class        => ['onCampaignImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
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
        $this->logAction('export', $campaignId, []);

        $campaignEvent = new EntityExportEvent(Event::ENTITY_NAME, $campaignId);
        $campaignEvent = $this->dispatcher->dispatch($campaignEvent);
        $event->addEntities($campaignEvent->getEntities());
        $event->addDependencies($campaignEvent->getDependencies());

        $this->exportRelatedEntities($event, $campaignId);
        $event->addEntity('dependencies', $event->getDependencies());
    }

    public function onCampaignImport(EntityImportEvent $event): void
    {
        if (Campaign::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $userId   = $event->getUserId();
        $userName = $this->getUserName($userId);

        $entityData = $event->getEntityData();
        if (!$entityData) {
            $this->logger->warning('No entity data provided for import.');
            $event->setStatus(EntityImportEvent::ERRORS, ['message' => 'No entity data provided.']);

            return;
        }

        $this->importCampaigns($event, $entityData, $userName);
        $this->importDependentEntities($event, $entityData, $userId);
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
            foreach ($entities as $entityId => $label) {
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
     */
    private function importCampaigns(EntityImportEvent $event, array $entityData, string $user): void
    {
        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];
        $allowedTags = ['p', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'br', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        foreach ($entityData[Campaign::ENTITY_NAME] as $campaignData) {
            $object = $this->entityManager->getRepository(Campaign::class)->findOneBy(['uuid' => $campaignData['uuid']]);
            $isNew  = !$object;

            $object ??= new Campaign();
            $isNew && $object->setDateAdded(new \DateTime());
            $object->setUuid($campaignData['uuid']);
            $object->setDateModified(new \DateTime());

            if ($isNew) {
                $object->setCreatedByUser($user);
            } else {
                $object->setModifiedByUser($user);
            }

            $object->setName(InputHelper::stripTags($campaignData['name'] ?? '', $allowedTags));
            $object->setDescription(InputHelper::stripTags($campaignData['description'] ?? '', $allowedTags));
            $object->setIsPublished(false);
            $object->setCanvasSettings($campaignData['canvas_settings'] ?? '');

            $this->campaignModel->saveEntity($object);

            $event->addEntityIdMap((int) $campaignData['id'], $object->getId());
            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $object->getName();
            $stats[$status]['ids'][]   = $object->getId();
            ++$stats[$status]['count'];

            $campaignEvent = new CampaignEvent($object, $isNew);
            $this->dispatcher->dispatch($campaignEvent);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Campaign::ENTITY_NAME => $info]);
            }
        }
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        $this->performDuplicationCheck(
            $event,
            Campaign::ENTITY_NAME,
            Campaign::class,
            'name',
            $this->entityManager
        );
    }

    /**
     * Import Dependent Entities Dynamically and Track Progress.
     *
     * @param array<string, mixed> $entityData
     */
    private function importDependentEntities(EntityImportEvent $event, array $entityData, ?int $userId): void
    {
        $this->updateDependencies($entityData['dependencies'], $event->getEntityIdMap(), Campaign::ENTITY_NAME);

        foreach ($entityData as $entity => $data) {
            if (in_array($entity, ['dependencies', Campaign::ENTITY_NAME, Email::ENTITY_NAME, Event::ENTITY_NAME, Action::ENTITY_NAME, Field::ENTITY_NAME], true)) {
                continue;
            }
            $subEvent = new EntityImportEvent($entity, $data, $userId);
            $subEvent = $this->dispatcher->dispatch($subEvent);
            $this->mergeStatus($event, $subEvent);

            $this->logger->info('Imported dependent entity: '.$entity, ['entityIdMap' => $subEvent->getEntityIdMap()]);

            $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), $entity);
        }

        foreach ([Field::ENTITY_NAME, Action::ENTITY_NAME] as $entity) {
            if (!isset($entityData[$entity])) {
                continue;
            }
            $this->updateFormRelatedData($entityData, $entityData['dependencies'], $entity);

            $subEvent = new EntityImportEvent($entity, $entityData[$entity], $userId);
            $subEvent = $this->dispatcher->dispatch($subEvent);
            $this->mergeStatus($event, $subEvent);

            $this->logger->info('Imported dependent entity: '.$entity, ['entityIdMap' => $subEvent->getEntityIdMap()]);

            $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), $entity);
        }

        if (isset($entityData[Email::ENTITY_NAME])) {
            $this->updateEmails($entityData, $entityData['dependencies']);

            $emailEvent = new EntityImportEvent(Email::ENTITY_NAME, $entityData[Email::ENTITY_NAME], $userId);
            $emailEvent = $this->dispatcher->dispatch($emailEvent);
            $this->mergeStatus($event, $emailEvent);

            $this->logger->info('Imported dependent entity: '.Email::ENTITY_NAME, ['entityIdMap' => $emailEvent->getEntityIdMap()]);

            $this->updateDependencies($entityData['dependencies'], $emailEvent->getEntityIdMap(), Email::ENTITY_NAME);
        }

        $this->processDependencies($entityData['dependencies']);
        if (isset($entityData[Event::ENTITY_NAME])) {
            $this->updateEvents($entityData, $entityData['dependencies']);

            $campaignEvent  = new EntityImportEvent(Event::ENTITY_NAME, $entityData[Event::ENTITY_NAME], $userId);
            $campaignEvent  = $this->dispatcher->dispatch($campaignEvent);
            $this->mergeStatus($event, $campaignEvent);

            $this->updateCampaignCanvasSettings($entityData, $campaignEvent->getEntityIdMap(), $event->getEntityIdMap());
        }

        $this->logger->info('Final entity ID map after import: ', ['entityIdMap' => $event->getEntityIdMap()]);
    }

    private function mergeStatus(EntityImportEvent $mainEvent, EntityImportEvent $subEvent): void
    {
        $subStatus = $subEvent->getStatus();

        foreach ([EntityImportEvent::NEW, EntityImportEvent::UPDATE, EntityImportEvent::ERRORS] as $type) {
            if (!isset($subStatus[$type])) {
                continue;
            }

            $mainStatus = $mainEvent->getStatus();

            foreach ($subStatus[$type] as $entityName => $statusData) {
                if (!isset($mainStatus[$type][$entityName])) {
                    $mainStatus[$type][$entityName] = [
                        'names' => [],
                        'ids'   => [],
                        'count' => 0,
                    ];
                }

                $mainStatus[$type][$entityName]['names'] = array_merge(
                    $mainStatus[$type][$entityName]['names'],
                    $statusData['names'] ?? []
                );
                $mainStatus[$type][$entityName]['ids'] = array_merge(
                    $mainStatus[$type][$entityName]['ids'],
                    $statusData['ids'] ?? []
                );
                $mainStatus[$type][$entityName]['count'] += $statusData['count'] ?? 0;
            }

            $mainEvent->setStatus($type, $mainStatus[$type]);
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
        unset($campaignData);

        $this->persistUpdatedCanvasSettings($data, $campaignIdMap);
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
        unset($node);
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
        unset($connection);
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
                            unset($subKey);
                        } else {
                            // If it's a single value, update it normally
                            if (isset($idMap[$dependency[$key]])) {
                                $dependency[$key] = $idMap[$dependency[$key]];
                            }
                        }
                    }
                }
                unset($dependency);
            }
            unset($items);
        }
        unset($dependencyGroup);
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
                        }
                    }
                    unset($dependency);
                }
                if (LeadList::ENTITY_NAME === $key) {
                    foreach ($items as &$dependency) {
                        if (isset($dependency[Campaign::ENTITY_NAME])) {
                            $this->insertCampaignSegmentXref($dependency[Campaign::ENTITY_NAME], $dependency[LeadList::ENTITY_NAME]);
                        }
                    }
                    unset($dependency);
                }
            }
        }
        unset($dependencyGroup);
    }

    private function insertCampaignFormXref(int $campaignId, int $formId): void
    {
        $connection = $this->entityManager->getConnection();
        $tableName  = MAUTIC_TABLE_PREFIX.'campaign_form_xref';

        $exists = $connection->fetchOne(
            "SELECT 1 FROM {$tableName} WHERE campaign_id = :campaignId AND form_id = :formId",
            ['campaignId' => $campaignId, 'formId' => $formId]
        );

        if (!$exists) {
            $connection->insert($tableName, [
                'campaign_id' => $campaignId,
                'form_id'     => $formId,
            ]);
        }
        $this->logger->info("Inserted campaign_form_xref: campaign_id={$campaignId}, form_id={$formId}");
    }

    private function insertCampaignSegmentXref(int $campaignId, int $segmentId): void
    {
        $connection = $this->entityManager->getConnection();
        $tableName  = MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref';

        $exists = $connection->fetchOne(
            "SELECT 1 FROM {$tableName} WHERE campaign_id = :campaignId AND leadlist_id = :leadlistId",
            [
                'campaignId' => $campaignId,
                'leadlistId' => $segmentId,
            ]
        );

        if (!$exists) {
            $connection->insert($tableName, [
                'campaign_id'     => $campaignId,
                'leadlist_id'     => $segmentId,
            ]);
        }
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
        unset($event);
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
        unset($email);
    }

    /**
     * @param array<string, mixed>             $data
     * @param array<int, array<string, mixed>> $dependencies
     */
    private function updateFormRelatedData(array &$data, array $dependencies, string $entity): void
    {
        if (empty($data[Form::ENTITY_NAME])) {
            return;
        }

        $formDependencies = $this->getSubDependencies($dependencies, Form::ENTITY_NAME);
        if (empty($formDependencies)) {
            return;
        }

        foreach ($data[$entity] as &$item) {
            foreach ($formDependencies as $dependency) {
                if (isset($dependency[$entity]) && isset($item['id']) && $item['id'] === $dependency[$entity]) {
                    if (isset($item['form']) && isset($dependency[Form::ENTITY_NAME])) {
                        $item['form'] = $dependency[Form::ENTITY_NAME];
                    }
                }
            }
        }
        unset($item);
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

        switch ($eventType) {
            case 'lead.pageHit':
                $this->updateProperty($event, 'properties.page', $eventDependency, Page::ENTITY_NAME);
                break;
            case 'lead.changelist':
                $this->updateArrayProperty($event, 'properties.addToLists', $eventDependency, LeadList::ENTITY_NAME);
                $this->updateArrayProperty($event, 'properties.removeFromLists', $eventDependency, LeadList::ENTITY_NAME);
                break;
            case 'lead.segments':
                if (!empty($eventDependency['lists']) && is_array($eventDependency['lists'])) {
                    $this->setNestedValue($event, 'properties.segments', $eventDependency['lists']);
                }

                $this->updateArrayProperty($event, 'properties.segments', $eventDependency, LeadList::ENTITY_NAME);
                break;
            case 'form.submit':
                $this->updateArrayProperty($event, 'properties.forms', $eventDependency, Form::ENTITY_NAME);
                break;
            case 'email.send.to.user':
                $this->updateArrayProperty($event, 'properties.useremail.email', $eventDependency, Email::ENTITY_NAME);
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
            unset($id);
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

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Campaign::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Campaign::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, []);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
