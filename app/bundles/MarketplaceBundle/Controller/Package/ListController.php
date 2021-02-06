<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ListController extends CommonController
{
    private $pluginCollector;
    private $requestStack;
    private $routeProvider;

    public function __construct(
        PluginCollector $pluginCollector,
        RequestStack $requestStack,
        RouteProvider $routeProvider
    ) {
        $this->pluginCollector = $pluginCollector;
        $this->requestStack    = $requestStack;
        $this->routeProvider   = $routeProvider;
    }

    public function listAction(int $page = 1): Response
    {
        // @todo implement permissions
        // try {
        //     $this->permissionProvider->canViewAtAll();
        // } catch (ForbiddenException $e) {
        //     return $this->accessDenied(false, $e->getMessage());
        // }

        $request    = $this->requestStack->getCurrentRequest();
        $search     = InputHelper::clean($request->get('search', ''));
        $limit      = (int) $request->get('limit', 30);
        // $orderBy    = $this->sessionProvider->getOrderBy(CustomObject::TABLE_ALIAS.'.id');
        // $orderByDir = $this->sessionProvider->getOrderByDir('ASC');
        $route      = $this->routeProvider->buildListRoute($page);

        // if ($request->query->has('orderby')) {
        //     $orderBy    = InputHelper::clean($request->query->get('orderby'), true);
        //     $orderByDir = 'ASC' === $orderByDir ? 'DESC' : 'ASC';
        //     $this->sessionProvider->setOrderBy($orderBy);
        //     $this->sessionProvider->setOrderByDir($orderByDir);
        // }

        // $this->sessionProvider->setPage($page);
        // $this->sessionProvider->setPageLimit($limit);
        // $this->sessionProvider->setFilter($search);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'    => $search,
                    'items'          => $this->pluginCollector->collectPackages($page, $limit, $search),
                    'count'          => $this->pluginCollector->getTotal(),
                    'page'           => $page,
                    'limit'          => $limit,
                    'tmpl'           => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'MarketplaceBundle:Package:list.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'route'         => $route,
                ],
            ]
        );
    }
}
