<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignEventImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class                 => ['onExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN_EVENT => ['onCampaignEventImport', 0],
        ];
    }

    public function onExport(EntityExportEvent $event): void
    {
        if (EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT !== $event->getEntityName()) {
            return;
        }

        $campaignId = $event->getEntityId();
        $campaign   = $this->campaignModel->getEntity($campaignId);
        if (!$campaign) {
            return;
        }

        $campaignEvents = $campaign->getEvents();
        $assets         = [];
        $pages          = [];
        $emails         = [];

        foreach ($campaignEvents as $campaignEvent) {
            $eventData  = $this->createEventData($campaign, $campaignEvent);
            $dependency = $this->createDependency($campaignId, $campaignEvent);

            $this->handleChannelExport($campaignEvent, $assets, $pages, $emails);

            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT, $eventData);
            $event->addDependencyEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT, $dependency);
        }

        $this->addEntitiesToEvent($event, $assets, $pages, $emails);
    }

    private function createEventData($campaign, $campaignEvent): array
    {
        $parentId = $campaignEvent->getParent()?->getId();

        return [
            'id'                    => $campaignEvent->getId(),
            'campaign_id'           => $campaign->getId(),
            'name'                  => $campaignEvent->getName(),
            'description'           => $campaignEvent->getDescription(),
            'type'                  => $campaignEvent->getType(),
            'event_type'            => $campaignEvent->getEventType(),
            'event_order'           => $campaignEvent->getOrder(),
            'properties'            => $campaignEvent->getProperties(),
            'trigger_interval'      => $campaignEvent->getTriggerInterval(),
            'trigger_interval_unit' => $campaignEvent->getTriggerIntervalUnit(),
            'trigger_mode'          => $campaignEvent->getTriggerMode(),
            'triggerDate'           => $campaignEvent->getTriggerDate()?->format(DATE_ATOM),
            'channel'               => $campaignEvent->getChannel(),
            'channel_id'            => $campaignEvent->getChannelId(),
            'parent_id'             => $parentId,
        ];
    }

    private function createDependency($campaignId, $campaignEvent): array
    {
        $channel   = $campaignEvent->getChannel() ?: 'channel';
        $channelId = $campaignEvent->getChannelId() ?: 0;

        return [
            EntityExportEvent::EXPORT_CAMPAIGN              => (int) $campaignId,
            EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT => (int) $campaignEvent->getId(),
            $channel                                        => (int) $channelId,
        ];
    }

    private function handleChannelExport($campaignEvent, &$assets, &$pages, &$emails): void
    {
        $channel   = $campaignEvent->getChannel();
        $channelId = $campaignEvent->getChannelId();

        if ($channel && $channelId) {
            $event = new EntityExportEvent($channel, $channelId);
            $this->dispatcher->dispatch($event);

            $this->collectEntities($event, EntityExportEvent::EXPORT_ASSET_EVENT, $assets);
            $this->collectEntities($event, EntityExportEvent::EXPORT_PAGE_EVENT, $pages);
            $this->collectEntities($event, EntityExportEvent::EXPORT_EMAIL_EVENT, $emails);
        }
    }

    private function collectEntities(EntityExportEvent $event, string $entityType, array &$collection): void
    {
        foreach ($event->getEntities()[$entityType] ?? [] as $entity) {
            $collection[$entity['id']] = $entity;
        }
    }

    private function addEntitiesToEvent(EntityExportEvent $event, array $assets, array $pages, array $emails): void
    {
        if (!empty($assets)) {
            $event->addEntities([EntityExportEvent::EXPORT_ASSET_EVENT => array_values($assets)]);
        }

        if (!empty($pages)) {
            $event->addEntities([EntityExportEvent::EXPORT_PAGE_EVENT => array_values($pages)]);
        }

        if (!empty($emails)) {
            $event->addEntities([EntityExportEvent::EXPORT_EMAIL_EVENT => array_values($emails)]);
        }
    }

    public function onCampaignEventImport(EntityImportEvent $event): void
    {
        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $campaignEvent = $this->createCampaignEvent($element);
            $this->entityManager->persist($campaignEvent);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $campaignEvent->getId());
            $output->writeln('<info>Imported campaign event: '.$campaignEvent->getName().' with ID: '.$campaignEvent->getId().'</info>');
        }

        $this->updateParentEvents($event);
    }

    private function createCampaignEvent(array $element): \Mautic\CampaignBundle\Entity\Event
    {
        $campaignEvent = new \Mautic\CampaignBundle\Entity\Event();
        $campaignEvent->setName($element['name'] ?? '');
        $campaignEvent->setDescription($element['description'] ?? '');
        $campaignEvent->setType($element['type'] ?? '');
        $campaignEvent->setEventType($element['event_type'] ?? '');
        $campaignEvent->setOrder($element['event_order'] ?? 0);
        $campaignEvent->setProperties($element['properties'] ?? []);
        $campaignEvent->setTriggerInterval($element['trigger_interval'] ?? 0);
        $campaignEvent->setTriggerIntervalUnit($element['trigger_interval_unit'] ?? '');
        $campaignEvent->setTriggerMode($element['trigger_mode'] ?? '');
        $campaignEvent->setTriggerDate(isset($element['triggerDate']) ? new \DateTime($element['triggerDate']) : null);
        $campaignEvent->setChannel($element['channel'] ?? '');
        $campaignEvent->setChannelId($element['channel_id'] ?? 0);

        $campaign = $this->campaignModel->getEntity($element['campaign_id']);
        $campaignEvent->setCampaign($campaign);

        return $campaignEvent;
    }

    private function updateParentEvents(EntityImportEvent $event): void
    {
        $idMap = $event->getEntityIdMap();
        foreach ($event->getEntityData() as $element) {
            if (isset($element['parent_id'])) {
                $originalParentId = (int) $element['parent_id'];
                $newParentId      = $idMap[$originalParentId] ?? null;

                if ($newParentId) {
                    $campaignEventId = $idMap[(int) $element['id']];
                    $campaignEvent   = $this->entityManager->getRepository(\Mautic\CampaignBundle\Entity\Event::class)->find($campaignEventId);
                    $parentEvent     = $this->entityManager->getRepository(\Mautic\CampaignBundle\Entity\Event::class)->find($newParentId);

                    if ($campaignEvent && $parentEvent) {
                        $campaignEvent->setParent($parentEvent);
                        $this->entityManager->persist($campaignEvent);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
