<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use PHPUnit\Framework\MockObject\MockObject;

final class ProcessUnsubscribeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Unsubscribe
     */
    private MockObject $unsubscribe;

    /**
     * @var MockObject|FeedbackLoop
     */
    private MockObject $feedbackLoop;

    private ProcessUnsubscribeSubscriber $subscriber;

    protected function setup(): void
    {
        parent::setUp();

        $this->unsubscribe      = $this->createMock(Unsubscribe::class);
        $this->feedbackLoop     = $this->createMock(FeedbackLoop::class);
        $this->subscriber       = new ProcessUnsubscribeSubscriber($this->unsubscribe, $this->feedbackLoop);
    }

    public function testOnEmailSend(): void
    {
        $helper = $this->createMock(MailHelper::class);
        $helper->method('generateUnsubscribeEmail')->willReturn('unsubscribe@example.com');
        $helper->method('getCustomHeaders')->willReturn([
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'List-Unsubscribe'      => '<https://example.com/email/unsubscribe/65cf64d8cb367903848157>',
        ]);

        $helper->expects($this->once())
            ->method('addCustomHeader')
            ->with('List-Unsubscribe', '<https://example.com/email/unsubscribe/65cf64d8cb367903848157>, <mailto:unsubscribe@example.com>');

        $event = new EmailSendEvent($helper);
        $this->subscriber->onEmailSend($event);
    }
}
