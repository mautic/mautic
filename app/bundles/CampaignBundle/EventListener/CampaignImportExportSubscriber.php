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
            EntityExportEvent::EXPORT_CAMPAIGN => ['onCampaignExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN => ['onCampaignImport', 0],
        ];
    }

    public function onCampaignExport(EntityExportEvent $event): void
    {
        try {
            $campaignId   = $event->getEntityId();
            $campaignData = $this->fetchCampaignData($campaignId);

            if (!$campaignData) {
                $this->logger->warning('Campaign data not found for ID: '.$campaignId);

                return;
            }

            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN, $campaignData);

            // Export campaign events
            $campaignEvent = new EntityExportEvent(EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT, $campaignId);
            $campaignEvent = $this->dispatcher->dispatch($campaignEvent, EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT);
            $event->addEntities($campaignEvent->getEntities());
            $event->addDependencies($campaignEvent->getDependencies());

            // Export related entities (forms and segments)
            $this->exportRelatedEntities($event, $campaignId);
            $event->addEntity('dependencies', $event->getDependencies());
        } catch (\Exception $e) {
            $this->logger->error('Error during campaign export: '.$e->getMessage(), ['exception' => $e]);
            throw $e; // Re-throw to ensure the error is not silently ignored
        }
    }

    public function onCampaignImport(EntityImportEvent $event): void
    {
        try {
            $userId   = $event->getUserId();
            $userName = '';

            if ($userId) {
                $user   = $this->userModel->getEntity($userId);
                if ($user) {
                    $userName = $user->getFirstName().' '.$user->getLastName();
                } else {
                    $this->logger->warning('User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.');
                }
            }

            $entityData = $event->getEntityData();
            if (!$entityData) {
                $this->logger->warning('No entity data provided for import.');

                return;
            }

            $this->importCampaigns($event, $entityData, $userName);
            $this->importDependentEntities($event, $entityData, $userId);
        } catch (\Exception $e) {
            $this->logger->error('Error during campaign import: '.$e->getMessage(), ['exception' => $e]);
            throw $e; // Re-throw to ensure the error is not silently ignored
        }
    }

    private function fetchCampaignData(int $campaignId): array
    {
        try {
            $campaign = $this->campaignModel->getEntity($campaignId);
            if (!$campaign) {
                $this->logger->warning('Campaign not found for ID: '.$campaignId);

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
            $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
            $this->exportForms($event, $queryBuilder, $campaignId);
            $this->exportSegments($event, $queryBuilder, $campaignId);
        } catch (\Exception $e) {
            $this->logger->error('Error exporting related entities: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function exportForms(EntityExportEvent $event, $queryBuilder, int $campaignId): void
    {
        try {
            $formIds = $queryBuilder
                ->select('fl.form_id')
                ->from('campaign_form_xref', 'fl')
                ->innerJoin('fl', 'forms', 'ff', 'ff.id = fl.form_id AND ff.is_published = 1')
                ->where('fl.campaign_id = :campaignId')
                ->setParameter('campaignId', $campaignId, \Doctrine\DBAL\ParameterType::INTEGER)
                ->executeQuery()
                ->fetchFirstColumn();

            foreach ($formIds as $formId) {
                $this->dispatchAndAddEntity($event, EntityExportEvent::EXPORT_FORM_EVENT, (int) $formId, [
                    EntityExportEvent::EXPORT_CAMPAIGN       => $campaignId,
                    EntityExportEvent::EXPORT_FORM_EVENT     => (int) $formId,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error exporting forms: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function exportSegments(EntityExportEvent $event, $queryBuilder, int $campaignId): void
    {
        try {
            $segmentIds = $queryBuilder
                ->select('cl.leadlist_id')
                ->from('campaign_leadlist_xref', 'cl')
                ->innerJoin('cl', 'lead_lists', 'll', 'll.id = cl.leadlist_id AND ll.is_published = 1')
                ->where('cl.campaign_id = :campaignId')
                ->setParameter('campaignId', $campaignId, \Doctrine\DBAL\ParameterType::INTEGER)
                ->executeQuery()
                ->fetchFirstColumn();

            foreach ($segmentIds as $segmentId) {
                $this->dispatchAndAddEntity($event, EntityExportEvent::EXPORT_SEGMENT_EVENT, (int) $segmentId, [
                    EntityExportEvent::EXPORT_CAMPAIGN       => $campaignId,
                    EntityExportEvent::EXPORT_SEGMENT_EVENT  => (int) $segmentId,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error exporting segments: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    private function dispatchAndAddEntity(EntityExportEvent $event, string $type, int $entityId, array $dependency): void
    {
        try {
            $entityEvent = new EntityExportEvent($type, $entityId);
            $entityEvent = $this->dispatcher->dispatch($entityEvent, $type);

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
            $this->updateDependencies($entityData['dependencies'], $event->getEntityIdMap(), 'campaignId');
            print_r($event->getEntityIdMap());

            $subEvent = new EntityImportEvent(EntityExportEvent::EXPORT_FORM_EVENT, $entityData[EntityExportEvent::EXPORT_FORM_EVENT], $userId);
            $subEvent = $this->dispatcher->dispatch($subEvent, 'import_'.EntityExportEvent::EXPORT_FORM_EVENT);
            print_r($subEvent->getEntityIdMap());

            $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), 'formId');

            $subEvent = new EntityImportEvent(EntityExportEvent::EXPORT_SEGMENT_EVENT, $entityData[EntityExportEvent::EXPORT_SEGMENT_EVENT], $userId);
            $subEvent = $this->dispatcher->dispatch($subEvent, 'import_'.EntityExportEvent::EXPORT_SEGMENT_EVENT);
            print_r($subEvent->getEntityIdMap());
            $this->updateDependencies($entityData['dependencies'], $subEvent->getEntityIdMap(), 'segmentId');

            print_r($entityData['dependencies']);

            $this->processDependencies($entityData['dependencies']);

            // $dependentEntities = [
            //     EntityExportEvent::EXPORT_FORM_EVENT,
            //     EntityExportEvent::EXPORT_SEGMENT_EVENT,
            // ];

            // $eventeDependentEntities = [
            //     EntityExportEvent::EXPORT_ASSET_EVENT,
            //     EntityExportEvent::EXPORT_PAGE_EVENT,
            // ];

            // foreach ($dependentEntities as $entity) {
            //     $subEvent = new EntityImportEvent($entity, $entityData[$entity], $userId);
            //     $subEvent = $this->dispatcher->dispatch($subEvent, 'import_'.$entity);
            //     $this->logger->info('Imported dependent entity: '.$entity, ['entityIdMap' => $subEvent->getEntityIdMap()]);
            //     print_r($subEvent->getEntityIdMap());
            // }

            $this->logger->info('Final entity ID map after import: ', ['entityIdMap' => $event->getEntityIdMap()]);
        } catch (\Exception $e) {
            $this->logger->error('Error importing dependent entities: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
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
                        $originalId       = $dependency[$key];
                        $dependency[$key] = $idMap[$originalId];
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
                // Process form dependencies for campaign_form_xref
                if ('form' === $key) {
                    foreach ($items as &$dependency) {
                        $campaignId = $dependency['campaignId'];
                        $formId     = $dependency['formId'];

                        $this->insertCampaignFormXref($campaignId, $formId);
                    }
                }
                // Process segment dependencies for campaign_leadlist_xref
                if ('segment' === $key) {
                    foreach ($items as &$dependency) {
                        $campaignId = $dependency['campaignId'];
                        $segmentId  = $dependency['segmentId'];

                        $this->insertCampaignSegmentXref($campaignId, $segmentId);
                    }
                }
            }
        }
    }

    private function insertCampaignFormXref(int $campaignId, int $formId): void
    {
        try {
            $connection = $this->entityManager->getConnection();
            $connection->insert('campaign_form_xref', [
                'campaign_id' => $campaignId,
                'form_id'     => $formId,
            ]);

            $this->logger->info("<info>Inserted campaign_form_xref: campaign_id={$campaignId}, form_id={$formId}</info>");
        } catch (\Exception $e) {
            $this->logger->info('<error>Failed to insert into campaign_form_xref: '.$e->getMessage().'</error>');
        }
    }

    private function insertCampaignSegmentXref(int $campaignId, int $segmentId): void
    {
        try {
            $connection = $this->entityManager->getConnection();
            $connection->insert('campaign_leadlist_xref', [
                'campaign_id' => $campaignId,
                'leadlist_id' => $segmentId,
            ]);

            $this->logger->info("<info>Inserted campaign_leadlist_xref: campaign_id={$campaignId}, leadlist_id={$segmentId}</info>");
        } catch (\Exception $e) {
            $this->logger->info('<error>Failed to insert into campaign_leadlist_xref: '.$e->getMessage().'</error>');
        }
    }
}
