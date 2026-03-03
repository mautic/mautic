<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collector;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Collector\FieldCollectorInterface;
use Mautic\FormBundle\Collector\MappedObjectCollector;
use PHPUnit\Framework\Assert;

final class MappedObjectCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildCollectionForNoObject(): void
    {
        $fieldCollector                            = new class implements FieldCollectorInterface {
            public int $getFieldsMethodCallCounter = 0;

            public function getFields(string $object): FieldCollection
            {
                ++$this->getFieldsMethodCallCounter;

                return new FieldCollection();
            }
        };

        $mappedObjectCollector = new MappedObjectCollector($fieldCollector);
        $objectCollection      = $mappedObjectCollector->buildCollection('');
        Assert::assertCount(0, $objectCollection);
        Assert::assertEquals(0, $fieldCollector->getFieldsMethodCallCounter);
    }

    public function testBuildCollectionForOneObject(): void
    {
        $fieldCollector                            = new class implements FieldCollectorInterface {
            public int $getFieldsMethodCallCounter = 0;

            public function getFields(string $object): FieldCollection
            {
                Assert::assertSame($object, 'contact');
                ++$this->getFieldsMethodCallCounter;

                return new FieldCollection();
            }
        };

        $mappedObjectCollector = new MappedObjectCollector($fieldCollector);
        $objectCollection      = $mappedObjectCollector->buildCollection('contact');
        Assert::assertCount(1, $objectCollection);
        Assert::assertEquals(1, $fieldCollector->getFieldsMethodCallCounter);
    }

    public function testBuildCollectionForMultipleObjects(): void
    {
        $fieldCollector                            = new class implements FieldCollectorInterface {
            public int $getFieldsMethodCallCounter = 0;

            public function getFields(string $object): FieldCollection
            {
                Assert::assertContains($object, ['company', 'contact']);
                ++$this->getFieldsMethodCallCounter;

                return new FieldCollection();
            }
        };

        $mappedObjectCollector = new MappedObjectCollector($fieldCollector);
        $objectCollection      = $mappedObjectCollector->buildCollection('contact', 'company');
        Assert::assertCount(2, $objectCollection);
        Assert::assertEquals(2, $fieldCollector->getFieldsMethodCallCounter);
    }
}
