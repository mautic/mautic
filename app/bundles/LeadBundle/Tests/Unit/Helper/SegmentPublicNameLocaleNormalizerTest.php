<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Helper\SegmentPublicNameLocaleNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SegmentPublicNameLocaleNormalizerTest extends TestCase
{
    #[DataProvider('equivalentLocales')]
    public function testNormalizesSupportedLocale(string $input, string $expected): void
    {
        $this->assertSame($expected, (new SegmentPublicNameLocaleNormalizer())->normalize($input));
    }

    public static function equivalentLocales(): iterable
    {
        yield ['fr-CH', 'fr_CH'];
        yield ['FR_ch', 'fr_CH'];
        yield [' fr_CH ', 'fr_CH'];
        yield ['fr', 'fr'];
    }

    #[DataProvider('invalidLocales')]
    public function testRejectsUnsupportedOrMalformedLocale(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new SegmentPublicNameLocaleNormalizer())->normalize($input);
    }

    public static function invalidLocales(): iterable
    {
        yield [''];
        yield ['fr_CH_extra_extra'];
        yield ['not a locale'];
        yield ['zz_ZZ'];
    }
}
