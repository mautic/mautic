<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_BUILD => ['OnPageBuild', 0],
        ];
    }

    /**
     * Add forms to available page tokens.
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild(PageBuilderEvent $event)
    {
        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $assetDownloads = [
                'group'    => 'mautic.asset.abtest.criteria',
                'label'    => 'mautic.asset.abtest.criteria.downloads',
                'callback' => '\Mautic\AssetBundle\Helper\AbTestHelper::determineDownloadWinner',
            ];
            $event->addAbTestWinnerCriteria('asset.downloads', $assetDownloads);
        }
    }
}
