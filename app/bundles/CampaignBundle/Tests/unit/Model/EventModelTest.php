<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\CampaignBundle\Model\EventModel;

class EventModelTest extends \PHPUnit_Framework_TestCase
{
    public function testThatClonedEventsDoNotAttemptNullingParentInDeleteEvents()
    {
        $mockModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockModel->expects($this->exactly(0))
            ->method('getRepository');

        $currentEvents = [
            'new1',
            'new2',
            'new3',
        ];

        $deletedEvents = [
            'new1',
        ];

        $mockModel->deleteEvents($currentEvents, $deletedEvents);
    }
}
