<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\MarketplaceBundle\Service\Composer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * Installs a Composer package.
     */
    public function installPackageAction(Request $request): JsonResponse
    {
        // This works, now handle logic here
        return $this->sendJsonResponse([
            'status' => 'ok',
        ], 400);
    }
}
