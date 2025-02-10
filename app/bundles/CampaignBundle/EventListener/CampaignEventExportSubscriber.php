<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignEventExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_CAMPAIGN_EVENT => ['onCampaignEventExport', 0],
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
                'campaignId' => (int) $campaignId,
                'eventId'    => (int) $campaignEvent->getId(),
                $channel     => $channelId,
            ];

            if (EntityExportEvent::EXPORT_ASSET === $channel && 0 !== (int) $channelId) {
                $assetEvent = new EntityExportEvent(EntityExportEvent::EXPORT_ASSET, $channelId);
                $this->dispatcher->dispatch($assetEvent, EntityExportEvent::EXPORT_ASSET);

                foreach ($assetEvent->getEntities()[EntityExportEvent::EXPORT_ASSET] ?? [] as $asset) {
                    $assets[$asset['id']] = $asset;
                }
            }

            if (EntityExportEvent::EXPORT_PAGE === $channel && 0 !== (int) $channelId) {
                $pageEvent = new EntityExportEvent(EntityExportEvent::EXPORT_PAGE, $channelId);
                $this->dispatcher->dispatch($pageEvent, EntityExportEvent::EXPORT_PAGE);

                foreach ($pageEvent->getEntities()[EntityExportEvent::EXPORT_PAGE] ?? [] as $page) {
                    $pages[$page['id']] = $page;
                }
            }

            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $eventData);
            $event->addDependencyEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $dependency);
        }

        if (!empty($assets)) {
            $event->addEntities([EntityExportEvent::EXPORT_ASSET => array_values($assets)]); // Convert unique assets to list
        }

        if (!empty($pages)) {
            $event->addEntities([EntityExportEvent::EXPORT_PAGE => array_values($pages)]); // Convert unique pages to list
        }
    }
}
