<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollector;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Cache\CacheItem;

final class AlreadyMappedFieldCollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&CacheProviderInterface
     */
    private MockObject $cacheProvider;

    private AlreadyMappedFieldCollector $collector;

    protected function setup(): void
    {
        parent::setUp();

        $this->cacheProvider = $this->createMock(CacheProviderInterface::class);
        $this->collector     = new AlreadyMappedFieldCollector($this->cacheProvider);
    }

    public function testWorkflow(): void
    {
        $createCacheItem = \Closure::bind(
            function () {
                $item             = new CacheItem();
                $item->isHit      = false;
                $item->isTaggable = true;

                return $item;
            },
            $this,
            CacheItem::class
        );
        $cacheItem = $createCacheItem();

        $formId    = '3';
        $object    = 'contact';

        $this->cacheProvider->method('getItem')
            ->with('mautic.form.3.object.contact.fields.mapped')
            ->willReturn($cacheItem);

        $this->cacheProvider->expects($this->exactly(4))
            ->method('save')
            ->with($cacheItem);

        // Ensure we get an empty array at the beginning.
        $this->assertNull($cacheItem->get());
        $this->assertSame([], $this->collector->getFields($formId, $object));

        // Add a mapped field.
        $this->collector->addField('3', 'contact', '44');
        $this->assertSame(['44'], $this->collector->getFields($formId, $object));

        // The field with key 44 should be added to the cache item.
        $this->assertSame('["44"]', $cacheItem->get());

        // Add another mapped field.
        $this->collector->addField('3', 'contact', '55');

        // The field with key 55 should be added to the cache item.
        $this->assertSame('["44","55"]', $cacheItem->get());
        $this->assertSame(['44', '55'], $this->collector->getFields($formId, $object));

        // Remove an exsting field.
        $this->collector->removeField('3', 'contact', '44');

        // The field with key 44 should be removed from the cache item.
        $this->assertSame('["55"]', $cacheItem->get());
        $this->assertSame(['55'], $this->collector->getFields($formId, $object));

        // Remove a not exsting field.
        $this->collector->removeField('3', 'contact', '44');

        // Still the same result after removing a field that did not exist.
        $this->assertSame('["55"]', $cacheItem->get());
        $this->assertSame(['55'], $this->collector->getFields($formId, $object));

        $this->cacheProvider->expects($this->once())
            ->method('invalidateTags')
            ->with(['mautic.form.3.fields.mapped']);

        $this->collector->removeAllForForm($formId);
    }
}
