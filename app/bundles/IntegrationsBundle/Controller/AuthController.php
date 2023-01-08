<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\UnauthorizedException;
use Mautic\IntegrationsBundle\Helper\AuthIntegrationsHelper;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends CommonController
{
    public function callbackAction(string $integration, Request $request)
    {
        /** @var AuthIntegrationsHelper $authIntegrationsHelper */
        $authIntegrationsHelper = $this->get('mautic.integrations.helper.auth_integrations');
        $authenticationError    = false;

        try {
            $authIntegration = $authIntegrationsHelper->getIntegration($integration);
            $message         = $authIntegration->authenticateIntegration($request);
        } catch (UnauthorizedException $exception) {
            $message             = $exception->getMessage();
            $authenticationError = true;
        } catch (IntegrationNotFoundException $exception) {
            return $this->notFound();
        }

        return $this->render(
            'IntegrationsBundle:Auth:authenticated.html.php',
            [
                'message'             => $message,
                'authenticationError' => $authenticationError,
            ]
        );
    }
}
