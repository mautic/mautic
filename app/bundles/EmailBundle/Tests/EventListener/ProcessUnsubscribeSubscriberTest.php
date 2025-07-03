<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;

final class ProcessUnsubscribeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&Unsubscribe
     */
    private MockObject $unsubscribe;

    /**
     * @var MockObject&FeedbackLoop
     */
    private MockObject $feedbackLoop;

    private ProcessUnsubscribeSubscriber $subscriber;

    protected function setup(): void
    {
        parent::setUp();

        $this->unsubscribe      = $this->createMock(Unsubscribe::class);
        $this->feedbackLoop     = $this->createMock(FeedbackLoop::class);
        $this->subscriber       = new ProcessUnsubscribeSubscriber($this->unsubscribe, $this->feedbackLoop, $this->createMock(CoreParametersHelper::class));
    }

    public function testOnEmailSend(): void
    {
        $helper = $this->createMock(MailHelper::class);
        $helper->method('generateUnsubscribeEmail')->willReturn('unsubscribe@example.com');
        $helper->method('getCustomHeaders')->willReturn([
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'List-Unsubscribe'      => '<https://example.com/email/unsubscribe/65cf64d8cb367903848157>',
        ]);

        $helper->expects($this->exactly(2))
            ->method('addCustomHeader')
            ->willReturnCallback(function (string $name, string $value) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    Assert::assertSame('List-Unsubscribe', $name);
                    Assert::assertSame(
                        '<https://example.com/email/unsubscribe/65cf64d8cb367903848157>, <mailto:unsubscribe@example.com>',
                        $value
                    );
                } elseif (2 === $callCount) {
                    Assert::assertSame('List-Unsubscribe-Post', $name);
                    Assert::assertSame('List-Unsubscribe=One-Click', $value);
                }
            });

        $event = new EmailSendEvent($helper);
        $this->subscriber->onEmailSend($event);
    }
}
