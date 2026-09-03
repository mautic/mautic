<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\MarketplaceBundle\Enum\PackageType;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class ListController extends CommonController
{
    private PluginCollector $pluginCollector;

    private RouteProvider $routeProvider;

    private Config $config;

    #[Required]
    public function autowireListController(
        PluginCollector $pluginCollector,
        RouteProvider $routeProvider,
        Config $config,
    ): void {
        $this->pluginCollector = $pluginCollector;
        $this->routeProvider   = $routeProvider;
        $this->config          = $config;
    }

    public function listAction(int $page = 1): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        $request = $this->getCurrentRequest();
        $session = $request->getSession();

        $search = InputHelper::clean($request->get('search', $session->get('mautic.marketplace.filter', '')));
        $session->set('mautic.marketplace.filter', $search);

        if (empty($page)) {
            $page = $session->get('mautic.marketplace.package.page', 1);
        }

        // Parse type filter and remove it from search string
        $type        = $this->parseTypeFromSearch($search);
        $searchQuery = $this->removeTypeCommandFromSearch($search);

        // set limits
        $limit   = $session->get('mautic.marketplace.package.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $route   = $this->routeProvider->buildListRoute($page);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'searchValue'       => $search,
                    'items'             => $this->pluginCollector->collectPackages($page, $limit, $searchQuery, $type),
                    'count'             => $this->pluginCollector->getTotal(),
                    'page'              => $page,
                    'limit'             => $limit,
                    'tmpl'              => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'isComposerEnabled' => $this->config->isComposerEnabled(),
                    'currentType'       => $type,
                ],
                'contentTemplate' => '@Marketplace/Package/list.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'route'         => $route,
                ],
            ]
        );
    }

    private function parseTypeFromSearch(string $search): ?string
    {
        foreach (PackageType::getSearchCommandMap() as $searchCommand => $packageType) {
            if (str_contains(strtolower($search), $searchCommand)) {
                return $packageType;
            }
        }

        return null;
    }

    private function removeTypeCommandFromSearch(string $search): string
    {
        return trim(preg_replace(PackageType::getTypeCommandPattern(), '', $search));
    }
}
