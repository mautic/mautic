<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\UtmTag;
use PHPUnit\Framework\Assert;

class UtmTagTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider setUtmTag
     */
    public function testSetUtmContent(string $utmContent, int $expected): void
    {
        $utmTag = new UtmTag();
        $utmTag->setUtmContent($utmContent);

        Assert::assertEquals($expected, mb_strlen($utmTag->getUtmContent()));
    }

    /**
     * @return iterable<array<int,int|string>>
     */
    public function setUtmTag(): iterable
    {
        yield ['custom', 6];
        yield ['UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 UTM content longer like 191 ', 191];
    }

    /**
     * @dataProvider utmTagsDataProvider
     */
    public function testHasUtmTags(?string $utmCampaign, ?string $utmSource, ?string $utmMedium, ?string $utmContent, ?string $utmTerm, bool $expectedResult): void
    {
        $utmTag = new UtmTag();
        $utmTag->setUtmCampaign($utmCampaign);
        $utmTag->setUtmSource($utmSource);
        $utmTag->setUtmMedium($utmMedium);
        $utmTag->setUtmContent($utmContent);
        $utmTag->setUtmTerm($utmTerm);

        $this->assertEquals($expectedResult, $utmTag->hasUtmTags());
    }

    /**
     * @return array<string|array<bool|string|null>>
     */
    public function utmTagsDataProvider(): array
    {
        return [
            'All tags are null'       => [null, null, null, null, null, false],
            'Only utmCampaign is set' => ['campaign', null, null, null, null, true],
            'Only utmSource is set'   => [null, 'source', null, null, null, true],
            'Only utmMedium is set'   => [null, null, 'medium', null, null, true],
            'Only utmContent is set'  => [null, null, null, 'content', null, true],
            'Only utmTerm is set'     => [null, null, null, null, 'term', true],
            'All tags are set'        => ['campaign', 'source', 'medium', 'content', 'term', true],
        ];
    }
}
