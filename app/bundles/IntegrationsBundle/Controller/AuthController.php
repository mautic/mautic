<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\UnauthorizedException;
use Mautic\IntegrationsBundle\Helper\AuthIntegrationsHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends CommonController
{
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        protected \Doctrine\Persistence\ManagerRegistry $doctrine,
        protected ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        protected \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        protected \Mautic\CoreBundle\Translation\Translator $translator,
        private \Mautic\CoreBundle\Service\FlashBag $flashBag,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly AuthIntegrationsHelper $authIntegrationsHelper,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

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
}
