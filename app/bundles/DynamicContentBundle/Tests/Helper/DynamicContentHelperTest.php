<?php

declare(strict_types=1);

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Tests\Helper;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DynamicContentHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetDwcBySlotNameWithPublished(): void
    {
        $mockModel = $this->getMockBuilder(DynamicContentModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntities'])
            ->getMock();

        $mockModel->expects($this->exactly(2))
            ->method('getEntities')
            ->withConsecutive(
                [
                    [
                        'filter' => [
                            'where' => [
                                [
                                    'col'  => 'e.slotName',
                                    'expr' => 'eq',
                                    'val'  => 'test',
                                ],
                                [
                                    'col'  => 'e.isPublished',
                                    'expr' => 'eq',
                                    'val'  => 1,
                                ],
                            ],
                        ],
                        'ignore_paginator' => true,
                    ],
                ],
                [
                    [
                        'filter' => [
                            'where' => [
                                [
                                    'col'  => 'e.slotName',
                                    'expr' => 'eq',
                                    'val'  => 'secondtest',
                                ],
                            ],
                        ],
                        'ignore_paginator' => true,
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $realTimeExecutioner = $this->createMock(RealTimeExecutioner::class);
        $mockDispatcher      = $this->createMock(EventDispatcher::class);

        $fixture = new DynamicContentHelper($mockModel, $realTimeExecutioner, $mockDispatcher);

        // Only get published
        $this->assertTrue($fixture->getDwcsBySlotName('test', true));

        // Get all
        $this->assertFalse($fixture->getDwcsBySlotName('secondtest'));
    }
}
