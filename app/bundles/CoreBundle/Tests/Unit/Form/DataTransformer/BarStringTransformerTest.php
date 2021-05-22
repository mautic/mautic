<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Form\DataTransformer;

use Mautic\CoreBundle\Form\DataTransformer\BarStringTransformer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class BarStringTransformerTest extends TestCase
{
    /**
     * @dataProvider transformProvider
     */
    public function testTransform($value, $expected): void
    {
        $transformer = new BarStringTransformer();
        Assert::assertSame($expected, $transformer->transform($value));
    }

    public function transformProvider(): \Generator
    {
        yield [null, ''];
        yield [[], ''];
        yield [123, ''];
        yield [new \StdClass(), ''];
        yield ['', ''];
        yield ['value A', ''];
        yield [['value A'], 'value A'];
        yield [['value A', 'value B'], 'value A|value B'];
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($value, $expected): void
    {
        $transformer = new BarStringTransformer();
        Assert::assertSame($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformProvider(): \Generator
    {
        yield [null, []];
        yield [[], []];
        yield [123, []];
        yield [new \StdClass(), []];
        yield ['', ['']];
        yield ['value A', ['value A']];
        yield ['value A|value B', ['value A', 'value B']];
        yield ['value A| value B  |  | value C', ['value A', 'value B', '', 'value C']];
    }
}
