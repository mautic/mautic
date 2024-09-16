<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\DataTransformer;

use Mautic\CoreBundle\Form\DataTransformer\BarStringTransformer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class BarStringTransformerTest extends TestCase
{
    /**
     * @dataProvider transformProvider
     *
     * @param mixed $value
     */
    public function testTransform($value, string $expected): void
    {
        $transformer = new BarStringTransformer();
        Assert::assertSame($expected, $transformer->transform($value));
    }

    /**
     * @return \Generator<array<mixed>>
     */
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
     *
     * @param mixed    $value
     * @param string[] $expected
     */
    public function testReverseTransform($value, array $expected): void
    {
        $transformer = new BarStringTransformer();
        Assert::assertSame($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return \Generator<array<mixed>>
     */
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
