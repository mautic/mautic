<?php

namespace Mautic\UserBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends CommonController implements EventSubscriberInterface
{
    public function __construct(
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        ?RequestStack $requestStack,
        ?CorePermissions $security,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function onRequest(RequestEvent $event): void
    {
        $controller = $event->getRequest()->attributes->get('_controller');
        \assert(is_string($controller));

        if (!str_contains($controller, self::class)) {
            return;
        }

        // redirect user if they are already authenticated
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            || $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $redirectUrl = $this->generateUrl('mautic_dashboard_index');
            $event->setResponse(new RedirectResponse($redirectUrl));
        }
    }

    /**
     * Generates login form and processes login.
     */
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils, IntegrationHelper $integrationHelper, TranslatorInterface $translator): \Symfony\Component\HttpFoundation\Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        if (null !== $error) {
            if ($error instanceof WeakPasswordException) {
                $this->addFlash(FlashBag::LEVEL_ERROR, $translator->trans('mautic.user.auth.error.weakpassword', [], 'flashes'));

                return $this->forward('Mautic\UserBundle\Controller\PublicController::passwordResetAction');
            } elseif ($error instanceof Exception\BadCredentialsException) {
                $msg = 'mautic.user.auth.error.invalidlogin';
            } elseif ($error instanceof Exception\DisabledException) {
                $msg = 'mautic.user.auth.error.disabledaccount';
            } else {
                $msg = $error->getMessage();
            }

            $this->addFlashMessage($msg, [], FlashBag::LEVEL_ERROR, null, false);
        }
        $request->query->set('tmpl', 'login');

        // Get a list of SSO integrations
        $integrations = $integrationHelper->getIntegrationObjects(null, ['sso_service'], true, null, true);

        return $this->delegateView([
            'viewParameters' => [
                'last_username' => $authenticationUtils->getLastUsername(),
                'integrations'  => $integrations,
            ],
            'contentTemplate' => '@MauticUser/Security/login.html.twig',
            'passthroughVars' => [
                'route'          => $this->generateUrl('login'),
                'mauticContent'  => 'user',
                'sessionExpired' => true,
            ],
        ]);
    }

    /**
     * Do nothing.
     */
    public function loginCheckAction(): void
    {
    }

    /**
     * The plugin should be handling this in it's listener.
     */
    public function ssoLoginAction($integration): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('login'));
    }

    /**
     * The plugin should be handling this in it's listener.
     */
    public function ssoLoginCheckAction($integration): RedirectResponse
    {
        // The plugin should be handling this in it's listener

        return new RedirectResponse($this->generateUrl('login'));
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }
}
