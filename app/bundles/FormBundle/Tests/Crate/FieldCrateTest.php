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

namespace Mautic\FormBundle\Tests\Crate;

use Mautic\FormBundle\Crate\FieldCrate;
use PHPUnit\Framework\Assert;

final class FieldCrateTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersForEmailField()
    {
        $field = new FieldCrate('6', 'Email', 'email', []);

        Assert::assertSame('6', $field->getKey());
        Assert::assertSame('Email', $field->getName());
        Assert::assertSame('email', $field->getType());
        Assert::assertSame([], $field->getProperties());
        Assert::assertFalse($field->isListType());
    }

    public function testGettersForSelectField()
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
