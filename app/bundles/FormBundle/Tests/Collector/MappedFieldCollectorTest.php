<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\FormBundle\Collector\MappedFieldCollector;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Cache\CacheItem;

final class MappedFieldCollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|CacheProviderInterface
     */
    private $cacheProvider;

    /**
     * @var MappedFieldCollector
     */
    private $collector;

    protected function setup()
    {
        parent::setUp();

        $this->cacheProvider = $this->createMock(CacheProviderInterface::class);
        $this->collector     = new MappedFieldCollector($this->cacheProvider);
    }

    public function testWorkflow()
    {
        $cacheItem = new CacheItem();
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

        // The field with key 44 should be removed from the cache item.
        $this->assertSame('["55"]', $cacheItem->get());
        $this->assertSame(['55'], $this->collector->getFields($formId, $object));
    }
}
