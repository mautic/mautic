<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var TemplatingHelper
     */
    private $templating;

    public function __construct(
        CampaignModel $campaignModel,
        CorePermissions $security,
        TemplatingHelper $templating
    ) {
        $this->campaignModel = $campaignModel;
        $this->security      = $security;
        $this->templating    = $templating;
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
                    $campaignResults[] = $this->templating->getTemplating()->renderResponse(
                        'MauticCampaignBundle:SubscribedEvents\Search:global.html.php',
                        [
                            'campaign' => $campaign,
                        ]
                    )->getContent();
                }
                if (count($campaigns) > 5) {
                    $campaignResults[] = $this->templating->getTemplating()->renderResponse(
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
