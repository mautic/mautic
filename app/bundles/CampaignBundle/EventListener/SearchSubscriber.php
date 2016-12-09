<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class SearchSubscriber.
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param CampaignModel $campaignModel
     */
    public function __construct(CampaignModel $campaignModel)
    {
        $this->campaignModel = $campaignModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::GLOBAL_SEARCH      => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        if ($this->security->isGranted('campaign:campaigns:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $campaigns = $this->campaignModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $str,
                ]);

            if (count($campaigns) > 0) {
                $campaignResults = [];
                foreach ($campaigns as $campaign) {
                    $campaignResults[] = $this->templating->renderResponse(
                        'MauticCampaignBundle:SubscribedEvents\Search:global.html.php',
                        [
                            'campaign' => $campaign,
                        ]
                    )->getContent();
                }
                if (count($campaigns) > 5) {
                    $campaignResults[] = $this->templating->renderResponse(
                        'MauticCampaignBundle:SubscribedEvents\Search:global.html.php',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($campaigns) - 5),
                        ]
                    )->getContent();
                }
                $campaignResults['count'] = count($campaigns);
                $event->addResults('mautic.campaign.campaigns', $campaignResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        $security = $this->security;
        if ($security->isGranted('campaign:campaigns:view')) {
            $event->addCommands(
                'mautic.campaign.campaigns',
                $this->campaignModel->getCommandList()
            );
        }
    }
}
