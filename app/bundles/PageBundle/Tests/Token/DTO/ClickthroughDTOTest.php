<?php

namespace Mautic\PageBundle\Tests\Token\DTO;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\PageBundle\Token\DTO\ClickthroughDTO;
use PHPUnit\Framework\TestCase;

class ClickthroughDTOTest extends TestCase
{
    /**
     * @dataProvider constructProvider
     */
    public function testConstruct(array $clickthrough, string $expectedChannel, $expectedStat)
    {
        $clickthrough    = ClickthroughHelper::encodeArrayForUrl($clickthrough);
        $clickthroughDTO = new ClickthroughDTO($clickthrough);

        $this->assertEquals($expectedChannel, $clickthroughDTO->getChannel());
        $this->assertEquals($expectedStat, $clickthroughDTO->getStat());
    }

    public function constructProvider()
    {
        return [
            [
                ['channel' => ['email' => 1], 'stat' => 1],
                'email',
                1,
            ],
            [
                ['channel' => ['social' => 1], 'stat' => 2],
                'social',
                2,
            ],
            [
                ['channel' => ['email' => 1]],
                'email',
                null,
            ],
        ];
    }
}
