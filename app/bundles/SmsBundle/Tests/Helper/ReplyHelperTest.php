<?php

namespace Mautic\SmsBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\Helper\ReplyHelper;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class ReplyHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private NullLogger $logger;

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $contactTracker;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger          = new NullLogger();
        $this->contactTracker  = $this->createMock(ContactTracker::class);
    }

    public function testFoundContactsDispatchEvent(): void
    {
        $handler = $this->createMock(CallbackInterface::class);
        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturn(new ArrayCollection([new Lead()]));

        $handler->method('getMessage')->willReturn('some message');

        $this->contactTracker->expects($this->once())
            ->method('setSystemContact');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->getHelper()->handleRequest($handler, new Request());
    }

    public function testContactsNotFoundDoesNotDispatchEvent(): void
    {
        $handler = $this->createMock(CallbackInterface::class);
        $handler->expects($this->once())
            ->method('getContacts')
            ->willReturnCallback(
                function (): void {
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
