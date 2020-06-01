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

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Collection\MappedObjectCollection;
use Mautic\FormBundle\Collector\FieldCollectorInterface;
use Mautic\FormBundle\Collector\MappedObjectCollector;
use PHPUnit\Framework\Assert;

final class MappedObjectCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject()
    {
        $fieldCollector                        = new class() implements FieldCollectorInterface {
            public $getFieldsMethodCallCounter = 0;

            public function getFields(string $object): FieldCollection
            {
                ++$this->getFieldsMethodCallCounter;

                return new FieldCollection();
            }
        };

        $mappedObjectCollector = new MappedObjectCollector($fieldCollector);
        $objectCollection      = $mappedObjectCollector->buildCollection('');
        Assert::assertInstanceOf(MappedObjectCollection::class, $objectCollection);
        Assert::assertCount(0, $objectCollection);
        Assert::assertEquals(0, $fieldCollector->getFieldsMethodCallCounter);
    }

    public function testBuildCollectionForOneObject()
    {
        $fieldCollector                        = new class() implements FieldCollectorInterface {
            public $getFieldsMethodCallCounter = 0;

            public function getFields(string $object): FieldCollection
            {
                Assert::assertSame($object, 'contact');
                ++$this->getFieldsMethodCallCounter;

                return new FieldCollection();
            }
        };

        $mappedObjectCollector = new MappedObjectCollector($fieldCollector);
        $objectCollection      = $mappedObjectCollector->buildCollection('contact');
        Assert::assertInstanceOf(MappedObjectCollection::class, $objectCollection);
        Assert::assertCount(1, $objectCollection);
        Assert::assertEquals(1, $fieldCollector->getFieldsMethodCallCounter);
    }

    public function testBuildCollectionForMultipleObjects()
    {
        $fieldCollector                        = new class() implements FieldCollectorInterface {
            public $getFieldsMethodCallCounter = 0;

            public function getFields(string $object): FieldCollection
            {
                Assert::assertContains($object, ['company', 'contact']);
                ++$this->getFieldsMethodCallCounter;

                return new FieldCollection();
            }
        };

        $mappedObjectCollector = new MappedObjectCollector($fieldCollector);
        $objectCollection      = $mappedObjectCollector->buildCollection('contact', 'company');
        Assert::assertInstanceOf(MappedObjectCollection::class, $objectCollection);
        Assert::assertCount(2, $objectCollection);
        Assert::assertEquals(2, $fieldCollector->getFieldsMethodCallCounter);
    }
}
