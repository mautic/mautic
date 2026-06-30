<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;

final class ProcessUnsubscribeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private ProcessUnsubscribeSubscriber $subscriber;

    protected function setup(): void
    {
        parent::setUp();

        $unsubscribe            = $this->createMock(Unsubscribe::class);
        $feedbackLoop           = $this->createMock(FeedbackLoop::class);
        $this->subscriber       = new ProcessUnsubscribeSubscriber($unsubscribe, $feedbackLoop, $this->createStub(CoreParametersHelper::class));
    }

    public function testOnEmailSend(): void
    {
        $helper = $this->createMock(MailHelper::class);
        $helper->method('generateUnsubscribeEmail')->willReturn('unsubscribe@example.com');
        $helper->method('getCustomHeaders')->willReturn([
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'List-Unsubscribe'      => '<https://example.com/email/unsubscribe/65cf64d8cb367903848157>',
        ]);

        $callCount = 0;
        $helper->expects($this->exactly(2))
            ->method('addCustomHeader')
            ->willReturnCallback(function ($headerName, $headerValue) use (&$callCount): void {
                if (0 === $callCount++) {
                    $this->assertSame('List-Unsubscribe', $headerName);
                    $this->assertSame('<https://example.com/email/unsubscribe/65cf64d8cb367903848157>, <mailto:unsubscribe@example.com>', $headerValue);
                } else {
                    $this->assertSame('List-Unsubscribe-Post', $headerName);
                    $this->assertSame('List-Unsubscribe=One-Click', $headerValue);
                }
            });

        $event = new EmailSendEvent($helper);
        $this->subscriber->onEmailSend($event);
    }
}
