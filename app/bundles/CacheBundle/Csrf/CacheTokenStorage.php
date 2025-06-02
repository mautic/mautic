<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Csrf;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

class CacheTokenStorage implements ClearableTokenStorageInterface
{
    public const TOKEN_TEMPLATE               = '_csrf_%s';
    public const SESSION_KEY_TOKEN_IDENTIFIER = 'csrf_token_identifier';
    public const SESSION_KEY_TOKEN_KEYS       = 'csrf_token_keys';

    /**
     * @var CacheProviderInterface
     */
    private $cache;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string|null
     */
    private $namespace;

    public function __construct(CacheProviderInterface $cacheProvider, SessionInterface $session)
    {
        $this->cache   = $cacheProvider;
        $this->session = $session;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->init();

        $this->cache->invalidateTags([$this->namespace]);

        $this->removeKnownTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId): string
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
     *
     * @return void
     */
    public function setToken($tokenId, $token)
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
    public function removeToken($tokenId)
    {
        $this->init();

        try {
            $token = $this->getToken($tokenId);
        } catch (TokenNotFoundException $e) {
            return null;
        }

        $this->cache->deleteItem($this->getKey($tokenId));

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId)
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

        if (false === $this->session->isStarted()) {
            $this->session->start();
        }

        if ($this->session->has(static::SESSION_KEY_TOKEN_IDENTIFIER)) {
            $tokenIdentifier = $this->session->get(static::SESSION_KEY_TOKEN_IDENTIFIER);
        } else {
            $this->session->set(static::SESSION_KEY_TOKEN_IDENTIFIER, $tokenIdentifier = Uuid::uuid4()->toString());
            $this->session->set(static::SESSION_KEY_TOKEN_KEYS, []);
        }

        $this->namespace = sprintf(static::TOKEN_TEMPLATE, $tokenIdentifier);
    }

    /**
     * Tracks the CSRF token key by adding it to the session.
     */
    private function trackTokenForRemoval(string $tokenKey): void
    {
        $tokenKeys   = $this->session->get(static::SESSION_KEY_TOKEN_KEYS, []);
        $tokenKeys[] = $tokenKey;

        $this->session->set(static::SESSION_KEY_TOKEN_KEYS, $tokenKeys);
    }

    /**
     * Gets list of tracked CSRF token keys from the session then deletes
     * them from cache. After deletion, remove the session keys that were
     * added then set the namespace and tag on this class to null so that
     * if tokens are needed again, the namespace gets regenerated.
     */
    private function removeKnownTokens(): void
    {
        $tokenKeys = (array) $this->session->get(static::SESSION_KEY_TOKEN_KEYS);

        foreach ($tokenKeys as $tokenKey) {
            $this->cache->deleteItem($tokenKey);
        }

        $this->session->remove(static::SESSION_KEY_TOKEN_KEYS);
        $this->session->remove(static::SESSION_KEY_TOKEN_IDENTIFIER);

        $this->namespace = null;
    }
}
