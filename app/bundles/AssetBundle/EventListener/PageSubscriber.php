<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageSubscriber implements EventSubscriberInterface
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
     */
    public function onPageBuild(PageBuilderEvent $event)
    {
        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $assetDownloads = [
                'group'    => 'mautic.asset.abtest.criteria',
                'label'    => 'mautic.asset.abtest.criteria.downloads',
                'event'    => AssetEvents::ON_DETERMINE_DOWNLOAD_RATE_WINNER,
            ];
            $event->addAbTestWinnerCriteria('asset.downloads', $assetDownloads);
        }
    }
}
