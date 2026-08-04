<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\UnauthorizedException;
use Mautic\IntegrationsBundle\Helper\AuthIntegrationsHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends CommonController
{
    private AuthIntegrationsHelper $authIntegrationsHelper;

    public function callbackAction(string $integration, Request $request): Response
    {
        $authenticationError = false;

        try {
            $authIntegration = $this->authIntegrationsHelper->getIntegration($integration);
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

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(
        AuthIntegrationsHelper $authIntegrationsHelper,
    ): void {
        $this->authIntegrationsHelper = $authIntegrationsHelper;
    }
}
