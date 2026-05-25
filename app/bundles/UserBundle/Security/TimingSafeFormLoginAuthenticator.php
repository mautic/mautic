<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class TimingSafeFormLoginAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    /**
     * @var array<mixed>
     */
    private array $options;

    /**
     * @param array<mixed> $options
     */
    public function __construct(private FormLoginAuthenticator $authenticator, private UserProviderInterface $userProvider, private PasswordHasherFactoryInterface $passwordHasherFactory, array $options)
    {
        $this->authenticator         = $authenticator;
        $this->userProvider          = $userProvider;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->options               = array_merge([
            'username_parameter' => '_username',
            'password_parameter' => '_password',
            'check_path'         => '/login_check',
            'post_only'          => true,
            'form_only'          => false,
            'enable_csrf'        => false,
            'csrf_parameter'     => '_csrf_token',
            'csrf_token_id'      => 'authenticate',
        ], $options);
    }

    public function supports(Request $request): ?bool
    {
        return $this->authenticator->supports($request);
    }

    public function authenticate(Request $request): Passport
    {
        $credentials           = $this->getCredentials($request);
        $passwordHasherFactory = $this->passwordHasherFactory;
        $userLoader            = function (string $identifier) use ($passwordHasherFactory, $credentials): UserInterface {
            try {
                // Attempt to load the real user.
                return $this->userProvider->loadUserByIdentifier($identifier);
            } catch (UserNotFoundException $e) {
                // If real user is not found, provide a dummy user and still 'check' the credentials to prevent
                // user enumeration via response timing comparison.
                // We check it against a pre-calculated hash so the verify functions take roughly
                // the same amount of time, and we pass the actual entered password so the response
                // timing varies with the given password the same way it does for existing users.
                $user = new User();
                $passwordHasherFactory->getPasswordHasher($user)->verify('$2y$13$aAwXNyqA87lcXQQuk8Cp6eo2amRywLct29oG2uWZ8lYBeamFZ8UhK', $credentials['password']);
                // Rethrow exception
                throw $e;
            }
        };

        $userBadge = new UserBadge($credentials['username'], $userLoader);
        $passport  = new Passport($userBadge, new PasswordCredentials($credentials['password']), [new RememberMeBadge()]);

        if ($this->options['enable_csrf']) {
            $passport->addBadge(new CsrfTokenBadge($this->options['csrf_token_id'], $credentials['csrf_token']));
        }

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userProvider));
        }

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return $this->authenticator->createToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->authenticator->onAuthenticationFailure($request, $exception);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->authenticator->start($request, $authException);
    }

    public function isInteractive(): bool
    {
        return $this->authenticator->isInteractive();
    }

    /**
     * @return array<mixed>
     */
    private function getCredentials(Request $request): array
    {
        $credentials               = [];
        $credentials['csrf_token'] = $request->get($this->options['csrf_parameter']);

        if ($this->options['post_only']) {
            $credentials['username'] = $request->request->get($this->options['username_parameter']);
            $credentials['password'] = $request->request->get($this->options['password_parameter'], '');
        } else {
            $credentials['username'] = $request->get($this->options['username_parameter']);
            $credentials['password'] = $request->get($this->options['password_parameter'], '');
        }

        if (!\is_string($credentials['username']) && !$credentials['username'] instanceof \Stringable) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials['username']);

        if (!\is_string($credentials['password']) && (!\is_object($credentials['password']) || !method_exists($credentials['password'], '__toString'))) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['password_parameter'], \gettype($credentials['password'])));
        }

        if (!\is_string($credentials['csrf_token'] ?? '') && (!\is_object($credentials['csrf_token']) || !method_exists($credentials['csrf_token'], '__toString'))) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['csrf_parameter'], \gettype($credentials['csrf_token'])));
        }

        return $credentials;
    }
}
