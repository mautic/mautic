<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_CAMPAIGN => ['onCampaignExport', 0],
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
            EntityExportEvent::EXPORT_EMAIL,
            EntityExportEvent::EXPORT_CAMPAIGN_SEGMENT,
            EntityExportEvent::EXPORT_CAMPAIGN_FORM,
        ];

        foreach ($dependentEntities as $entity) {
            $subEvent = new EntityExportEvent($entity, $campaignId);
            $subEvent = $this->dispatcher->dispatch($subEvent, $entity);

            $event->addEntities($subEvent->getEntities());
        }
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
