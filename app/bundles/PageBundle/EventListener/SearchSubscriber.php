<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var PageModel
     */
    private $pageModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(
        UserHelper $userHelper,
        PageModel $pageModel,
        CorePermissions $security,
        Environment $twig
    ) {
        $this->userHelper = $userHelper;
        $this->pageModel  = $pageModel;
        $this->security   = $security;
        $this->twig       = $twig;
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
            ['page:pages:viewown', 'page:pages:viewother'],
            'RETURN_ARRAY'
        );
        if ($permissions['page:pages:viewown'] || $permissions['page:pages:viewother']) {
            if (!$permissions['page:pages:viewother']) {
                $filter['force'][] = [
                    'column' => 'IDENTITY(p.createdBy)',
                    'expr'   => 'eq',
                    'value'  => $this->userHelper->getUser()->getId(),
                ];
            }

            $pages = $this->pageModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $filter,
                ]);

            if (count($pages) > 0) {
                $pageResults = [];

                foreach ($pages as $page) {
                    $pageResults[] = $this->twig->render(
                        '@MauticPage/SubscribedEvents\Search/global.html.twig',
                        ['page' => $page]
                    );
                }
                if (count($pages) > 5) {
                    $pageResults[] = $this->twig->render(
                        '@MauticPage/SubscribedEvents\Search/global.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($pages) - 5),
                        ]
                    );
                }
                $pageResults['count'] = count($pages);
                $event->addResults('mautic.page.pages', $pageResults);
            }
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(['page:pages:viewown', 'page:pages:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.page.pages',
                $this->pageModel->getCommandList()
            );
        }
    }
}
