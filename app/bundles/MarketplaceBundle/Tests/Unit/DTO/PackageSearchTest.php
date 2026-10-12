<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Unit\DTO;

use Mautic\MarketplaceBundle\DTO\PackageSearch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PackageSearchTest extends TestCase
{
    #[DataProvider('searches')]
    public function testParse(string $search, string $query, string $type): void
    {
        $filter = new PackageSearch($search);

        self::assertSame($query, $filter->query);
        self::assertSame($type, $filter->type);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function searches(): iterable
    {
        yield 'empty' => ['', '', ''];
        yield 'plain text' => [' newsletter recovery ', 'newsletter recovery', ''];
        yield 'plugin' => ['type:mautic-plugin', '', 'mautic-plugin'];
        yield 'theme after text' => ['newsletter type:mautic-theme', 'newsletter', 'mautic-theme'];
        yield 'resource before text' => ['type:mautic-resource recovery', 'recovery', 'mautic-resource'];
        yield 'stale multiple selections' => ['type:mautic-plugin recovery type:mautic-resource', 'recovery', 'mautic-resource'];
        yield 'unknown type stays in query' => ['type:unknown', 'type:unknown', ''];
        yield 'partial value stays in query' => ['type:mautic-theme-extra', 'type:mautic-theme-extra', ''];
        yield 'embedded command stays in query' => ['some-type:mautic-plugin', 'some-type:mautic-plugin', ''];
        yield 'other commands stay in query' => ['vendor:mautic type:mautic-theme', 'vendor:mautic', 'mautic-theme'];
    }
}
