<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Event;

use Mautic\CoreBundle\Event\CustomTemplateEvent;

class CustomTemplateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testNullRequestDoesNotThrowException()
    {
        $event = new CustomTemplateEvent(null, 'test');
        $this->assertSame('test', $event->getTemplate());
    }

    public function testEmptyTemplateThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        new CustomTemplateEvent();
    }
}
