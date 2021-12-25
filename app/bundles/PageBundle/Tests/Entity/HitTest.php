<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\PageBundle\Entity\Hit;
use PHPUnit\Framework\Assert;

class HitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider setIsPreferenceCenterDataProvider
     */
    public function testSetUrlTitle($urlTitle, $expected): void
    {
        $hit = new Hit();
        $hit->setUrlTitle($urlTitle);

        Assert::assertEquals($expected, strlen($hit->getUrlTitle()));
    }

    public function setIsPreferenceCenterDataProvider(): iterable
    {
        yield ['custom', 6];
        yield ['Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars Title longer than 191 chars ', 191];
    }
}
