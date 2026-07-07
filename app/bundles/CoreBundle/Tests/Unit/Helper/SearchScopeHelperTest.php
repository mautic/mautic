<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\SearchScopeHelper;
use PHPUnit\Framework\TestCase;

final class SearchScopeHelperTest extends TestCase
{
    private const CONTACT_SCOPES = ['', 'firstname', 'lastname', 'email', 'country', 'ids'];

    public function testParseReturnsStandardForUnscopedSearch(): void
    {
        self::assertSame(
            ['command' => '', 'value' => 'john@example.com'],
            SearchScopeHelper::parse('john@example.com', self::CONTACT_SCOPES)
        );
    }

    public function testParseSplitsScopedSearch(): void
    {
        self::assertSame(
            ['command' => 'firstname', 'value' => 'John'],
            SearchScopeHelper::parse('firstname:John', self::CONTACT_SCOPES)
        );
    }

    public function testParseMatchesLongestCommandFirst(): void
    {
        self::assertSame(
            ['command' => 'is:published', 'value' => ''],
            SearchScopeHelper::parse('is:published', ['', 'is', 'is:published'])
        );
    }

    public function testComposeStandardPreservesFreeText(): void
    {
        self::assertSame('tag:vip', SearchScopeHelper::compose('', 'tag:vip'));
    }

    public function testComposeScopedSearch(): void
    {
        self::assertSame('firstname:John', SearchScopeHelper::compose('firstname', 'John'));
    }

    public function testComposeCommandWithoutValue(): void
    {
        self::assertSame('is:published', SearchScopeHelper::compose('is:published', ''));
    }

    public function testRoundTrip(): void
    {
        $composed = SearchScopeHelper::compose('email', 'test@example.com');
        $parsed   = SearchScopeHelper::parse($composed, self::CONTACT_SCOPES);

        self::assertSame('email', $parsed['command']);
        self::assertSame('test@example.com', $parsed['value']);
    }
}
