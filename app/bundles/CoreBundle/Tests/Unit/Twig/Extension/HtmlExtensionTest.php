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
}
