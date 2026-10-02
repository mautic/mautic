<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\PageBundle\Entity\Hit;
use PHPUnit\Framework\Attributes\DataProvider;

final class HitTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('setUrlTitle')]
    public function testSetUrlTitle(string $urlTitle, int $expected): void
    {
        $hit = new Hit();
        $hit->setUrlTitle($urlTitle);

        $this->assertSame($expected, mb_strlen($hit->getUrlTitle()));
    }

    /**
     * @return iterable<array<int,int|string>>
     */
    public static function setUrlTitle(): iterable
    {
        yield ['custom', 6];
        yield ['Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars ', 191];
    }
}
