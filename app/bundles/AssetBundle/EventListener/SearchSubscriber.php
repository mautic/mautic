<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var AssetModel
     */
    protected $assetModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param AssetModel $assetModel
     */
    public function __construct(AssetModel $assetModel)
    {
        $this->assetModel = $assetModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $filter     = array("string" => $str, "force" => array());

        $permissions = $this->security->isGranted(
            array('asset:assets:viewown', 'asset:assets:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['asset:assets:viewown'] || $permissions['asset:assets:viewother']) {
            if (!$permissions['asset:assets:viewother']) {
                $filter['force'][] = array(
                    'column' => 'IDENTITY(a.createdBy)',
                    'expr'   => 'eq',
                    'value'  => $this->factory->getUser()->getId()
                );
            }

            $assets = $this->assetModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($assets) > 0) {
                $assetResults = array();

                foreach ($assets as $asset) {
                    $assetResults[] = $this->templating->renderResponse(
                        'MauticAssetBundle:SubscribedEvents\Search:global.html.php',
                        array('asset' => $asset)
                    )->getContent();
                }
                if (count($assets) > 5) {
                    $assetResults[] = $this->templating->renderResponse(
                        'MauticAssetBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($assets) - 5)
                        )
                    )->getContent();
                }
                $assetResults['count'] = count($assets);
                $event->addResults('mautic.asset.assets', $assetResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('asset:assets:viewown', 'asset:assets:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.asset.assets',
                $this->assetModel->getCommandList()
            );
        }
    }
}