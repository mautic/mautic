<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Fetcher;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[\PHPUnit\Framework\Attributes\CoversClass(Fetcher::class)]
class FetcherTest extends \PHPUnit\Framework\TestCase
{
    protected $mailboxes = [
        'EmailBundle_bounces' => [
            'address'           => 'bounces@test.com',
            'host'              => 'mail.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => 0,
            'folder'            => 'INBOX',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
        'EmailBundle_unsubscribes' => [
            'address'           => 'unsubscribes@test.com',
            'host'              => 'mail2.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => 0,
            'folder'            => 'INBOX',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
        'EmailBundle_replies' => [
            'address'           => 'replies@test.com',
            'host'              => 'mail3.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => 0,
            'folder'            => 'INBOX',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
    ];

    #[\PHPUnit\Framework\Attributes\TestDox('Test that the EmailEvents::EMAIL_PARSE event is dispatched from found messages')]
    public function testMessagesAreFetchedAndEventDispatched(): void
    {
        $mailbox = $this->createMock(Mailbox::class);
        $mailbox->method('getMailboxSettings')
            ->willReturnCallback(
                fn ($mailbox) => $this->mailboxes[$mailbox]
            );
        $mailbox->method('searchMailBox')
            ->willReturn([1]);
        $mailbox->method('getMail')
            ->willReturn(new Message());

        $event      = new ParseEmailEvent();
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn($event);

        $translator = $this->createMock(Translator::class);

        $fetcher = new Fetcher($mailbox, $dispatcher, $translator);
        $fetcher->setMailboxes(array_keys($this->mailboxes))
            ->fetch();
    }
}
