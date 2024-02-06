<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Helper;

use Mautic\CoreBundle\Doctrine\Helper\FulltextKeyword;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class FulltextKeywordTest extends TestCase
{
    /**
     * @dataProvider dataDefault
     */
    public function testDefault(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value);

        Assert::assertSame($expected, $fulltextKeyword->format());
        Assert::assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataDefault(): iterable
    {
        yield ['some word', '(+some* +word*) >"some word"'];
        yield ['another', '(+another*) >"another"'];
        yield ['s', '(+s*) >"s"'];
        yield ['', ''];
    }

    /**
     * @dataProvider dataInflectingEnabled
     */
    public function testInflectingEnabled(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value, true, true, true);

        Assert::assertSame($expected, $fulltextKeyword->format());
        Assert::assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataInflectingEnabled(): iterable
    {
        yield ['some word', '(+(some* <som*) +(word* <wor*)) >"some word"'];
        yield ['another', '(+(another* <anothe*)) >"another"'];
        yield ['s', '(+s*) >"s"'];
        yield ['', ''];
    }

    /**
     * @dataProvider dataWordSearchDisabled
     */
    public function testWordSearchDisabled(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value, true, false);

        Assert::assertSame($expected, $fulltextKeyword->format());
        Assert::assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataWordSearchDisabled(): iterable
    {
        yield ['some word', '"some word"'];
        yield ['another', '"another"'];
        yield ['s', '"s"'];
        yield ['', ''];
    }

    /**
     * @dataProvider dataBooleanModeDisabled
     */
    public function testBooleanModeDisabled(string $value, string $expected): void
    {
        $fulltextKeyword = new FulltextKeyword($value, false);

        Assert::assertSame($expected, $fulltextKeyword->format());
        Assert::assertSame($expected, (string) $fulltextKeyword);
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataBooleanModeDisabled(): iterable
    {
        yield ['some word', 'some word'];
        yield ['another', 'another'];
        yield ['s', 's'];
        yield ['', ''];
    }
}
