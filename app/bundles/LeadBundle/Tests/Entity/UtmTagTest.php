<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\UtmTag;
use PHPUnit\Framework\Attributes\DataProvider;

final class UtmTagTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('setUtmTag')]
    public function testSetUtmContent(string $utmContent, int $expected): void
    {
        $utmTag = new UtmTag();
        $utmTag->setUtmContent($utmContent);

        $this->assertSame($expected, mb_strlen($utmTag->getUtmContent()));
    }

    /**
     * @return iterable<array<int,int|string>>
     */
    public static function setUtmTag(): iterable
    {
        yield ['custom', 6];
        yield ['UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 ', 191];
    }

    #[DataProvider('utmTagsDataProvider')]
    public function testHasUtmTags(?string $utmCampaign, ?string $utmSource, ?string $utmMedium, ?string $utmContent, ?string $utmTerm, bool $expectedResult): void
    {
        $utmTag = new UtmTag();
        $utmTag->setUtmCampaign($utmCampaign);
        $utmTag->setUtmSource($utmSource);
        $utmTag->setUtmMedium($utmMedium);
        $utmTag->setUtmContent($utmContent);
        $utmTag->setUtmTerm($utmTerm);

        $this->assertSame($expectedResult, $utmTag->hasUtmTags());
    }

    /**
     * @return \Iterator<(int|string), (array<(bool|string)>|string)>
     */
    public static function utmTagsDataProvider(): \Iterator
    {
        yield 'All tags are empty' => ['', '', '', '', '', false];
        yield 'Only utmCampaign is set' => ['campaign', '', '', '', '', true];
        yield 'Only utmSource is set' => ['', 'source', '', '', '', true];
        yield 'Only utmMedium is set' => ['', '', 'medium', '', '', true];
        yield 'Only utmContent is set' => ['', '', '', 'content', '', true];
        yield 'Only utmTerm is set' => ['', '', '', '', 'term', true];
        yield 'All tags are set' => ['campaign', 'source', 'medium', 'content', 'term', true];
    }
}
