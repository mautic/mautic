<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authenticator;

use FOS\OAuthServerBundle\Model\AccessToken;
use FOS\OAuthServerBundle\Security\Authenticator\Passport\Badge\AccessTokenBadge;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class Oauth2Authenticator extends \FOS\OAuthServerBundle\Security\Authenticator\Oauth2Authenticator
{
    private ?TokenPermissions $tokenPermissions = null;

    /**
     * @internal used by the service container to keep the legacy constructor contract intact
     */
    public function setTokenPermissions(TokenPermissions $tokenPermissions): void
    {
        $this->tokenPermissions = $tokenPermissions;
    }

    public function supports(Request $request): bool
    {
        // needed until the oAuth2 library will not be updated to 4.0.5
        return null !== $this->serverService->getBearerToken($request);
    }

    /**
     * Mirrors the parent implementation, but seeds UserBadge with the access
     * token user's identifier when one is available instead of always using the
     * OAuth client identifier.
     */
    public function authenticate(Request $request): SelfValidatingPassport
    {
        try {
            $tokenString = $this->serverService->getBearerToken($request);
            if (null === $tokenString) {
                throw new AuthenticationException('OAuth2 authentication failed: missing access token.');
            }

            /** @var AccessToken $accessToken */
            $accessToken = $this->serverService->verifyAccessToken($tokenString);

            $tokenUser = $accessToken->getUser();
            $user      = $tokenUser;
            $client    = $accessToken->getClient();

            if (null === $user && null !== $this->tokenPermissions) {
                $user = $this->tokenPermissions->setActivePermissionsOnAuthToken($accessToken);
            }

            if (null !== $user) {
                try {
                    $this->userChecker->checkPreAuth($user);
                } catch (AccountStatusException $e) {
                    throw new OAuth2AuthenticateException((string) Response::HTTP_UNAUTHORIZED, OAuth2::TOKEN_TYPE_BEARER, $this->serverService->getVariable(OAuth2::CONFIG_WWW_REALM), 'access_denied', $e->getMessage());
                }
            }

            $roles = (null !== $user) ? $user->getRoles() : [];
            $scope = $accessToken->getScope();

            if (!empty($scope)) {
                foreach (explode(' ', $scope) as $role) {
                    $roles[] = 'ROLE_'.mb_strtoupper($role);
                }
            }

            $roles = array_unique($roles, SORT_REGULAR);

            $accessTokenBadge = new AccessTokenBadge($accessToken, $roles);
            $userLoader = null;
            if (null === $tokenUser && null !== $user) {
                $userLoader = fn (): UserInterface => $user;
            }

            // Parent uses $client->getUserIdentifier() here, which breaks
            // user-bound bearer tokens on /api/v2 because the client identifier
            // is not a Mautic username.
            return new SelfValidatingPassport(
                new UserBadge(
                    $user?->getUserIdentifier() ?? $client->getUserIdentifier(),
                    $userLoader,
                ),
                [$accessTokenBadge]
            );
        } catch (OAuth2ServerException $e) {
            throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
        }
    }

    /**
     * A BC compatible response.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();
        if ($previous instanceof OAuth2ServerException) {
            return $previous->getHttpResponse();
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}
