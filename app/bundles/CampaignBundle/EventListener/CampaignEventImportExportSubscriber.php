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
            EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT  => ['onCampaignEventExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN_EVENT         => ['onCampaignEventImport', 0],
        ];
    }

    public function onCampaignEventExport(EntityExportEvent $event): void
    {
        $campaignId = $event->getEntityId();
        $campaign   = $this->campaignModel->getEntity($campaignId);
        if (!$campaign) {
            return;
        }

        $campaignEvents    = $campaign->getEvents();
        $eventData         = [];
        $assets            = [];
        $pages             = [];

        foreach ($campaignEvents as $campaignEvent) {
            $parent   = $campaignEvent->getParent();
            $parentId = $parent ? $parent->getId() : null;

            $eventData = [
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
                'triggerDate'           => $campaignEvent->getTriggerDate() ? $campaignEvent->getTriggerDate()->format(DATE_ATOM) : null,
                'channel'               => $campaignEvent->getChannel(),
                'channel_id'            => $campaignEvent->getChannelId(),
                'parent_id'             => $parentId,
            ];

            $channel    = $campaignEvent->getChannel() ? $campaignEvent->getChannel() : 'channel';
            $channelId  = $campaignEvent->getChannelId() ? $campaignEvent->getChannelId() : 0;
            $dependency = [
                EntityExportEvent::EXPORT_CAMPAIGN                  => (int) $campaignId,
                EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT     => (int) $campaignEvent->getId(),
                $channel                                            => (int) $channelId,
            ];

            if (EntityExportEvent::EXPORT_ASSET_EVENT === $channel && 0 !== (int) $channelId) {
                $assetEvent = new EntityExportEvent(EntityExportEvent::EXPORT_ASSET_EVENT, $channelId);
                $this->dispatcher->dispatch($assetEvent, EntityExportEvent::EXPORT_ASSET_EVENT);

                foreach ($assetEvent->getEntities()[EntityExportEvent::EXPORT_ASSET_EVENT] ?? [] as $asset) {
                    $assets[$asset['id']] = $asset;
                }
            }

            if (EntityExportEvent::EXPORT_PAGE_EVENT === $channel && 0 !== (int) $channelId) {
                $pageEvent = new EntityExportEvent(EntityExportEvent::EXPORT_PAGE_EVENT, $channelId);
                $this->dispatcher->dispatch($pageEvent, EntityExportEvent::EXPORT_PAGE_EVENT);

                foreach ($pageEvent->getEntities()[EntityExportEvent::EXPORT_PAGE_EVENT] ?? [] as $page) {
                    $pages[$page['id']] = $page;
                }
            }

            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT, $eventData);
            $event->addDependencyEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENTS_EVENT, $dependency);
        }

        if (!empty($assets)) {
            $event->addEntities([EntityExportEvent::EXPORT_ASSET_EVENT => array_values($assets)]); // Convert unique assets to list
        }

        if (!empty($pages)) {
            $event->addEntities([EntityExportEvent::EXPORT_PAGE_EVENT => array_values($pages)]); // Convert unique pages to list
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

            $this->entityManager->persist($campaignEvent);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $campaignEvent->getId());
            $output->writeln('<info>Imported campaign event: '.$campaignEvent->getName().' with ID: '.$campaignEvent->getId().'</info>');
        }

        $idMap = $event->getEntityIdMap();
        foreach ($elements as $element) {
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
