<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Crate;

use Mautic\FormBundle\Crate\FieldCrate;

final class FieldCrateTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersForEmailField(): void
    {
        $field = new FieldCrate('6', 'Email', 'email', []);

        $this->assertSame('6', $field->getKey());
        $this->assertSame('Email', $field->getName());
        $this->assertSame('email', $field->getType());
        $this->assertSame([], $field->getProperties());
        $this->assertFalse($field->isListType());
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

        $this->assertSame('7', $field->getKey());
        $this->assertSame('Colors', $field->getName());
        $this->assertSame('select', $field->getType());
        $this->assertSame($properties, $field->getProperties());
        $this->assertTrue($field->isListType());
    }
}
