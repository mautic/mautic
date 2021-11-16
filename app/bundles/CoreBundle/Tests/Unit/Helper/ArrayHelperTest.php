<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ArrayHelper;
use PHPUnit\Framework\Assert;

class ArrayHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetValue(): void
    {
        $origin = ['one', 'two' => 'three'];

        $this->assertSame('one', ArrayHelper::getValue(0, $origin));
        $this->assertSame('three', ArrayHelper::getValue('two', $origin));
        $this->assertNull(ArrayHelper::getValue('five', $origin));
        $this->assertSame('default', ArrayHelper::getValue('five', $origin, 'default'));
    }

    public function testPickValue(): void
    {
        $origin = ['one', 'two' => 'three', 'four' => null];

        $this->assertSame('one', ArrayHelper::pickValue(0, $origin));
        $this->assertSame(['two' => 'three', 'four' => null], $origin);
        $this->assertSame('three', ArrayHelper::pickValue('two', $origin));
        $this->assertSame(['four' => null], $origin);
        $this->assertNull(ArrayHelper::pickValue('five', $origin));
        $this->assertSame('default', ArrayHelper::pickValue('five', $origin, 'default'));
        $this->assertNull(ArrayHelper::pickValue('four', $origin, 'default'));
        $this->assertSame([], $origin);
    }

    public function testSelect(): void
    {
        $origin = ['one', 'two' => 'three', 'four' => 'five'];

        $this->assertSame(['two' => 'three'], ArrayHelper::select(['two'], $origin));
        $this->assertSame(['two' => 'three', 'four' => 'five'], ArrayHelper::select(['two', 'four'], $origin));
        $this->assertSame(['one', 'two' => 'three'], ArrayHelper::select(['two', 0], $origin));
    }

    /**
     * @dataProvider removeEmptyValuesProvider
     *
     * @param mixed[] $value
     * @param mixed[] $expected
     */
    public function testRemoveEmptyValues(array $value, array $expected): void
    {
        Assert::assertSame($expected, ArrayHelper::removeEmptyValues($value));
    }

    /**
     * @return \Generator<mixed[]>
     */
    public function removeEmptyValuesProvider(): \Generator
    {
        $object = new \StdClass();
        yield [[null], []];
        yield [[], []];
        yield [[123], [123]];
        yield [[$object], [$object]];
        yield [[''], []];
        yield [['value A', ''], ['value A']];
    }
}
