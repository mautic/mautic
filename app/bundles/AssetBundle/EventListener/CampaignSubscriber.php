<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class CampaignSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            AssetEvents::ASSET_ON_DOWNLOAD    => array('onAssetDownload', 0)
        );
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $trigger = array(
            'label'       => 'mautic.asset.campaign.trigger.download',
            'description' => 'mautic.asset.campaign.trigger.download_descr',
            'callback'    => array('\\Mautic\\AssetBundle\\Helper\\CampaignTriggerHelper', 'validateAssetDownloadTrigger'),
            'formType'    => 'campaigntrigger_assetdownload'
        );

        $event->addLeadDecision('asset.download', $trigger);
    }

    /**
     * Trigger point actions for asset download
     *
     * @param AssetEvent $event
     */
    public function onAssetDownload(AssetEvent $event)
    {
        $asset = $event->getAsset();
        $this->factory->getModel('campaign')->triggerEvent('asset.download', $asset, 'asset.download.'.$asset->getId());
    }
}