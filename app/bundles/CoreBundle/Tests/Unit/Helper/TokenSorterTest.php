<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\TokenSorter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TokenSorterTest extends TestCase
{
    private TokenSorter $tokenSorter;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(fn (string $key): string => match ($key) {
                'mautic.page.token.thispage' => 'This page',
                'mautic.email.email'         => 'Email',
                default                      => $key,
            });

        $this->tokenSorter = new TokenSorter($translator);
    }

    public function testSortEmptyArray(): void
    {
        $result = $this->tokenSorter->sortTokens([]);

        $this->assertSame([], $result);
    }

    public function testSortSingleToken(): void
    {
        $tokens = ['{contactfield=email}' => 'Email'];

        $result = $this->tokenSorter->sortTokens($tokens);

        $this->assertSame($tokens, $result);
    }

    public function testContactTokensAppearBeforeOwnerTokens(): void
    {
        $tokens = [
            '{ownerfield=email}'    => 'Owner: Email',
            '{contactfield=email}'  => 'Contact: Email',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        $this->assertSame('{contactfield=email}', $keys[0]);
        $this->assertSame('{ownerfield=email}', $keys[1]);
    }

    public function testOwnerTokensAppearBeforePageLinkTokens(): void
    {
        $tokens = [
            '{pagelink=123}'      => 'a:Page: my-page (123)',
            '{ownerfield=email}'  => 'Owner: Email',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        $this->assertSame('{ownerfield=email}', $keys[0]);
        $this->assertSame('{pagelink=123}', $keys[1]);
    }

    public function testCompanyFieldsAppearAfterContactFields(): void
    {
        $tokens = [
            '{contactfield=companyname}'  => 'Company: Name',
            '{contactfield=email}'        => 'Contact: Email',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        $this->assertSame('{contactfield=email}', $keys[0]);
        $this->assertSame('{contactfield=companyname}', $keys[1]);
    }

    public function testContactPriorityFieldsAppearFirst(): void
    {
        $tokens = [
            '{contactfield=email}'     => 'Contact: Email',
            '{contactfield=lastname}'  => 'Contact: Lastname',
            '{contactfield=firstname}' => 'Contact: Firstname',
            '{contactfield=title}'     => 'Contact: Title',
            '{contactfield=address1}'  => 'Contact: Address 1',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        $this->assertSame('{contactfield=firstname}', $keys[0]);
        $this->assertSame('{contactfield=lastname}', $keys[1]);
        $this->assertSame('{contactfield=title}', $keys[2]);
        // Remaining tokens sorted alphabetically by label
        $this->assertSame('{contactfield=address1}', $keys[3]);
        $this->assertSame('{contactfield=email}', $keys[4]);
    }

    public function testTokensWithinSameCategoryAreSortedAlphabetically(): void
    {
        $tokens = [
            '{contactfield=city}'    => 'Contact: City',
            '{contactfield=address}' => 'Contact: Address',
            '{contactfield=zip}'     => 'Contact: ZIP',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        $this->assertSame('{contactfield=address}', $keys[0]);
        $this->assertSame('{contactfield=city}', $keys[1]);
        $this->assertSame('{contactfield=zip}', $keys[2]);
    }

    public function testLabelPrefixCategoryDetection(): void
    {
        $tokens = [
            '{unsubscribe_url}'     => 'Email: Unsubscribe URL',
            '{contactfield=email}'  => 'Contact: Email',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        // Contact category (10) should come before Email category (90)
        $this->assertSame('{contactfield=email}', $keys[0]);
        $this->assertSame('{unsubscribe_url}', $keys[1]);
    }

    public function testUnknownTokensFallToOtherCategory(): void
    {
        $tokens = [
            '{unknown_token}'       => 'Some Unknown Token',
            '{contactfield=email}'  => 'Contact: Email',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        // Contact category (10) should come before Other category (999)
        $this->assertSame('{contactfield=email}', $keys[0]);
        $this->assertSame('{unknown_token}', $keys[1]);
    }

    public function testFullCategorySortOrder(): void
    {
        $tokens = [
            '{unknown}'                      => 'Unknown Token',
            '{unsubscribe_url}'              => 'Email: Unsubscribe',
            '{page_title}'                   => 'This page: Title',
            '{assetlink=1}'                  => 'a:Asset: File (1)',
            '{focus=1}'                      => 'Focus: Item 1',
            '{dwc=slot}'                     => 'DWC: Slot',
            '{pagelink=1}'                   => 'a:Page: Home (1)',
            '{ownerfield=email}'             => 'Owner: Email',
            '{contactfield=companyname}'     => 'Company: Name',
            '{contactfield=email}'           => 'Contact: Email',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        // Expected order based on category priorities:
        // CONTACT=10, COMPANY=20, OWNER=30, PAGE_LINK=40, DWC=50, FOCUS=60, ASSET=70, THIS_PAGE=80, EMAIL=90, OTHER=999
        $this->assertSame('{contactfield=email}', $keys[0]);          // Contact (10)
        $this->assertSame('{contactfield=companyname}', $keys[1]);    // Company (20)
        $this->assertSame('{ownerfield=email}', $keys[2]);            // Owner (30)
        $this->assertSame('{pagelink=1}', $keys[3]);                  // Page Link (40)
        $this->assertSame('{dwc=slot}', $keys[4]);                    // DWC (50)
        $this->assertSame('{focus=1}', $keys[5]);                     // Focus (60)
        $this->assertSame('{assetlink=1}', $keys[6]);                 // Asset (70)
        $this->assertSame('{page_title}', $keys[7]);                  // This Page (80)
        $this->assertSame('{unsubscribe_url}', $keys[8]);             // Email (90)
        $this->assertSame('{unknown}', $keys[9]);                     // Other (999)
    }

    public function testCaseInsensitiveAlphabeticalSort(): void
    {
        $tokens = [
            '{contactfield=ZIP}'     => 'Contact: ZIP Code',
            '{contactfield=address}' => 'Contact: address',
        ];

        $result = $this->tokenSorter->sortTokens($tokens);
        $keys   = array_keys($result);

        // 'address' should come before 'ZIP Code' (case-insensitive)
        $this->assertSame('{contactfield=address}', $keys[0]);
        $this->assertSame('{contactfield=ZIP}', $keys[1]);
    }
}
