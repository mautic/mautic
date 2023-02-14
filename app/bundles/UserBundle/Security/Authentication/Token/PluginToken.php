<?php

namespace Mautic\UserBundle\Security\Authentication\Token;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class PluginToken extends AbstractToken
{
    private ?string $providerKey;

    private string $isSupportUser;

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
        private readonly ?Response $response = null,
        bool $isSupportUser = false
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
        $this->isSupportUser = $isSupportUser ? 'yes' : 'no';
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getProviderKey(): ?string
    {
        return $this->providerKey;
    }

    public function isSupportUser(): bool
    {
        return 'yes' == $this->isSupportUser;
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
        return [$this->authenticatingService, $this->credentials, $this->providerKey, $this->isSupportUser, parent::__serialize()];
    }

    /**
     * @param array<int, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        [$this->authenticatingService, $this->credentials, $this->providerKey, $this->isSupportUser, $parentArray] = $data;
        parent::__unserialize($parentArray);
    }
}
