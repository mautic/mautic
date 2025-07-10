<?php

namespace Mautic\MessengerBundle\Tests\MessageHandler;

use Doctrine\DBAL\Exception\RetryableException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

class EmailHitNotificationHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $hitId   = sha1((string) random_int(0, 1_000_000));
        $request = new Request();
        $request->query->set('testMe', 'I am here');

        /** @var MockObject|EmailModel $emailModelMock */
        $emailModelMock = $this->createMock(EmailModel::class);
        $emailModelMock
            ->expects($this->exactly(1))
            ->method('hitEmail')
            ->with($hitId, $request);

        /** @var MockObject&CoreParametersHelper $parametersHelper */
        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->method('get')
            ->willReturn('sync://');

        $message = new EmailHitNotification($hitId, $request);

        $handler  = new EmailHitNotificationHandler($emailModelMock, $parametersHelper);
        $handler->__invoke($message);
    }

    public function testInvokeThrowsRecoverableExceptionOnDBLock(): void
    {
        $hitId   = sha1((string) random_int(0, 1_000_000));
        $request = new Request();
        $request->query->set('testMe', 'I am here');

        /** @var MockObject|EmailModel $emailModelMock */
        $emailModelMock = $this->createMock(EmailModel::class);
        $emailModelMock
            ->expects($this->exactly(1))
            ->method('hitEmail')
            ->willThrowException($this->createMock(RetryableException::class));

        /** @var MockObject&CoreParametersHelper $parametersHelper */
        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->method('get')
            ->willReturn('sync://');

        $message = new EmailHitNotification($hitId, $request);

        $handler  = new EmailHitNotificationHandler($emailModelMock, $parametersHelper);
        $this->expectException(RecoverableMessageHandlingException::class);
        $handler->__invoke($message);
    }

    public function testInvokeLogsUnrecoverableException(): void
    {
        $hitId   = sha1((string) random_int(0, 1_000_000));
        $request = new Request();
        $request->query->set('testMe', 'I am here');

        /** @var MockObject|EmailModel $emailModelMock */
        $emailModelMock = $this->createMock(EmailModel::class);
        $emailModelMock
            ->expects($this->exactly(1))
            ->method('hitEmail')
            ->willThrowException(new \InvalidArgumentException('got my argument?'));

        /** @var MockObject&CoreParametersHelper $parametersHelper */
        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->method('get')
            ->willReturn('sync://');

        $message  = new EmailHitNotification($hitId, $request);
        $handler  = new EmailHitNotificationHandler($emailModelMock, $parametersHelper);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('got my argument?');
        $handler->__invoke($message);
    }
}
