<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Mautic\EmailBundle\EventListener\EmailSubscriber;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Model\EmailDraftModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\BatchTransport;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class EmailSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&IpLookupHelper
     */
    private MockObject $ipLookupHelper;

    /**
     * @var MockObject&AuditLogModel
     */
    private MockObject $auditLogModel;

    /**
     * @var MockObject&EmailModel
     */
    private MockObject $emailModel;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&MauticMessage
     */
    private MockObject $mockMessage;

    private EmailSubscriber $subscriber;

    protected function setup(): void
    {
        parent::setUp();

        $this->ipLookupHelper   = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel    = $this->createMock(AuditLogModel::class);
        $this->emailModel       = $this->createMock(EmailModel::class);
        $this->translator       = $this->createMock(TranslatorInterface::class);
        $this->mockMessage      = $this->createMock(MauticMessage::class);
        $this->subscriber       = new EmailSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->emailModel, $this->translator, $this->createMock(EntityManager::class), $this->createMock(EmailDraftModel::class));
    }

    public function testOnEmailResendWithNoLeadIdHash(): void
    {
        $event = new QueueEmailEvent($this->mockMessage);

        $this->emailModel->expects($this->never())
            ->method('getEmailStatus');

        $this->subscriber->onEmailResend($event);

        Assert::assertFalse($event->shouldTryAgain());
    }

    public function testOnEmailResendWithNoStat(): void
    {
        $message = new class extends MauticMessage {
            public ?string $leadIdHash = 'some-hash';
        };

        $event = new QueueEmailEvent($message);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus');

        $this->emailModel->expects($this->never())
            ->method('saveEmailStat');

        $this->emailModel->expects($this->never())
            ->method('setDoNotContact');

        $this->subscriber->onEmailResend($event);

        Assert::assertFalse($event->shouldTryAgain());
    }

    public function testOnEmailResendWithNoRetry(): void
    {
        $message = new class extends MauticMessage {
            public ?string $leadIdHash = 'some-hash';
        };

        $event = new QueueEmailEvent($message);
        $stat  = new Stat();

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->emailModel->expects($this->once())
            ->method('saveEmailStat')
            ->with($stat);

        $this->emailModel->expects($this->never())
            ->method('setDoNotContact');

        $this->subscriber->onEmailResend($event);

        Assert::assertSame(1, $stat->getRetryCount());
        Assert::assertTrue($event->shouldTryAgain());
    }

    public function testOnEmailResendWhenShouldTryAgain(): void
    {
        $this->mockMessage->method('getLeadIdHash')
            ->willReturn('idhash');

        $queueEmailEvent = new QueueEmailEvent($this->mockMessage);

        $stat = new Stat();
        $stat->setRetryCount(2);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->subscriber->onEmailResend($queueEmailEvent);
        $this->assertTrue($queueEmailEvent->shouldTryAgain());
    }

    public function testOnEmailResendWhenShouldNotTryAgain(): void
    {
        $this->mockMessage
            ->method('getLeadIdHash')
            ->willReturn('idhash');

        $this->mockMessage->expects($this->once())
            ->method('getSubject')
            ->willReturn('Subject');

        $queueEmailEvent = new QueueEmailEvent($this->mockMessage);

        $stat = new Stat();
        $stat->setRetryCount(3);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->subscriber->onEmailResend($queueEmailEvent);
        $this->assertFalse($queueEmailEvent->shouldTryAgain());
    }

    public function testOnEmailResendWith4Retry(): void
    {
        $message = new class extends MauticMessage {
            public ?string $leadIdHash = 'some-hash';
        };

        $message->subject('Subject');

        $event = new QueueEmailEvent($message);
        $stat  = new Stat();

        $stat->setRetryCount(4);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->willReturn($stat);

        $this->emailModel->expects($this->once())
            ->method('saveEmailStat')
            ->with($stat);

        $this->emailModel->expects($this->once())
            ->method('setDoNotContact')
            ->with($stat);

        $this->subscriber->onEmailResend($event);

        Assert::assertSame(5, $stat->getRetryCount());
        Assert::assertFalse($event->shouldTryAgain());
    }

    public function testOnEmailSendAddPreheaderText(): void
    {
        $this->runPreheaderEvent(
            <<<'CONTENT'
<html xmlns="http://www.w3.org/1999/xhtml">
    <body style="margin: 0px; cursor: auto;" class="ui-sortable">
        <div data-section-wrapper="1">
            <center>
                <table data-section="1" style="width: 600;" width="600" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td>
                                <div data-slot-container="1" style="min-height: 30px">
                                    <div data-slot="text"><br /><h2>Hello there!</h2><br />{test} test We haven't heard from you for a while...<a href="https://google.com">check this link</a><br /><br />{unsubscribe_text} | {webview_text}</div>{dynamiccontent="Dynamic Content 2"}<div data-slot="codemode">
                                    <div id="codemodeHtmlContainer">
    <p>Place your content here {test}</p></div>

                                </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </center>
        </div>
</body></html>
CONTENT,
            function (string $content): void {
                $preheaderTextHtml = EmailSubscriber::PREHEADER_HTML_ELEMENT_BEFORE.'this is a nice preheader text'.EmailSubscriber::PREHEADER_HTML_ELEMENT_AFTER;
                $this->assertStringContainsString($preheaderTextHtml, $content);
                $this->assertMatchesRegularExpression(EmailSubscriber::PREHEADER_HTML_SEARCH_PATTERN, $content);
            }
        );
    }

    public function testOnEmailSendAddPreheaderTextWithPreheaderPresent(): void
    {
        $this->runPreheaderEvent(
            <<<'CONTENT'
<html xmlns="http://www.w3.org/1999/xhtml">
    <body style="margin: 0px; cursor: auto;" class="ui-sortable">
        <div class="preheader" style="font-size:1px;line-height:1px;display:none;color:#fff;max-height:0;max-width:0;opacity:0;overflow:hidden">Original Preheader here</div>
        <div data-section-wrapper="1">
            <center>
                <table data-section="1" style="width: 600;" width="600" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td>
                                <div data-slot-container="1" style="min-height: 30px">
                                    <div data-slot="text"><br /><h2>Hello there!</h2><br />{test} test We haven't heard from you for a while...<a href="https://google.com">check this link</a><br /><br />{unsubscribe_text} | {webview_text}</div>{dynamiccontent="Dynamic Content 2"}<div data-slot="codemode">
                                    <div id="codemodeHtmlContainer"><p>Place your content here {test}</p></div>
                                </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </center>
        </div>
</body></html>
CONTENT,

            function (string $content): void {
                $preheaderTextHtml = EmailSubscriber::PREHEADER_HTML_ELEMENT_BEFORE.'this is a nice preheader text'.EmailSubscriber::PREHEADER_HTML_ELEMENT_AFTER;
                $this->assertStringContainsString($preheaderTextHtml, $content);
                $this->assertStringNotContainsString('Original Preheader here', $content);
                $this->assertMatchesRegularExpression(EmailSubscriber::PREHEADER_HTML_SEARCH_PATTERN, $content);
            }
        );
    }

    private function runPreheaderEvent(string $html, callable $assert): void
    {
        /** @var MockObject&FromEmailHelper $fromEmailHelper */
        $fromEmailHelper = $this->createMock(FromEmailHelper::class);

        /** @var MockObject&CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        /** @var MockObject&Mailbox $mailbox */
        $mailbox = $this->createMock(Mailbox::class);

        /** @var MockObject&RouterInterface $router */
        $router = $this->createMock(RouterInterface::class);

        /** @var MockObject&Environment $twig */
        $twig = $this->createMock(Environment::class);

        $themeHelper = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_from_name', null, 'No Body'],
                ]
            );
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');

        $mailer       = new Mailer(new BatchTransport());
        $requestStack = new RequestStack();
        $mailHelper   = new MailHelper(
            $mailer,
            $fromEmailHelper,
            $coreParametersHelper,
            $mailbox,
            new NullLogger(),
            new MailHashHelper($coreParametersHelper),
            $router,
            $twig,
            $themeHelper,
            $this->createMock(PathsHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $requestStack,
            $entityManager,
            $this->createMock(ModelFactory::class),
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
        );

        $email = new Email();
        $email->setCustomHtml($html);
        $email->setPreheaderText('this is a nice preheader text');
        $mailHelper->setEmail($email);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);

        $event = new EmailSendEvent($mailHelper);

        $this->subscriber->onEmailSendAddPreheaderText($event);

        $assert($event->getContent());
    }
}
