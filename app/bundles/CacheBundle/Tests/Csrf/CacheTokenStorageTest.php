<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Tests\Csrf;

use Closure;
use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CacheBundle\Csrf\CacheTokenStorage;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;

class CacheTokenStorageTest extends TestCase
{
    /**
     * @var CacheProviderInterface|MockObject
     */
    private $cache;

    /**
     * @var MockObject|SessionInterface
     */
    private $session;

    /**
     * @var CacheTokenStorage
     */
    private $cacheTokenStorage;

    /**
     * @var string[]
     */
    private $sessionStorage = [];

    /**
     * @var array<string, CacheItem>
     */
    private $cacheStorage   = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->cache             = $this->createMock(CacheProviderInterface::class);
        $this->session           = $this->createMock(SessionInterface::class);
        $this->cacheTokenStorage = new CacheTokenStorage($this->cache, $this->session);

        $createCacheItem = Closure::bind(
            static function ($key) {
                $item = new CacheItem();
                $item->key = CacheItem::validateKey($key);
                $item->isHit = false;

                return $item;
            },
            null,
            CacheItem::class
        );

        $this->cache->method('hasItem')
            ->willReturnCallback(function ($key) {
                return array_key_exists($key, $this->cacheStorage);
            });

        $this->cache->method('getItem')
            ->willReturnCallback(function ($key) use ($createCacheItem) {
                if (array_key_exists($key, $this->cacheStorage)) {
                    return $this->cacheStorage[$key];
                }

                return $this->cacheStorage[$key] = $createCacheItem($key);
            });

        $this->cache->method('deleteItem')
            ->willReturnCallback(function ($key) {
                unset($this->cacheStorage[$key]);

                return true;
            });

        $this->cache->method('save')
            ->willReturnCallback(function (CacheItem $item) {
                return $this->cacheStorage[$item->getKey()] = $item;
            });

        $this->session->method('isStarted')
            ->willReturn(false);

        $this->session->method('has')
            ->willReturnCallback(function ($key) {
                return array_key_exists($key, $this->sessionStorage);
            });

        $this->session->method('set')
            ->willReturnCallback(function ($key, $value) {
                $this->sessionStorage[$key] = $value;
            });

        $this->session->method('get')
            ->willReturnCallback(function ($key) {
                return $this->sessionStorage[$key];
            });

        $this->session->method('remove')
            ->willReturnCallback(function ($key) {
                $value = $this->sessionStorage[$key] ?? null;

                unset($this->sessionStorage[$key]);

                return $value;
            });
    }

    public function testTokenNotFoundException(): void
    {
        $this->expectException(TokenNotFoundException::class);

        $this->cacheTokenStorage->getToken('example');
    }

    public function testTokenLifecycle(): void
    {
        $this->cacheTokenStorage->setToken('example', 'token-value');
        $keyName = $this->getNamespace($this->cacheTokenStorage).'_example';

        // token added to cache
        Assert::assertArrayHasKey($keyName, $this->cacheStorage);

        // token key saved in session for tracking & deletion
        Assert::assertContains($keyName, $this->sessionStorage[CacheTokenStorage::SESSION_KEY_TOKEN_KEYS]);

        // token identifier saved in session
        Assert::assertArrayHasKey(CacheTokenStorage::SESSION_KEY_TOKEN_IDENTIFIER, $this->sessionStorage);

        Assert::assertSame('token-value', $this->cacheTokenStorage->getToken('example'));

        $this->cacheTokenStorage->removeToken('example');

        // token deleted from cache
        Assert::assertArrayNotHasKey($keyName, $this->cacheStorage);

        $this->cache->expects($this->once())
            ->method('invalidateTags');

        $this->cacheTokenStorage->clear();

        // Session has been cleared of csrf related items
        Assert::assertArrayNotHasKey(CacheTokenStorage::SESSION_KEY_TOKEN_IDENTIFIER, $this->sessionStorage);
        Assert::assertArrayNotHasKey(CacheTokenStorage::SESSION_KEY_TOKEN_KEYS, $this->sessionStorage);

        Assert::assertFalse($this->cacheTokenStorage->hasToken('example'));
    }

    public function testRemovingUnknownTokenReturnsNull(): void
    {
        Assert::assertNull($this->cacheTokenStorage->removeToken('example'));
    }

    public function testExistingSessionWithSessionKeyTokenIdentifierUsesThatTokenIdentifierInsteadOfGeneratingNew(): void
    {
        $this->sessionStorage[CacheTokenStorage::SESSION_KEY_TOKEN_IDENTIFIER] = 'example_identifier';

        Assert::assertFalse($this->cacheTokenStorage->hasToken('example'));
        Assert::assertSame(
            sprintf(CacheTokenStorage::TOKEN_TEMPLATE, 'example_identifier'),
            $this->getNamespace($this->cacheTokenStorage)
        );
    }

    private function getNamespace(CacheTokenStorage $object): ?string
    {
        return Closure::bind(function ($object) {
            return $object->namespace;
        }, null, CacheTokenStorage::class)($object);
    }
}
