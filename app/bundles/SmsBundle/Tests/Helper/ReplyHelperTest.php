<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Callback\ResponseInterface;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\Helper\ReplyHelper;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplyHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var NullLogger
     */
    private $logger;

    /**
     * @var ContactTracker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactTracker;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger          = new NullLogger();
        $this->contactTracker  = $this->createMock(ContactTracker::class);
    }

    public function testFoundContactsDispatchEvent()
    {
        $handler = $this->createMock(CallbackInterface::class);
        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->contactTracker->expects($this->once())
            ->method('setSystemContact');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->getHelper()->handleRequest($handler, new Request());
    }

    public function testHandlerResponseIsReturnedIfResponseInterface()
    {
        $handler = $this->createMock([CallbackInterface::class, ResponseInterface::class]);

        $handlerResponse = new Response('hi');
        $handler->expects($this->once())
            ->method('getResponse')
            ->willReturn($handlerResponse);

        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->contactTracker->expects($this->once())
            ->method('setSystemContact');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $response = $this->getHelper()->handleRequest($handler, new Request());

        $this->assertEquals($handlerResponse, $response);
    }

    public function testContactsNotFoundDoesNotDispatchEvent()
    {
        $handler = $this->createMock(CallbackInterface::class);
        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturnCallback(
                function () {
                    throw new NumberNotFoundException('');
                }
            );

        $this->contactTracker->expects($this->never())
            ->method('setSystemContact');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->getHelper()->handleRequest($handler, new Request());
    }

    /**
     * @return ReplyHelper
     */
    private function getHelper()
    {
        return new ReplyHelper($this->eventDispatcher, $this->logger, $this->contactTracker);
    }
}
