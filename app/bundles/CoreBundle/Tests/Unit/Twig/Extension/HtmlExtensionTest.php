<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\HtmlExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlExtensionTest extends TestCase
{
    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('convertStringToArrayProvider')]
    public function testConvertStringToArray(string $input, array|bool $expected): void
    {
        $extension = new HtmlExtension();

        $actual = $extension->convertHtmlAttributesToArray($input);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return iterable<int, mixed>
     */
    public static function convertStringToArrayProvider(): iterable
    {
        yield ['id="test-id" class="test-class"', [
            'id'    => 'test-id',
            'class' => ['test-class'],
        ]];

        yield ['id="test-id" class="test-class-one test-class-two"', [
            'id'    => 'test-id',
            'class' => ['test-class-one', 'test-class-two'],
        ]];

        yield ['id=" test-id " class=" test-class-one      test-class-two           "', [
            'id'    => 'test-id',
            'class' => ['test-class-one', 'test-class-two'],
        ]];

        yield ['', []];
    }

    #[DataProvider('htmlEntityDecodeProvider')]
    public function testHtmlEntityDecode(string $input, string $expected): void
    {
        $extension = new HtmlExtension();

        $this->assertSame($expected, $extension->htmlEntityDecode($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function htmlEntityDecodeProvider(): iterable
    {
        yield 'ampersand entity' => ['Peculiar &amp; Co', 'Peculiar & Co'];
        yield 'numeric ampersand' => ['Peculiar &#38; Co', 'Peculiar & Co'];
        yield 'raw ampersand' => ['Peculiar & Co', 'Peculiar & Co'];
        yield 'empty' => ['', ''];
    }
}
