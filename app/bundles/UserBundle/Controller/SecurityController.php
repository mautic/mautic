<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactoryInterface;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Security\SAML\Helper as SAMLHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
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

        // Don't redirect from OIDC actions - oidcRequiredAction needs user to link account,
        // oidcCheckAction needs to return 404 for direct access
        if (str_contains($controller, 'oidcRequiredAction')
            || str_contains($controller, 'oidcCheckAction')
            || str_contains($controller, 'oidcLoginAction')) {
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

    public function samlLoginRetryAction(Request $request, SAMLHelper $samlHelper, SessionInterface $session): Response
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

    public function oidcLoginAction(
        Settings $oidcSettings,
        ClientFactoryInterface $clientFactory,
        ClientCredentials $clientCredentials,
        LoggerInterface $logger,
        SessionInterface $session,
    ): RedirectResponse {
        if (!$oidcSettings->isEnabled()) {
            return $this->redirectToRoute('login');
        }

        // Clear any previous OIDC session state to ensure a clean authentication flow
        // This is important when user is already authenticated with non-OIDC method
        $oidcSessionKeys = ['openid_connect_state', 'openid_connect_nonce', 'openid_connect_code_verifier'];
        foreach ($oidcSessionKeys as $key) {
            $session->remove($key);
        }

        try {
            $oidcClient = $clientFactory->create($clientCredentials);
            $authenticatedRedirect = $oidcClient->authenticate();
            if ($authenticatedRedirect) {
                return $authenticatedRedirect;
            }
        } catch (\Mautic\UserBundle\Exception\OidcAuthorizationException $e) {
            $logger->error('OpenID Connect: Login action failed', ['exception' => $e, 'message' => $e->getMessage()]);
        }

        throw new Exception\AuthenticationException('OpenID Connect authentication failed.');
    }

    /**
     * OIDC login check action (handled by authenticator).
     * This endpoint should only be reached when the authenticator processes the OIDC callback.
     * Direct GET requests should return 404.
     */
    public function oidcCheckAction(): Response
    {
        // This method should be intercepted by the authenticator
        // If we reach here, it means the request was not handled by the authenticator
        throw $this->createNotFoundException('This endpoint is handled by the OIDC authenticator.');
    }

    /**
     * OIDC required action - prompts user to link their OIDC account.
     */
    public function oidcRequiredAction(
        Settings $oidcSettings,
        OidcSubjectIdRepository $repository,
    ): Response {
        if (!$oidcSettings->isEnabled() || !($user = $this->getUser())) {
            return $this->redirectToRoute('login');
        }

        // Show the OIDC required page - it will prompt them to click OIDC login button
        // Whether they already have OIDC linked or not, they need to authenticate via OIDC
        return $this->render('@MauticUser/Security/oidc_required.html.twig', [
            'parameters' => $oidcSettings,
            'hasOidcLinked' => (bool) $repository->findOneBy(['user' => $user]),
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
