<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Controller;


use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends CommonController
{
    public function callbackAction(string $integration, Request $request)
    {
        // Find the integration
        $authIntegrationsHelper = $this->get('mautic.integrations.helper.auth_integrations');
        try {
            $authIntegrationsHelper->getIntegration($integration);
        } catch (IntegrationNotFoundException $exception) {
            return $this->notFound();
        }

        if (Request::METHOD_POST === $request->getMethod()) {

        }
    }
}