<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Form\DataTransformer;

use Mautic\CoreBundle\Form\DataTransformer\SortableListTransformer;
use PHPUnit\Framework\TestCase;

class SortableListTransformerTest extends TestCase
{
    /**
     * @dataProvider standardListProvider
     *
     * @param array<string, array<int|string, string>>    $input
     * @param array<string, array<array<string, string>>> $expected
     */
    public function testTransformStandardListWithLabels(array $input, array $expected): void
    {
        $transformer = new SortableListTransformer(withLabels: true, useKeyValuePairs: false);
        $result      = $transformer->transform($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider keyValuePairProvider
     *
     * @param array<string, string>                       $input
     * @param array<string, array<array<string, string>>> $expected
     */
    public function testTransformKeyValuePairs(array $input, array $expected): void
    {
        $transformer = new SortableListTransformer(withLabels: true, useKeyValuePairs: true);
        $result      = $transformer->transform($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider standardListWithoutLabelsProvider
     *
     * @param array<string, array<int|string, string>> $input
     * @param array<string, array<string>>             $expected
     */
    public function testTransformListWithoutLabels(array $input, array $expected): void
    {
        $transformer = new SortableListTransformer(withLabels: false, useKeyValuePairs: false);
        $result      = $transformer->transform($input);

        $this->assertEquals($expected, $result);
    }

    public function testTransformHandlesNullInput(): void
    {
        $transformer = new SortableListTransformer();
        $result      = $transformer->transform(null);

        $this->assertEquals(['list' => []], $result);
    }

    public function testReverseTransformHandlesNullInput(): void
    {
        $transformer = new SortableListTransformer(useKeyValuePairs: true);
        $result      = $transformer->reverseTransform(null);

        $this->assertEquals([], $result);
    }

    /**
     * @dataProvider reverseKeyValuePairProvider
     *
     * @param array<string, array<array<string, string>>> $input
     * @param array<string, string>                       $expected
     */
    public function testReverseTransformKeyValuePairs(array $input, array $expected): void
    {
        $transformer = new SortableListTransformer(withLabels: true, useKeyValuePairs: true);
        $result      = $transformer->reverseTransform($input);

        $this->assertEquals($expected, $result);
    }

    public function testReverseTransformHandlesMissingLabels(): void
    {
        $transformer = new SortableListTransformer(useKeyValuePairs: true);
        $input       = [
            'list' => [
                ['value' => 'test1'], // missing label
                ['label' => 'key2', 'value' => 'test2'],
            ],
        ];
        $expected = ['key2' => 'test2'];

        $result = $transformer->reverseTransform($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{input: array<string, array<int|string, string>>, expected: array<string, array<array<string, string>>>}>
     */
    public function standardListProvider(): array
    {
        return [
            'simple list' => [
                'input' => [
                    'list' => [
                        3 => 'a@example.com',
                        2 => 'b@example.com',
                    ],
                ],
                'expected' => [
                    'list' => [
                        ['label' => 'a@example.com', 'value' => 'a@example.com'],
                        ['label' => 'b@example.com', 'value' => 'b@example.com'],
                    ],
                ],
            ],
            'non sequential indexes' => [
                'input' => [
                    'list' => ['item1', 'item2', 'item3'],
                ],
                'expected' => [
                    'list' => [
                        ['label' => 'item1', 'value' => 'item1'],
                        ['label' => 'item2', 'value' => 'item2'],
                        ['label' => 'item3', 'value' => 'item3'],
                    ],
                ],
            ],
            'empty list' => [
                'input'    => ['list' => []],
                'expected' => ['list' => []],
            ],
        ];
    }

    /**
     * @return array<string, array{input: array<string, array<int|string, string>>, expected: array<string, array<string>>}>
     */
    public function standardListWithoutLabelsProvider(): array
    {
        return [
            'simple list without labels' => [
                'input' => [
                    'list' => ['item1', 'item2', 'item3'],
                ],
                'expected' => [
                    'list' => ['item1', 'item2', 'item3'],
                ],
            ],
            'non sequential indexes' => [
                'input' => [
                    'list' => [
                        6 => 'item1',
                        3 => 'item2',
                        4 => 'item3',
                    ],
                ],
                'expected' => [
                    'list' => ['item1', 'item2', 'item3'],
                ],
            ],
            'empty list' => [
                'input'    => ['list' => []],
                'expected' => ['list' => []],
            ],
        ];
    }

    /**
     * @return array<string, array{input: array<string, string>, expected: array<string, array<array<string, string>>>}>
     */
    public function keyValuePairProvider(): array
    {
        return [
            'key value pairs' => [
                'input' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
                'expected' => [
                    'list' => [
                        ['label' => 'key1', 'value' => 'value1'],
                        ['label' => 'key2', 'value' => 'value2'],
                    ],
                ],
            ],
            'empty array' => [
                'input'    => [],
                'expected' => ['list' => []],
            ],
        ];
    }

    /**
     * @return array<string, array{input: array<string, array<array<string, string>>>, expected: array<string, string>}>
     */
    public function reverseKeyValuePairProvider(): array
    {
        return [
            'standard key-value pairs' => [
                'input' => [
                    'list' => [
                        ['label' => 'key1', 'value' => 'value1'],
                        ['label' => 'key2', 'value' => 'value2'],
                    ],
                ],
                'expected' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            'empty list' => [
                'input'    => ['list' => []],
                'expected' => [],
            ],
        ];
    }
}
