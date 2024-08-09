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
    public function callbackAction(AuthIntegrationsHelper $authIntegrationsHelper, string $integration, Request $request)
    {
        $authenticationError = false;

        try {
            $authIntegration = $authIntegrationsHelper->getIntegration($integration);
            $message         = $authIntegration->authenticateIntegration($request);
        } catch (UnauthorizedException $exception) {
            $message             = $exception->getMessage();
            $authenticationError = true;
        } catch (IntegrationNotFoundException) {
            return $this->notFound();
        }

        return $this->render(
            '@Integrations/Auth/authenticated.html.twig',
            [
                'message'             => $message,
                'authenticationError' => $authenticationError,
            ]
        );
    }
}
