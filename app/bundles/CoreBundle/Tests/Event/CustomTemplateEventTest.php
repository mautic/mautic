<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Event;


use Mautic\CoreBundle\Event\CustomTemplateEvent;

class CustomTemplateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testNullRequestDoesNotThrowException()
    {
        new CustomTemplateEvent(null, 'test');
    }

    public function testEmptyTemplateThrowsException()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        new CustomTemplateEvent();
    }
}