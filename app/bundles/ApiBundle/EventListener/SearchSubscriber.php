<?php

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var ClientModel
     */
    private $apiClientModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(ClientModel $apiClientModel, CorePermissions $security, Environment $twig)
    {
        $this->apiClientModel = $apiClientModel;
        $this->security       = $security;
        $this->twig           = $twig;
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
        if ($this->security->isGranted('api:clients:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $clients = $this->apiClientModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $str,
                ]);

            if (count($clients) > 0) {
                $clientResults = [];
                $canEdit       = $this->security->isGranted('api:clients:edit');
                foreach ($clients as $client) {
                    $clientResults[] = $this->twig->render(
                        '@MauticApi/SubscribedEvents\Search/global.html.twig',
                        [
                            'client'  => $client,
                            'canEdit' => $canEdit,
                        ]
                    );
                }
                if (count($clients) > 5) {
                    $clientResults[] = $this->twig->render(
                        '@MauticApi/SubscribedEvents\Search/global.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($clients) - 5),
                        ]
                    );
                }
                $clientResults['count'] = count($clients);
                $event->addResults('mautic.api.client.menu.index', $clientResults);
            }
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted('api:clients:view')) {
            $event->addCommands(
                'mautic.api.client.header.index',
                $this->apiClientModel->getCommandList()
            );
        }
    }
}
