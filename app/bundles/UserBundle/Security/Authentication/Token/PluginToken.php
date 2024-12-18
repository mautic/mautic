<?php

namespace Mautic\UserBundle\Security\Authentication\Token;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class PluginToken extends AbstractToken
{
    private ?string $providerKey;

    /**
     * @param UserInterface|string|null $user
     * @param array<string>             $roles
     */
    public function __construct(
        ?string $providerKey,
        private ?string $authenticatingService = null,
        $user = null,
        private string $credentials = '',
        array $roles = [],
        private ?Response $response = null,
    ) {
        parent::__construct($roles);

        if ('' === $providerKey) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        if (is_string($user)) {
            $user = null;
        }

        if (null !== $user) {
            $this->setUser($user);
        }

        $this->providerKey = $providerKey;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getProviderKey(): ?string
    {
        return $this->providerKey;
    }

    public function getAuthenticatingService(): ?string
    {
        return $this->authenticatingService;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * @return array<int, mixed>
     */
    public function __serialize(): array
    {
        return array_merge([$this->authenticatingService, $this->credentials, $this->providerKey, parent::__serialize()]);
    }

    /**
     * @param array<int, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        [$this->authenticatingService, $this->credentials, $this->providerKey, $parentArray] = $data;
        parent::__unserialize($parentArray);
    }
}
