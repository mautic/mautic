<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\EventCollector\Accessor\Event;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;

class ActionAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testBatchEventNameIsNotExtra()
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test']);

        $this->assertEmpty($actionAccessor->getExtraProperties());
    }

    public function testBatchNameIsReturned()
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test']);

        $this->assertEquals('test', $actionAccessor->getBatchEventName());
    }

    public function testExtraParamIsReturned()
    {
        $actionAccessor = new ActionAccessor(['batchEventName' => 'test', 'foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $actionAccessor->getExtraProperties());
    }
}
