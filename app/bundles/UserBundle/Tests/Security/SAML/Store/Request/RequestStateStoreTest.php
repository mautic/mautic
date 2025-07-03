<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\SAML\Store\Request;

use LightSaml\State\Request\RequestState;
use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\UserBundle\Security\SAML\Store\Request\RequestStateStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\CacheItem;

class RequestStateStoreTest extends TestCase
{
    /**
     * @var CacheProviderInterface&MockObject
     */
    private MockObject $cacheProvider;

    private CacheItem $cacheItem;

    private RequestStateStore $requestStateStore;
    private string $cachePrefix = 'prefix_suffix';
    private string $stateId     = 'state_id';

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheItem = new CacheItem();

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

        $this->cacheProvider->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $this->requestStateStore->set($state);

        $check = \Closure::bind(
            static function (CacheItem $actual, TestCase $case) use ($state): void {
                $case::assertEqualsWithDelta(
                    (2 * 60) + microtime(true),
                    $actual->expiry,
                    1
                );
                $case::assertSame($state, $actual->value);
            },
            null,
            CacheItem::class
        );
        ($check)($this->cacheItem, $this);
    }

    public function testGetNotHit(): void
    {
        self::assertNull($this->requestStateStore->get($this->stateId));
    }

    public function testGetIsHitButNotRequestState(): void
    {
        $setUp = \Closure::bind(
            static function (CacheItem $item): void {
                $item->isHit = true;
                $item->value = 'string';
            },
            null,
            CacheItem::class
        );
        ($setUp)($this->cacheItem);

        self::assertNull($this->requestStateStore->get($this->stateId));
    }

    public function testGetIsHitRequestState(): void
    {
        $state = $this->createMock(RequestState::class);

        $setUp = \Closure::bind(
            static function (CacheItem $item, RequestState $state): void {
                $item->isHit = true;
                $item->value = $state;
            },
            null,
            CacheItem::class
        );
        ($setUp)($this->cacheItem, $state);

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
