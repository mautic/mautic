<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authenticator;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * This is a modified copy of the \Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator
 * Replaces \Mautic\UserBundle\Security\Authenticator\FormAuthenticator.
 */
final class SsoAuthenticator extends AbstractAuthenticator implements InteractiveAuthenticatorInterface
{
    /**
     * @var array<mixed>
     */
    private array $options;

    /**
     * @param array<mixed> $options
     */
    public function __construct(array $options, private HttpUtils $httpUtils, private UserProviderInterface $userProvider, private AuthenticationSuccessHandlerInterface $successHandler, private AuthenticationFailureHandlerInterface $failureHandler, private IntegrationHelper $integrationHelper, private EventDispatcherInterface $dispatcher)
    {
        if ([] === $options) {
            throw new \RuntimeException('$options parameter is empty. Did you forgot to configure?');
        }

        $this->options           = array_merge([
            'username_parameter'    => '_username',
            'password_parameter'    => '_password',
            'integration_parameter' => 'integration',
            'post_only'             => true,
            'enable_csrf'           => true,
            'csrf_parameter'        => '_csrf_token',
            'csrf_token_id'         => 'authenticate',
        ], $options);
    }

    public function supports(Request $request): bool
    {
        if (true === $this->options['post_only'] && !$request->isMethod(Request::METHOD_POST)) {
            return false;
        }

        if (!$this->httpUtils->checkRequestPath($request, $this->options['check_path'])) {
            return false;
        }

        if (true === $this->options['form_only'] && 'form' !== $request->getContentTypeFormat()) {
            return false;
        }

        if (true === $this->options['post_only']) {
            return $request->request->has($this->options['integration_parameter']);
        }

        return $request->query->has($this->options['integration_parameter'])
            || $request->request->has($this->options['integration_parameter']);
    }

    public function authenticate(Request $request): Passport
    {
        $credentials           = $this->getCredentials($request);
        $authenticatingService = $credentials['integration'] ?? null;

        $passport = new Passport(
            new UserBadge($credentials['username'], function (string $userIdentifier) use ($request, $authenticatingService, $credentials): ?User {
                /** @var User|null $user */
                $user = null;

                try {
                    $user = $this->userProvider->loadUserByIdentifier($userIdentifier);
                } catch (UserNotFoundException) {
                    // Do nothing. Will try to authenticate by username.
                }

                // Try authenticating with a plugin
                $integrations = $this->integrationHelper->getIntegrationObjects($authenticatingService, ['sso_form'], false, null, true);

                $token = new PluginToken(
                    null,
                    $authenticatingService,
                    $userIdentifier,
                    null !== $user ? ($credentials['password'] ?? null) : '',
                    null !== $user ? $user->getRoles() : [],
                );

                $authEvent = new AuthenticationEvent(
                    $user ?? $userIdentifier,
                    $token,
                    $this->userProvider,
                    $request,
                    false,
                    $authenticatingService,
                    $integrations
                );

                if ($this->dispatcher->hasListeners(UserEvents::USER_FORM_AUTHENTICATION)) {
                    $authEvent = $this->dispatcher->dispatch($authEvent, UserEvents::USER_FORM_AUTHENTICATION);
                }

                if ($authEvent->isAuthenticated()) {
                    $user = $authEvent->getUser();

                    // This line is most likely will never happen. Keep it until this is thoroughly tested manually.
                    if (null !== $user && !$user instanceof User) {
                        return null;
                    }

                    return $user;
                }

                if ($authEvent->isFailed()) {
                    throw new AuthenticationException($authEvent->getFailedAuthenticationMessage());
                }

                if (!$user instanceof User) {
                    return null;
                }

                return $user;
            }),
            new PasswordCredentials($credentials['password'] ?? null),
            [new RememberMeBadge()]
        );

        if ($this->options['enable_csrf']) {
            $passport->addBadge(new CsrfTokenBadge($this->options['csrf_token_id'], $credentials['csrf_token']));
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCredentials(Request $request): array
    {
        $credentials               = [];
        $credentials['csrf_token'] = $request->get($this->options['csrf_parameter']);

        if ($this->options['post_only']) {
            $credentials['username']    = $request->request->get($this->options['username_parameter']);
            $credentials['password']    = $request->request->get($this->options['password_parameter']) ?? '';
            $credentials['integration'] = $request->request->get($this->options['integration_parameter']);
        } else {
            $credentials['username']    = $request->get($this->options['username_parameter']);
            $credentials['password']    = $request->get($this->options['password_parameter']) ?? '';
            $credentials['integration'] = $request->get($this->options['integration_parameter']);
        }

        if (!\is_string($credentials['username'])) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        if (\strlen($credentials['username']) > UserBadge::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        if (null !== $credentials['integration'] && !\is_string($credentials['integration'])) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string or null, "%s" given.', $this->options['integration_parameter'], \gettype($credentials['integration'])));
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials['username']);

        if (!\is_string($credentials['password'])) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['password_parameter'], \gettype($credentials['password'])));
        }

        return $credentials;
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
