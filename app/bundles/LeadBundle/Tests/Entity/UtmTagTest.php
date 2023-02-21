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
}
