<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Session;

use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Getting CSRF token in Symfony 6 is not possible.
 * This class is a workaround to get the same CSRF in the whole app and all the requests.
 * The issue was mostly that the session was not started in the test environment.
 * And when started it was different in each request.
 */
class InMemoryTokenStorage implements ClearableTokenStorageInterface
{
    /**
     * @var array<string,string[]>
     */
    private array $store = [];

    public function __construct(private string $namespace = SessionTokenStorage::SESSION_NAMESPACE)
    {
        $this->store[$this->namespace] = [];
    }

    public function getToken(string $tokenId): string
    {
        if (!isset($this->store[$this->namespace][$tokenId])) {
            throw new TokenNotFoundException('The CSRF token with ID '.$tokenId.' does not exist.');
        }

        return (string) $this->store[$this->namespace][$tokenId];
    }

    public function setToken(string $tokenId, #[\SensitiveParameter] string $token): void
    {
        $this->store[$this->namespace][$tokenId] = $token;
    }

    public function hasToken(string $tokenId): bool
    {
        return isset($this->store[$this->namespace][$tokenId]);
    }

    public function removeToken(string $tokenId): ?string
    {
        if (!isset($this->store[$this->namespace][$tokenId])) {
            return null;
        }

        $token = (string) $this->store[$this->namespace][$tokenId];

        unset($this->store[$this->namespace][$tokenId]);

        return $token;
    }

    public function clear(): void
    {
        $this->store[$this->namespace] = [];
    }
}
