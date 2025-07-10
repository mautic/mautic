<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class DetailController extends CommonController
{
    public function __construct(
        private PackageModel $packageModel,
        private RouteProvider $routeProvider,
        private Config $config,
        private ComposerHelper $composer,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function viewAction(string $vendor, string $package): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            return $this->accessDenied();
        }

        $isInstalled = $this->composer->isInstalled("{$vendor}/{$package}");

        try {
            $packageDetail = $this->packageModel->getPackageDetail("{$vendor}/{$package}");
        } catch (RecordNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        $security = $this->security;

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'packageDetail'     => $packageDetail,
                    'isInstalled'       => $isInstalled,
                    'isComposerEnabled' => $this->config->isComposerEnabled(),
                    'security'          => $security,
                ],
                'contentTemplate' => '@Marketplace/Package/detail.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'package',
                    'activeLink'    => '#mautic_marketplace',
                    'route'         => $this->routeProvider->buildDetailRoute($vendor, $package),
                ],
            ]
        );
    }
}
