<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class PageSubscriber
{
    /**
     * Add forms to available page tokens.
     */
    #[AsEventListener(
        event: PageBuilderEvent::class,
        priority: 0
    )]
    public function onPageBuild(PageBuilderEvent $event): void
    {
        if ($event->abTestWinnerCriteriaRequested()) {
            // add AB Test Winner Criteria
            $assetDownloads = [
                'group'    => 'mautic.asset.abtest.criteria',
                'label'    => 'mautic.asset.abtest.criteria.downloads',
                'event'    => AssetEvents::ON_DETERMINE_DOWNLOAD_RATE_WINNER,
            ];
            $event->addAbTestWinnerCriteria('asset.downloads', $assetDownloads);
        }
    }
}
