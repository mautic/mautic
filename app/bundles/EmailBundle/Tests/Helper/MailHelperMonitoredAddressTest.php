<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\SMimeHelper;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class MailHelperMonitoredAddressTest extends TestCase
{
    public function testGenerateUnsubscribeEmailUsesConfiguredMonitoringAddress(): void
    {
        $mailbox = $this->createMock(Mailbox::class);
        $mailbox->expects($this->once())
            ->method('isConfigured')
            ->with('EmailBundle', 'unsubscribes')
            ->willReturn(true);
        $mailbox->expects($this->once())
            ->method('getMailboxSettings')
            ->willReturn(['address' => 'list@example.com']);

        $helper = $this->createMailHelper($mailbox);

        $this->assertSame('list+unsubscribe_stat123@example.com', $helper->generateUnsubscribeEmail('stat123'));
    }

    private function createMailHelper(Mailbox $mailbox): MailHelper
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnMap(
            [
                ['mailer_return_path', false, null],
                ['mailer_from_email', false, 'nobody@nowhere.com'],
                ['mailer_reply_to_email', false, null],
                ['mailer_from_name', false, 'No Body'],
                ['mailer_address_length_limit', false, 320],
            ]
        );

        $sMimeHelper = $this->createMock(SMimeHelper::class);
        $sMimeHelper->method('sMimeSigningEnabled')->willReturn(false);

        return new MailHelper(
            new Mailer(new SmtpTransport()),
            $this->createMock(FromEmailHelper::class),
            $coreParametersHelper,
            $mailbox,
            $this->createMock(LoggerInterface::class),
            $this->createMock(MailHashHelper::class),
            $this->createMock(RouterInterface::class),
            $this->createMock(Environment::class),
            $this->createMock(ThemeHelper::class),
            $this->createMock(PathsHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
            $sMimeHelper,
            $this->createMock(EmailStatModel::class),
        );
    }
}
