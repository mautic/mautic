<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\DTO;

use Mautic\FormBundle\DTO\TokenDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenDto::class)]
class TokenDtoTest extends TestCase
{
    public static function provideData(): \Generator
    {
        yield 'empty strings' => [
            '',
            '',
            '{=}',
        ];

        yield 'empty string and zero' => [
            '',
            0,
            '{=0}',
        ];

        yield 'non-empty strings 1' => [
            'lorem ipsum',
            'dolor sit amet',
            '{lorem ipsum=dolor sit amet}',
        ];

        yield 'non-empty strings 2' => [
            'lorem ipsum 12',
            'dolor sit amet 34',
            '{lorem ipsum 12=dolor sit amet 34}',
        ];

        yield 'non-empty string and number' => [
            'lorem ipsum',
            123,
            '{lorem ipsum=123}',
        ];
    }

    #[DataProvider('provideData')]
    public function testToString(string $name, string|int $value, string $expected): void
    {
        $tokenDto = new TokenDto($name, $value);
        self::assertEquals($expected, $tokenDto->toString());
    }
}
