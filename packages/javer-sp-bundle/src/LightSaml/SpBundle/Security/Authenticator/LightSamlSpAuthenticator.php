<?php

namespace LightSaml\SpBundle\Security\Authenticator;

use LightSaml\Builder\Profile\ProfileBuilderInterface;
use LightSaml\Model\Protocol\Response as LightSamlResponse;
use LightSaml\SpBundle\Security\Authentication\Token\SamlSpToken;
use LightSaml\SpBundle\Security\Authentication\Token\SamlSpTokenFactoryInterface;
use LightSaml\SpBundle\Security\User\AttributeMapperInterface;
use LightSaml\SpBundle\Security\User\UserCreatorInterface;
use LightSaml\SpBundle\Security\User\UsernameMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

class LightSamlSpAuthenticator extends AbstractAuthenticator
{
    public const PASSPORT_ATTRIBUTES = 'attributes';
    public const PASSPORT_SAML_RESPONSE = 'samlResponse';

    public function __construct(
        private string $firewallName,
        private HttpUtils $httpUtils,
        private ProfileBuilderInterface $profile,
        private ?UserProviderInterface $userProvider = null,
        private ?UsernameMapperInterface $usernameMapper = null,
        private ?UserCreatorInterface $userCreator = null,
        private ?AttributeMapperInterface $attributeMapper = null,
        private ?SamlSpTokenFactoryInterface $tokenFactory = null,
        private ?AuthenticationSuccessHandlerInterface $successHandler = null,
        private ?AuthenticationFailureHandlerInterface $failureHandler = null,
        private array $options = [],
    )
    {
    }

    public function supports(Request $request): bool
    {
        return !isset($this->options['check_path'])
            || $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    public function authenticate(Request $request): Passport
    {
        $samlResponse = $this->receiveSamlResponse();

        $user = $this->resolveUser($samlResponse);

        $attributes = $this->getAttributes($samlResponse);

        $passport = new SelfValidatingPassport(new UserBadge(
            method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername(),
            static fn() => $user,
        ));
        $passport->setAttribute(self::PASSPORT_ATTRIBUTES, $attributes);
        $passport->setAttribute(self::PASSPORT_SAML_RESPONSE, $samlResponse);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): SamlSpToken
    {
        $user = $passport->getUser();
        $attributes = $passport->getAttribute(self::PASSPORT_ATTRIBUTES);
        $samlResponse = $passport->getAttribute(self::PASSPORT_SAML_RESPONSE);

        if ($this->tokenFactory) {
            $token = $this->tokenFactory->create($user, $this->firewallName, $attributes, $samlResponse);
        } else {
            $token = new SamlSpToken($user, $this->firewallName, $user->getRoles(), $attributes);
        }

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler?->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler?->onAuthenticationFailure($request, $exception);
    }

    protected function receiveSamlResponse(): LightSamlResponse
    {
        $context = $this->profile->buildContext();
        $action = $this->profile->buildAction();

        $action->execute($context);

        return $context->getInboundMessage();
    }

    /**
     * @throws AuthenticationException
     */
    protected function resolveUser(LightSamlResponse $samlResponse): UserInterface
    {
        try {
            $user = $this->loadUser($samlResponse);
        } catch (UserNotFoundException) {
            $user = $this->createUser($samlResponse);
        }

        if (null === $user) {
            throw new AuthenticationException('Unable to resolve user');
        }

        return $user;
    }

    /**
     * @throws UserNotFoundException
     */
    protected function loadUser(LightSamlResponse $samlResponse): UserInterface
    {
        if (null === $this->usernameMapper || null === $this->userProvider) {
            throw new UserNotFoundException();
        }

        $username = $this->usernameMapper->getUsername($samlResponse);

        if (null === $username) {
            throw new UserNotFoundException();
        }

        return method_exists($this->userProvider, 'loadUserByIdentifier')
            ? $this->userProvider->loadUserByIdentifier($username)
            : $this->userProvider->loadUserByUsername($username);
    }

    protected function createUser(LightSamlResponse $samlResponse): ?UserInterface
    {
        return $this->userCreator?->createUser($samlResponse);
    }

    protected function getAttributes(LightSamlResponse $samlResponse): array
    {
        return $this->attributeMapper?->getAttributes($samlResponse) ?? [];
    }
}
