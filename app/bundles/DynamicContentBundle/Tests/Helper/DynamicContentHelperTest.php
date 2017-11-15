<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Tests\Helper;

use Mautic\CampaignBundle\Model\EventModel;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DynamicContentHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDwcBySlotNameWithPublished()
    {
        $mockModel = $this->getMockBuilder(DynamicContentModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntities'])
            ->getMock();

        $mockModel->expects($this->at(0))
            ->method('getEntities')
            ->with([
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
            ])
            ->willReturn(true);

        $mockModel->expects($this->at(1))
            ->method('getEntities')
            ->with([
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
            ])
            ->willReturn(false);

        $mockEventModel = $this->createMock(EventModel::class);
        $mockDispatcher = $this->createMock(EventDispatcher::class);

        $fixture = new DynamicContentHelper($mockModel, $mockEventModel, $mockDispatcher);

        // Only get published
        $this->assertTrue($fixture->getDwcsBySlotName('test', true));

        // Get all
        $this->assertFalse($fixture->getDwcsBySlotName('secondtest'));
    }
}
