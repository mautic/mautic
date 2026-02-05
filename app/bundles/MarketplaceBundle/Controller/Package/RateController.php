<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller\Package;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Symfony\Component\HttpFoundation\Response;

class RateController extends CommonController
{
    public function rateAction(string $vendor, string $package, Config $config): Response
    {
        if (!$config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            return $this->accessDenied();
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'vendor'          => $vendor,
                    'package'         => $package,
                    'auth0Domain'     => $config->getAuth0Domain(),
                    'auth0ClientId'   => $config->getAuth0ClientId(),
                    'reviewsApiUrl'   => $config->getReviewsApiUrl(),
                ],
                'contentTemplate' => '@Marketplace/Package/rating.html.twig',
            ]
        );
    }
}
