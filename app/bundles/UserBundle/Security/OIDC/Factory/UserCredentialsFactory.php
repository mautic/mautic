<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Factory;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserCredentialsFactoryInterface::class)]
final readonly class UserCredentialsFactory implements UserCredentialsFactoryInterface
{
    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private ClientCredentials $clientCredentials,
        private LoggerInterface $logger,
    ) {
    }

    public function create(): UserCredentials
    {
        // Create client on-demand to avoid HTTP requests during container compilation
        $client   = $this->clientFactory->create($this->clientCredentials);
        $claims   = ['email', 'preferred_username', 'given_name', 'family_name', $client->getMappingField()];
        $userInfo = [];

        try {
            $userInfo = $client->requestUserInfo($claims);
        } catch (OidcAuthorizationException $e) {
        }

        try {
            $tokens = $client->getVerifiedClaims($claims);
        } catch (OidcAuthorizationException $e) {
            throw new OidcException('mautic.open_id.login.exception.user_info', $e->getCode(), $e);
        }

        $this->logger->debug('OpenID Connect: User info', $userInfo);
        $this->logger->debug('OpenID Connect: Token claims', $tokens);

        // values from the token have precedence over the userinfo endpoint
        $data = array_merge($userInfo, $tokens);
        // values are not guaranteed to be set
        $id                = $data[$client->getMappingField()] ?? null;
        $email             = $data['email'] ?? null;
        $preferredUsername = $data['preferred_username'] ?? null;
        $givenName         = $data['given_name'] ?? null;
        $familyName        = $data['family_name'] ?? null;

        if (!$id) {
            $this->logger->error('Unable to locate identifier field in response', ['mapping_field' => $client->getMappingField(), 'data' => $data]);
            throw new OidcException('mautic.open_id.login.exception.invalid_mapping_field');
        }

        return new UserCredentials($id, $email, $preferredUsername, $givenName, $familyName);
    }
}
