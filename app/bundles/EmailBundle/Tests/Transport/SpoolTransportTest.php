<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\EmailBundle\Swiftmailer\Spool\DelegatingSpool;
use Mautic\EmailBundle\Swiftmailer\Transport\SpoolTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SpoolTransportTest extends TestCase
{
    /**
     * @var \Swift_Events_EventDispatcher|MockObject
     */
    private $eventDispatcher;

    /**
     * @var DelegatingSpool|MockObject
     */
    private $spool;

    /**
     * @var \Swift_Mime_SimpleMessage|MockObject
     */
    private $message;

    /**
     * @var \Swift_Events_SendEvent|MockObject
     */
    private $sendEvent;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(\Swift_Events_EventDispatcher::class);
        $this->spool           = $this->createMock(DelegatingSpool::class);
        $this->message         = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $this->sendEvent       = $this->createMock(\Swift_Events_SendEvent::class);

        $this->eventDispatcher->expects($this->once())
            ->method('createSendEvent')
            ->willReturn($this->sendEvent);
    }

    public function testSpooledEventIsDispatched()
    {
        $this->spool->expects($this->once())
            ->method('delegateMessage')
            ->willReturn(1);

        $this->spool->expects($this->once())
            ->method('wasMessageSpooled')
            ->willReturn(true);

        $this->sendEvent->expects($this->once())
            ->method('setResult')
            ->with(\Swift_Events_SendEvent::RESULT_SPOOLED);

        $transport = new SpoolTransport($this->eventDispatcher, $this->spool);

        $failed = [];
        $sent   = $transport->send($this->message, $failed);

        $this->assertEquals(1, $sent);
    }

    public function testSuccessEventIsDispatched()
    {
        $this->spool->expects($this->once())
            ->method('delegateMessage')
            ->willReturn(1);

        $this->spool->expects($this->once())
            ->method('wasMessageSpooled')
            ->willReturn(false);

        $this->sendEvent->expects($this->once())
            ->method('setResult')
            ->with(\Swift_Events_SendEvent::RESULT_SUCCESS);

        $transport = new SpoolTransport($this->eventDispatcher, $this->spool);

        $failed = [];
        $sent   = $transport->send($this->message, $failed);

        $this->assertEquals(1, $sent);
    }

    public function testFailedEventIsDispatched()
    {
        $this->spool->expects($this->once())
            ->method('delegateMessage')
            ->willReturn(0);

        $this->spool->expects($this->once())
            ->method('wasMessageSpooled')
            ->willReturn(false);

        $this->sendEvent->expects($this->once())
            ->method('setResult')
            ->with(\Swift_Events_SendEvent::RESULT_FAILED);

        $transport = new SpoolTransport($this->eventDispatcher, $this->spool);

        $failed = [];
        $sent   = $transport->send($this->message, $failed);

        $this->assertEquals(0, $sent);
    }
}
