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
        $this->assertSame(
            ['command' => '', 'value' => 'john@example.com'],
            SearchScopeHelper::parse('john@example.com', self::CONTACT_SCOPES)
        );
    }

    public function testParseSplitsScopedSearch(): void
    {
        $this->assertSame(
            ['command' => 'firstname', 'value' => 'John'],
            SearchScopeHelper::parse('firstname:John', self::CONTACT_SCOPES)
        );
    }

    public function testParseMatchesLongestCommandFirst(): void
    {
        $this->assertSame(
            ['command' => 'is:published', 'value' => ''],
            SearchScopeHelper::parse('is:published', ['', 'is', 'is:published'])
        );
    }

    public function testComposeStandardPreservesFreeText(): void
    {
        $this->assertSame('tag:vip', SearchScopeHelper::compose('', 'tag:vip'));
    }

    public function testComposeScopedSearch(): void
    {
        $this->assertSame('firstname:John', SearchScopeHelper::compose('firstname', 'John'));
    }

    public function testComposeCommandWithoutValue(): void
    {
        $this->assertSame('is:published', SearchScopeHelper::compose('is:published', ''));
    }

    public function testRoundTrip(): void
    {
        $composed = SearchScopeHelper::compose('email', 'test@example.com');
        $parsed   = SearchScopeHelper::parse($composed, self::CONTACT_SCOPES);

        $this->assertSame('email', $parsed['command']);
        $this->assertSame('test@example.com', $parsed['value']);
    }

    public function testFormatLabelCapitalizesSimpleCommand(): void
    {
        $this->assertSame('Name', SearchScopeHelper::formatLabel('name'));
    }

    public function testFormatLabelCapitalizesColonSeparatedCommand(): void
    {
        $this->assertSame('Is:Published', SearchScopeHelper::formatLabel('is:published'));
        $this->assertSame('Is:Mine', SearchScopeHelper::formatLabel('is:mine'));
    }

    public function testFormatLabelPreservesAlreadyCapitalizedLabel(): void
    {
        $this->assertSame('Standard', SearchScopeHelper::formatLabel('Standard'));
    }

    public function testFormatLabelIndentsCustomFieldLabel(): void
    {
        $this->assertSame("\u{00A0}\u{00A0}\u{00A0}\u{00A0}My Custom Field", SearchScopeHelper::formatLabel('My Custom Field', true));
    }

    public function testFormatLabelDoesNotIndentByDefault(): void
    {
        $this->assertStringStartsNotWith("\u{00A0}", SearchScopeHelper::formatLabel('My Custom Field'));
    }
}
