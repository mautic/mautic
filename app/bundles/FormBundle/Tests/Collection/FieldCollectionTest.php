<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Collection;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Exception\FieldNotFoundException;

final class FieldCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testToChoicesWithObjects(): void
    {
        $collection = new FieldCollection(
            [
                new FieldCrate('6', 'email', 'email', []),
                new FieldCrate('7', 'first_name', 'text', []),
            ]
        );

        $this->assertSame(
            [
                'email'      => '6',
                'first_name' => '7',
            ],
            $collection->toChoices()
        );
    }

    public function testToChoicesWithoutObjects(): void
    {
        $collection = new FieldCollection();

        $this->assertSame([], $collection->toChoices());
    }

    public function testGetFieldByKey(): void
    {
        $field6     = new FieldCrate('6', 'email', 'email', []);
        $field7     = new FieldCrate('7', 'first_name', 'text', []);
        $collection = new FieldCollection([$field6, $field7]);

        $this->assertSame($field6, $collection->getFieldByKey('6'));
        $this->assertSame($field7, $collection->getFieldByKey('7'));

        $this->expectException(FieldNotFoundException::class);
        $collection->getFieldByKey('8');
    }

    public function testRemoveFieldsWithKeysWithNoKeyToKeep(): void
    {
        $field6             = new FieldCrate('6', 'email', 'email', []);
        $field7             = new FieldCrate('7', 'first_name', 'text', []);
        $field8             = new FieldCrate('8', 'last_name', 'text', []);
        $originalCollection = new FieldCollection([$field6, $field7, $field8]);
        $resultCollection   = $originalCollection->removeFieldsWithKeys(['6', '8']);

        // It should return a clone of the original collection. Not mutation.
        $this->assertNotSame($originalCollection, $resultCollection);
        $this->assertCount(1, $resultCollection);
        $this->assertSame($field7, $resultCollection->getFieldByKey('7'));
    }

    public function testRemoveFieldsWithKeysWithKeyToKeep(): void
    {
        $field6             = new FieldCrate('6', 'email', 'email', []);
        $field7             = new FieldCrate('7', 'first_name', 'text', []);
        $field8             = new FieldCrate('8', 'last_name', 'text', []);
        $originalCollection = new FieldCollection([$field6, $field7, $field8]);
        $resultCollection   = $originalCollection->removeFieldsWithKeys(['6', '8'], '8');

        $this->assertCount(2, $resultCollection);
        $this->assertSame($field7, $resultCollection->getFieldByKey('7'));
        $this->assertSame($field8, $resultCollection->getFieldByKey('8'));
    }
}
