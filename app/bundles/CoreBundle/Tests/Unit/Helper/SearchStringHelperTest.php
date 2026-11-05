<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\SearchStringHelper;

final class SearchStringHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testNegativeGroup(): void
    {
        $result = SearchStringHelper::parseSearchString('email:!(test@example.%)');

        $this->assertArrayHasKey('email', $result->commands);
        $this->assertEquals('email', $result->root[0]->command);
        $this->assertEquals('test@example.%', $result->root[0]->string);
        $this->assertEquals(1, $result->root[0]->not);
    }

    public function testCommandWithoutValueIsMarkedAsMissing(): void
    {
        $result = SearchStringHelper::parseSearchString('form:');

        $this->assertSame('form', $result->root[0]->command);
        $this->assertSame('', $result->root[0]->string);
        $this->assertTrue($result->root[0]->missingValue);
        $this->assertSame(SearchStringHelper::COMMAND_POSIT, $result->commands['form']);
    }

    /**
     * @param array<int, array{command: string, string: string, type: string}> $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('multipleSpacesProvider')]
    public function testMultipleSpacesBetweenTerms(string $searchString, array $expected): void
    {
        $result = SearchStringHelper::parseSearchString($searchString);

        $actual = array_map(
            fn (\stdClass $filter): array => [
                'command' => $filter->command,
                'string'  => $filter->string,
                'type'    => $filter->type,
            ],
            array_values($result->root)
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, array<int, array{command: string, string: string, type: string}>}>
     */
    public static function multipleSpacesProvider(): iterable
    {
        yield 'three spaces' => ['a   b', [
            ['command' => '', 'string' => 'a', 'type' => 'and'],
            ['command' => '', 'string' => 'b', 'type' => 'and'],
        ]];

        yield 'three spaces followed by another term' => ['a   b c', [
            ['command' => '', 'string' => 'a', 'type' => 'and'],
            ['command' => '', 'string' => 'b', 'type' => 'and'],
            ['command' => '', 'string' => 'c', 'type' => 'and'],
        ]];

        yield 'six spaces' => ['a      b', [
            ['command' => '', 'string' => 'a', 'type' => 'and'],
            ['command' => '', 'string' => 'b', 'type' => 'and'],
        ]];

        yield 'OR followed by multiple spaces' => ['a OR   b', [
            ['command' => '', 'string' => 'a', 'type' => 'and'],
            ['command' => '', 'string' => 'b', 'type' => 'or'],
        ]];

        yield 'command followed by multiple spaces' => ['email:   b', [
            ['command' => 'email', 'string' => 'b', 'type' => 'and'],
        ]];
    }
}
