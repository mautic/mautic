<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\StageEvents;

/**
 * Class StageSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class StageSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_ON_BUILD    => array('onStageBuild', 0),
            AssetEvents::ASSET_ON_DOWNLOAD => array('onAssetDownload', 0)
        );
    }

    /**
     * @param StageBuilderEvent $event
     */
    public function onStageBuild(StageBuilderEvent $event)
    {
        $action = array(
            'group'       => 'mautic.asset.actions',
            'label'       => 'mautic.asset.stage.action.download',
            'description' => 'mautic.asset.stage.action.download_descr',
            'callback'    => array('\\Mautic\\AssetBundle\\Helper\\StageActionHelper', 'validateAssetDownload'),
            'formType'    => 'stageaction_assetdownload'
        );

        $event->addAction('asset.download', $action);
    }

    /**
     * Trigger stage actions for asset download
     *
     * @param AssetEvent $event
     */
    public function onAssetDownload(AssetEvent $event)
    {
        $this->factory->getModel('stage')->triggerAction('asset.download', $event->getAsset());
    }
}