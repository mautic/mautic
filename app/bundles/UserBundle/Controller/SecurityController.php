<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Security\SAML\Helper as SAMLHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityController extends CommonController implements EventSubscriberInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;

    #[Required]
    public function autowireSecurityController(
        AuthorizationCheckerInterface $authorizationChecker,
    ): void {
        $this->authorizationChecker = $authorizationChecker;
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
    #[Route(
        '/s/login',
        name: 'login',
        priority: -751
    )]
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils, IntegrationHelper $integrationHelper, TranslatorInterface $translator): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        if (null !== $error) {
            if ($error instanceof WeakPasswordException) {
                $this->addFlash(FlashBag::LEVEL_ERROR, $translator->trans('mautic.user.auth.error.weakpassword', [], 'flashes'));

                return $this->forward('Mautic\UserBundle\Controller\PublicController::passwordResetAction');
            }
            if ($error instanceof Exception\BadCredentialsException) {
                $msg = 'mautic.user.auth.error.invalidlogin';
            } elseif ($error instanceof Exception\DisabledException) {
                $msg = 'mautic.user.auth.error.disabledaccount';
            } elseif ($error instanceof Exception\AuthenticationException) {
                $msg = $error->getMessageKey();
            } else {
                $msg = $error->getMessage();
            }

            $messageVars = $error instanceof Exception\AuthenticationException ? $error->getMessageData() : [];
            $this->addFlashMessage($msg, $messageVars, FlashBag::LEVEL_ERROR, null);
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
     * The plugin should be handling this in it's listener.
     */
    #[Route(
        '/s/sso_login/{integration}',
        name: 'mautic_sso_login',
        priority: -754
    )]
    public function ssoLoginAction($integration): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('login'));
    }

    /**
     * The plugin should be handling this in it's listener.
     */
    #[Route(
        '/s/sso_login_check/{integration}',
        name: 'mautic_sso_login_check',
        priority: -755
    )]
    public function ssoLoginCheckAction($integration): RedirectResponse
    {
        // The plugin should be handling this in it's listener

        return new RedirectResponse($this->generateUrl('login'));
    }

    #[Route(
        '/saml/login_retry',
        name: 'mautic_saml_login_retry',
        priority: -265
    )]
    public function samlLoginRetryAction(SAMLHelper $samlHelper, SessionInterface $session): Response
    {
        if (!$samlHelper->isSamlEnabled()) {
            return new RedirectResponse($this->generateUrl('login'));
        }

        $session->invalidate();

        $this->addFlashMessage('mautic.user.security.saml.clearsession', [], FlashBag::LEVEL_ERROR);

        return $this->delegateView([
            'viewParameters' => [
                'loginRoute' => $this->generateUrl('lightsaml_sp.discovery'),
            ],
            'contentTemplate' => '@MauticUser/Security/saml_login_retry.html.twig',
            'passthroughVars' => [
                'route'          => $this->generateUrl('mautic_base_index'),
                'mauticContent'  => 'user',
                'sessionExpired' => true,
            ],
        ]);
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
