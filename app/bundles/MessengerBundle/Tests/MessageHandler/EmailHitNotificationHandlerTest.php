<?php

namespace Mautic\MessengerBundle\Tests\MessageHandler;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\MessengerBundle\MauticMessengerBundle;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class EmailHitNotificationHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $hitId   = sha1(rand());
        $request = new Request();
        $request->query->set('testMe', 'I am here');

        /** @var MockObject|EmailModel $emailModelMock */
        $emailModelMock = $this->createMock(EmailModel::class);
        $emailModelMock
            ->expects($this->exactly(1))
            ->method('hitEmail')
            ->with($hitId, $request)
        ;

        /** @var MockObject|LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $message = new EmailHitNotification($hitId, $request);

        $handler  = new EmailHitNotificationHandler($emailModelMock, $loggerMock);
        $response = $handler->__invoke($message);
        $this->assertEquals(
            MauticMessengerBundle::LOG_PREFIX.'processed email hit #'.$message->getStatId(),
            $response
        );
    }
}
