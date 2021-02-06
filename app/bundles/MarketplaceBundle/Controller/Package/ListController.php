<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ListController extends CommonController
{
    /**
     * @var PluginCollector
     */
    private $pluginCollector;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouteProvider
     */
    private $routeProvider;

    /**
     * @var CorePermissions
     */
    private $corePermissions;

    public function __construct(
        PluginCollector $pluginCollector,
        RequestStack $requestStack,
        RouteProvider $routeProvider,
        CorePermissions $corePermissions
    ) {
        $this->pluginCollector = $pluginCollector;
        $this->requestStack    = $requestStack;
        $this->routeProvider   = $routeProvider;
        $this->corePermissions = $corePermissions;
    }

    public function listAction(int $page = 1): Response
    {
        if (!$this->corePermissions->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            return $this->accessDenied();
        }

        $request = $this->requestStack->getCurrentRequest();
        $search  = InputHelper::clean($request->get('search', ''));
        $limit   = (int) $request->get('limit', 30);
        $route   = $this->routeProvider->buildListRoute($page);

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
