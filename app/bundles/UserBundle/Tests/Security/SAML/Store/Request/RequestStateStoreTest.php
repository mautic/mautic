<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\SAML\Store\Request;

use LightSaml\State\Request\RequestState;
use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\UserBundle\Security\SAML\Store\Request\RequestStateStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;

class RequestStateStoreTest extends TestCase
{
    /**
     * @var CacheProviderInterface&MockObject
     */
    private MockObject $cacheProvider;

    /**
     * @var MockObject&CacheItemInterface
     */
    private MockObject $cacheItem;

    private RequestStateStore $requestStateStore;
    private string $cachePrefix = 'prefix_suffix';
    private string $stateId     = 'state_id';

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cacheProvider = $this->createMock(CacheProviderInterface::class);
        $this->cacheProvider->method('getItem')
            ->with($this->cachePrefix.$this->stateId)
            ->willReturn($this->cacheItem);

        $this->requestStateStore = new RequestStateStore($this->cacheProvider, 'prefix', '_suffix');
    }

    public function testSet(): void
    {
        $state = $this->createMock(RequestState::class);
        $state->expects(self::once())
            ->method('getId')
            ->willReturn($this->stateId);

        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(2 * 60);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($state);

        $this->cacheProvider->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $this->requestStateStore->set($state);
    }

    public function testGetNotHit(): void
    {
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::never())
            ->method('get');

        self::assertNull($this->requestStateStore->get($this->stateId));
    }

    public function testGetIsHitButNotRequestState(): void
    {
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn('string');

        self::assertNull($this->requestStateStore->get($this->stateId));
    }

    public function testGetIsHitRequestState(): void
    {
        $state = $this->createMock(RequestState::class);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($state);

        self::assertSame($state, $this->requestStateStore->get($this->stateId));
    }

    public function testRemove(): void
    {
        $id = 'whatever';
        $this->cacheProvider->expects(self::once())
            ->method('deleteItem')
            ->with($this->cachePrefix.$id)
            ->willReturn(true);

        self::assertTrue($this->requestStateStore->remove($id));
    }

    public function testClear(): void
    {
        $this->cacheProvider->expects(self::once())
            ->method('clear')
            ->with($this->cachePrefix);

        $this->requestStateStore->clear();
    }
}
