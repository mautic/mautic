<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Helper;

use Mautic\CoreBundle\Doctrine\Helper\FulltextKeyword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FulltextKeywordTest extends TestCase
{
    #[DataProvider('dataDefault')]
    public function testDefault(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value);

        $this->assertSame($expected, $fulltextKeyword->format());
        $this->assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public static function dataDefault(): iterable
    {
        yield ['some word', '(+some* +word*) >"some word"'];
        yield ['another', '(+another*) >"another"'];
        yield ['s', '(+s*) >"s"'];
        yield ['', ''];
    }

    #[DataProvider('dataInflectingEnabled')]
    public function testInflectingEnabled(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value, true, true, true);

        $this->assertSame($expected, $fulltextKeyword->format());
        $this->assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public static function dataInflectingEnabled(): iterable
    {
        yield ['some word', '(+(some* <som*) +(word* <wor*)) >"some word"'];
        yield ['another', '(+(another* <anothe*)) >"another"'];
        yield ['s', '(+s*) >"s"'];
        yield ['', ''];
    }

    #[DataProvider('dataWordSearchDisabled')]
    public function testWordSearchDisabled(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value, true, false);

        $this->assertSame($expected, $fulltextKeyword->format());
        $this->assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public static function dataWordSearchDisabled(): iterable
    {
        yield ['some word', '"some word"'];
        yield ['another', '"another"'];
        yield ['s', '"s"'];
        yield ['', ''];
    }

    #[DataProvider('dataBooleanModeDisabled')]
    public function testBooleanModeDisabled(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value, false);

        $this->assertSame($expected, $fulltextKeyword->format());
        $this->assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public static function dataBooleanModeDisabled(): iterable
    {
        yield ['some word', 'some word'];
        yield ['another', 'another'];
        yield ['s', 's'];
        yield ['', ''];
    }
}
