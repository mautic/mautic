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
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\Helper\CallbackHelper;
use Mautic\SmsBundle\Model\SmsModel;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CallbackHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var NullLogger
     */
    private $logger;

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactTracker;

    /**
     * @var SmsModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $smsModel;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger          = new NullLogger();
        $this->contactTracker  = $this->createMock(ContactTracker::class);
        $this->smsModel        = $this->createMock(SmsModel::class);
    }

    public function testFoundContactsDispatchEvent()
    {
        $handler = $this->createMock(CallbackInterface::class);

        $replyEvent = new ReplyEvent();
        $replyEvent->setMessage('test');

        $handler->expects($this->once())
            ->method('getEvent')
            ->willReturn($replyEvent);

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

        $replyEvent = new ReplyEvent();
        $replyEvent->setMessage('test');

        $handler->expects($this->once())
            ->method('getEvent')
            ->willReturn($replyEvent);

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

        $replyEvent = new ReplyEvent();
        $replyEvent->setMessage('test');

        $handler->expects($this->once())
            ->method('getEvent')
            ->willReturn([$replyEvent]);

        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->getHelper()->handleRequest($handler, new Request());
    }

    /**
     * @return CallbackHelper
     */
    private function getHelper()
    {
        return new CallbackHelper($this->eventDispatcher, $this->logger, $this->contactTracker, $this->smsModel);
    }
}
