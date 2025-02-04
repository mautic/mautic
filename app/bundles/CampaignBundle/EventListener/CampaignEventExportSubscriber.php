<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignEventExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
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
            $dependency = [
                'campaignId' => (int) $campaignId,
                'eventId'    => (int) $campaignEvent->getId(),
            ];
            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $eventData);
            $event->addDependencyEntity(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $dependency);
        }
    }
}
