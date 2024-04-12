<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetModel $assetModel,
        private CorePermissions $security,
        private UserHelper $userHelper,
        private Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH      => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
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
                    $assetResults[] = $this->twig->render(
                        '@MauticAsset/SubscribedEvents/Search/global.html.twig',
                        ['asset' => $asset]
                    );
                }
                if (count($assets) > 5) {
                    $assetResults[] = $this->twig->render(
                        '@MauticAsset/SubscribedEvents/Search/global.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($assets) - 5),
                        ]
                    );
                }
                $assetResults['count'] = count($assets);
                $event->addResults('mautic.asset.assets', $assetResults);
            }
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted(['asset:assets:viewown', 'asset:assets:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.asset.assets',
                $this->assetModel->getCommandList()
            );
        }
    }
}
