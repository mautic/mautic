<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Crate;

use Mautic\FormBundle\Crate\FieldCrate;
use PHPUnit\Framework\Assert;

final class FieldCrateTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersForEmailField(): void
    {
        $field = new FieldCrate('6', 'Email', 'email', []);

        Assert::assertSame('6', $field->getKey());
        Assert::assertSame('Email', $field->getName());
        Assert::assertSame('email', $field->getType());
        Assert::assertSame([], $field->getProperties());
        Assert::assertFalse($field->isListType());
    }

    public function testGettersForSelectField(): void
    {
        $properties = [
            'list' => [
                'Red'   => 'red',
                'Green' => 'green',
            ],
        ];
        $field = new FieldCrate('7', 'Colors', 'select', $properties);

        Assert::assertSame('7', $field->getKey());
        Assert::assertSame('Colors', $field->getName());
        Assert::assertSame('select', $field->getType());
        Assert::assertSame($properties, $field->getProperties());
        Assert::assertTrue($field->isListType());
    }
}
