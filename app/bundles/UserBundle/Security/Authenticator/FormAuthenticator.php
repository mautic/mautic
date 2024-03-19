<?php

namespace Mautic\UserBundle\Security\Authenticator;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class FormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE       = 'login';

    public const LOGIN_CHECK_ROUTE = 'mautic_user_logincheck';

    /**
     * @var string|null After upgrade to Symfony 5.2 we should use Passport system to store the authenticatingService
     */
    private ?string $authenticatingService = null;

    private ?Response $authEventResponse = null;

    public function __construct(
        private IntegrationHelper $integrationHelper,
        private UserPasswordHasher $hasher,
        private EventDispatcherInterface $dispatcher,
        private ?RequestStack $requestStack,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_CHECK_ROUTE === $request->attributes->get('_route')
            && $request->isMethod(Request::METHOD_POST);
    }

    /**
     * @return array<string, mixed|null>
     */
    public function getCredentials(Request $request): array
    {
        $credentials = [
            'username'    => $request->request->get('_username'),
            'password'    => $request->request->get('_password'),
            'csrf_token'  => $request->request->get('_csrf_token'),
            'integration' => $request->get('integration'),
        ];

        $request->getSession()->set(Security::LAST_USERNAME, $credentials['username']);

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        $csrfToken = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new InvalidCsrfTokenException();
        }

        try {
            /** @var User $user */
            $user = $userProvider->loadUserByIdentifier($credentials['username']);
        } catch (UserNotFoundException) {
            /** @var string $user */
            $user = $credentials['username'];
        }

        $this->authenticatingService = $credentials['integration'] ?? null;

        // Try authenticating with a plugin first
        $integrations = $this->integrationHelper->getIntegrationObjects($this->authenticatingService, ['sso_form'], false, null, true);
        $token        = new PluginToken(
            null, // In 4.4 there was a provider key. If the issue will be severe we need to override whole guard. Otherwise, wait for Symfony 5.2 and Passport.
            $this->authenticatingService,
            $user,
            ($user instanceof User) ? $this->getPassword($credentials) : '',
            ($user instanceof User) ? $user->getRoles() : [],
            $this->authEventResponse // though this will be null ?
        );
        $authEvent = new AuthenticationEvent(
            $user,
            $token,
            $userProvider,
            $this->requestStack->getCurrentRequest(),
            false,
            $this->authenticatingService,
            $integrations
        );

        if ($this->dispatcher->hasListeners(UserEvents::USER_FORM_AUTHENTICATION)) {
            $this->dispatcher->dispatch($authEvent, UserEvents::USER_FORM_AUTHENTICATION);
        }

        if ($authEvent->isAuthenticated()) {
            $user                        = $authEvent->getUser();
            $this->authenticatingService = $authEvent->getAuthenticatingService();
        } elseif ($authEvent->isFailed()) {
            throw new AuthenticationException($authEvent->getFailedAuthenticationMessage());
        }

        $this->authEventResponse = $authEvent->getResponse();

        if (!$user instanceof User) {
            throw new BadCredentialsException();
        }

        if ($this->dispatcher->hasListeners(UserEvents::USER_FORM_POST_LOCAL_PASSWORD_AUTHENTICATION)) {
            $authEvent = new AuthenticationEvent($user, $token, $userProvider, $this->requestStack->getCurrentRequest());
            $this->dispatcher->dispatch($authEvent, UserEvents::USER_FORM_POST_LOCAL_PASSWORD_AUTHENTICATION);
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // Temp solution to remap a UserInterface object to a PasswordAuthenticatedUserInterface object
        $newUser = new User();
        $newUser->setUsername($user->getUserIdentifier());
        $newUser->setPassword($user->getPassword());

        return $this->hasher->isPasswordValid($newUser, $this->getPassword($credentials));
    }

    public function getPassword($credentials): ?string
    {
        return $credentials['password'] ?? null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?RedirectResponse
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // If integrations fail due to redirect to dashboard look into
        // how to detect if that's a proper form auth and return null if request must continue w/o redirect
        return new RedirectResponse($this->urlGenerator->generate('mautic_dashboard_index'));
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
