<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\HtmlExtension;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class HtmlExtensionTest extends TestCase
{
    /**
     * @dataProvider convertStringToArrayProvider
     *
     * @param array<string, mixed> $expected
     */
    public function testConvertStringToArray(string $input, array|bool $expected): void
    {
        $extension = new HtmlExtension();

        $actual = $extension->convertHtmlAttributesToArray($input);

        Assert::assertSame($expected, $actual);
    }

    /**
     * @return array<int, mixed>
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
