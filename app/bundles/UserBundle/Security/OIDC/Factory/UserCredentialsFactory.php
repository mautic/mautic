<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Factory;

use Mautic\OpenIdBundle\Exception\AuthorizationRequestFailedException;
use Mautic\OpenIdBundle\Exception\InvalidMappedIdentifierException;
use Mautic\OpenIdBundle\Exception\UserInfoException;
use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Psr\Log\LoggerInterface;

final class UserCredentialsFactory implements UserCredentialsFactoryInterface
{
    private LoggerInterface $logger;
    private ClientInterface $client;

    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {
        $this->logger     = $logger;
        $this->client     = $client;
    }

    public function create(): UserCredentials
    {
        $claims   = ['email', 'preferred_username', 'given_name', 'family_name', $this->client->getMappingField()];

        try {
            $userInfo = $this->client->requestUserInfo($claims);
            $tokens   = $this->client->getVerifiedClaims($claims);
        } catch (AuthorizationRequestFailedException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            throw new UserInfoException('mautic.open_id.login.exception.user_info');
        }

        $this->logger->debug('OpenID Connect: User info', $userInfo);
        $this->logger->debug('OpenID Connect: Token claims', $tokens);

        // values from the token have precedence over the userinfo endpoint
        $data = array_merge($userInfo, $tokens);
        // values are not guaranteed to be set
        $id                = $data[$this->client->getMappingField()] ?? null;
        $email             = $data['email'] ?? null;
        $preferredUsername = $data['preferred_username'] ?? null;
        $givenName         = $data['given_name'] ?? null;
        $familyName        = $data['family_name'] ?? null;

        if (!$id) {
            $this->logger->error('Unable to locate identifier field in response', ['mapping_field' => $this->client->getMappingField(), 'data' => $data]);
            throw new InvalidMappedIdentifierException('mautic.open_id.login.exception.invalid_mapping_field');
        }

        return new UserCredentials($id, $email, $preferredUsername, $givenName, $familyName);
    }
}
