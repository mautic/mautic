<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Csrf;

use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

class CacheTokenStorage implements ClearableTokenStorageInterface
{
    public const TOKEN_TEMPLATE               = '_csrf_%s';
    public const SESSION_KEY_TOKEN_IDENTIFIER = 'csrf_token_identifier';
    public const SESSION_KEY_TOKEN_KEYS       = 'csrf_token_keys';

    private ?string $namespace = null;

    public function __construct(private CacheProviderTagAwareInterface $cache, private RequestStack $requestStack)
    {
    }

    private function getSession(): SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return new Session();
        }
    }

    public function clear(): void
    {
        $this->init();

        $this->cache->invalidateTags([$this->namespace]);

        $this->removeKnownTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(mixed $tokenId): string
    {
        $this->init();

        if (false === $this->hasToken($tokenId)) {
            throw new TokenNotFoundException('The CSRF token with ID '.$tokenId.' does not exist.');
        }

        return (string) $this->cache->getItem($this->getKey($tokenId))->get();
    }

    /**
     * @param string $tokenId
     * @param string $token
     */
    public function setToken($tokenId, $token): void
    {
        $this->init();

        $item = $this->cache->getItem($tokenKey = $this->getKey($tokenId))->set($token);

        $item->expiresAfter(new \DateInterval('PT2H')); // 2 hours
        $item->tag($this->namespace);

        $this->cache->save($item);

        $this->trackTokenForRemoval($tokenKey);
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken(mixed $tokenId): ?string
    {
        $this->init();

        try {
            $token = $this->getToken($tokenId);
        } catch (TokenNotFoundException) {
            return null;
        }

        $this->cache->deleteItem($this->getKey($tokenId));

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken(mixed $tokenId): bool
    {
        $this->init();

        return $this->cache->hasItem($this->getKey($tokenId));
    }

    // Prepends the namespace to the key.
    private function getKey(string $tokenId): string
    {
        return $this->namespace.'_'.$tokenId;
    }

    // Starts the session and sets the namespace
    private function init(): void
    {
        if (isset($this->namespace)) {
            return;
        }

        $session = $this->getSession();

        if (false === $session->isStarted()) {
            $session->start();
        }

        if ($session->has(static::SESSION_KEY_TOKEN_IDENTIFIER)) {
            $tokenIdentifier = $session->get(static::SESSION_KEY_TOKEN_IDENTIFIER);
        } else {
            $session->set(static::SESSION_KEY_TOKEN_IDENTIFIER, $tokenIdentifier = Uuid::uuid4()->toString());
            $session->set(static::SESSION_KEY_TOKEN_KEYS, []);
        }

        $this->namespace = sprintf(static::TOKEN_TEMPLATE, $tokenIdentifier);
    }

    /**
     * Tracks the CSRF token key by adding it to the session.
     */
    private function trackTokenForRemoval(string $tokenKey): void
    {
        $session     = $this->getSession();
        $tokenKeys   = $session->get(static::SESSION_KEY_TOKEN_KEYS, []);
        $tokenKeys[] = $tokenKey;

        $session->set(static::SESSION_KEY_TOKEN_KEYS, $tokenKeys);
    }

    /**
     * Gets list of tracked CSRF token keys from the session then deletes
     * them from cache. After deletion, remove the session keys that were
     * added then set the namespace and tag on this class to null so that
     * if tokens are needed again, the namespace gets regenerated.
     */
    private function removeKnownTokens(): void
    {
        $session   = $this->getSession();
        $tokenKeys = (array) $session->get(static::SESSION_KEY_TOKEN_KEYS);

        foreach ($tokenKeys as $tokenKey) {
            $this->cache->deleteItem($tokenKey);
        }

        $session->remove(static::SESSION_KEY_TOKEN_KEYS);
        $session->remove(static::SESSION_KEY_TOKEN_IDENTIFIER);

        $this->namespace = null;
    }
}
