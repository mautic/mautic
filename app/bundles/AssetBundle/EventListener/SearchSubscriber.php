<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var AssetModel
     */
    private $assetModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var TemplatingHelper
     */
    private $templating;

    public function __construct(AssetModel $assetModel, CorePermissions $security, UserHelper $userHelper, TemplatingHelper $templating)
    {
        $this->assetModel = $assetModel;
        $this->security   = $security;
        $this->userHelper = $userHelper;
        $this->templating = $templating;
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
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $filter = ['string' => $str, 'force' => []];

        $permissions = $this->security->isGranted(
            ['asset:assets:viewown', 'asset:assets:viewother'],
            'RETURN_ARRAY'
        );
        if ($permissions['asset:assets:viewown'] || $permissions['asset:assets:viewother']) {
            if (!$permissions['asset:assets:viewother']) {
                $filter['force'][] = [
                    'column' => 'IDENTITY(a.createdBy)',
                    'expr'   => 'eq',
                    'value'  => $this->userHelper->getUser()->getId(),
                ];
            }

            $assets = $this->assetModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $filter,
                ]);

            if (count($assets) > 0) {
                $assetResults = [];

                foreach ($assets as $asset) {
                    $assetResults[] = $this->templating->getTemplating()->renderResponse(
                        'MauticAssetBundle:SubscribedEvents\Search:global.html.php',
                        ['asset' => $asset]
                    )->getContent();
                }
                if (count($assets) > 5) {
                    $assetResults[] = $this->templating->getTemplating()->renderResponse(
                        'MauticAssetBundle:SubscribedEvents\Search:global.html.php',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($assets) - 5),
                        ]
                    )->getContent();
                }
                $assetResults['count'] = count($assets);
                $event->addResults('mautic.asset.assets', $assetResults);
            }
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(['asset:assets:viewown', 'asset:assets:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.asset.assets',
                $this->assetModel->getCommandList()
            );
        }
    }
}
