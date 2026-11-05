<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Helper\SegmentPublicNameLocaleNormalizer;
use Mautic\LeadBundle\Helper\SegmentPublicNameResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SegmentPublicNameResolverTest extends TestCase
{
    #[DataProvider('labels')]
    public function testResolvesName(?string $locale, ?string $default, array $translations, string $expected): void
    {
        $resolver = new SegmentPublicNameResolver(new SegmentPublicNameLocaleNormalizer());

        $this->assertSame($expected, $resolver->resolve($locale, $default, 'Internal name', $translations));
    }

    public static function labels(): iterable
    {
        $translations = ['fr' => 'Nouveautés Mautic', 'fr_CH' => 'Actualités Mautic', 'en' => 'Mautic News'];

        yield 'exact takes precedence' => ['fr-CH', 'Mautic Neuigkeiten', $translations, 'Actualités Mautic'];
        yield 'case normalized exact' => ['FR_ch', 'Mautic Neuigkeiten', $translations, 'Actualités Mautic'];
        yield 'base language' => ['fr_CA', 'Mautic Neuigkeiten', $translations, 'Nouveautés Mautic'];
        yield 'blank exact uses base' => ['fr_CH', 'Mautic Neuigkeiten', ['fr_CH' => ' ', 'fr' => 'Nouveautés Mautic'], 'Nouveautés Mautic'];
        yield 'default' => ['it', 'Mautic Neuigkeiten', $translations, 'Mautic Neuigkeiten'];
        yield 'empty locale' => ['', 'Mautic Neuigkeiten', $translations, 'Mautic Neuigkeiten'];
        yield 'null locale' => [null, 'Mautic Neuigkeiten', $translations, 'Mautic Neuigkeiten'];
        yield 'malformed locale' => ['not a locale', 'Mautic Neuigkeiten', $translations, 'Mautic Neuigkeiten'];
        yield 'internal fallback' => ['it', '', $translations, 'Internal name'];
        yield 'blank translation' => ['fr', 'Mautic Neuigkeiten', ['fr' => ''], 'Mautic Neuigkeiten'];
        yield 'whitespace translation' => ['fr', 'Mautic Neuigkeiten', ['fr' => ' '], 'Mautic Neuigkeiten'];
    }
}
