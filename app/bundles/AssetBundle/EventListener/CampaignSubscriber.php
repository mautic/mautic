<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Event\AssetLoadEvent;
use Mautic\AssetBundle\Form\Type\CampaignEventAssetDownloadType;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RealTimeExecutioner $realTimeExecutioner,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            AssetEvents::ASSET_ON_LOAD                => ['onAssetDownload', 0],
            AssetEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $trigger = [
            'label'          => 'mautic.asset.campaign.event.download',
            'description'    => 'mautic.asset.campaign.event.download_descr',
            'eventName'      => AssetEvents::ON_CAMPAIGN_TRIGGER_DECISION,
            'formType'       => CampaignEventAssetDownloadType::class,
            'channel'        => 'asset',
            'channelIdField' => 'assets',
        ];

        $event->addDecision('asset.download', $trigger);
    }

    /**
     * Trigger point actions for asset download.
     */
    public function onAssetDownload(AssetLoadEvent $event): void
    {
        $asset = $event->getRecord()->getAsset();

        if (null !== $asset) {
            $this->realTimeExecutioner->execute('asset.download', $asset, 'asset', $asset->getId());
        }
    }

    public function onCampaignTriggerDecision(CampaignExecutionEvent $event): void
    {
        $eventDetails = $event->getEventDetails();

        if (null == $eventDetails) {
            $event->setResult(true);

            return;
        }

        if (!$eventDetails instanceof Asset) {
            $event->setResult(false);

            return;
        }

        $assetId       = $eventDetails->getId();
        $limitToAssets = $event->getConfig()['assets'];

        if (!empty($limitToAssets) && !in_array($assetId, $limitToAssets)) {
            $event->setResult(false);

            return;
        }

        $event->setResult(true);
    }
}
