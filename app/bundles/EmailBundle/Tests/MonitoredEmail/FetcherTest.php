<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Fetcher;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FetcherTest extends \PHPUnit_Framework_TestCase
{
    protected $mailboxes = [
        'EmailBundle_bounces' => [
            'address'           => 'bounces@test.com',
            'host'              => 'mail.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => '',
            'folder'            => 'INBOX',
            'ssl'               => '1',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
        'EmailBundle_unsubscribes' => [
            'address'           => 'unsubscribes@test.com',
            'host'              => 'mail2.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => '',
            'folder'            => 'INBOX',
            'ssl'               => '1',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
        'EmailBundle_replies' => [
            'address'           => 'replies@test.com',
            'host'              => 'mail3.test.com',
            'port'              => '993',
            'encryption'        => '/ssl',
            'user'              => 'user',
            'password'          => 'password',
            'override_settings' => '',
            'folder'            => 'INBOX',
            'ssl'               => '1',
            'imap_path'         => '{mail.test.com:993/imap/ssl}',
        ],
    ];

    /**
     * @testdox Test that the EmailEvents::EMAIL_PARSE event is dispatched from found messages
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Fetcher::fetch()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Fetcher::getMessages()
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Fetcher::getConfigs()
     */
    public function testMessagesAreFetchedAndEventDispatched()
    {
        $mailbox = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailbox->method('getMailboxSettings')
            ->willReturnCallback(
                function ($mailbox) {
                    return $this->mailboxes[$mailbox];
                }
            );
        $mailbox->method('searchMailBox')
            ->willReturn([1]);
        $mailbox->method('getMail')
            ->willReturn(new Message());

        $event      = new ParseEmailEvent();
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn($event);

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fetcher = new Fetcher($mailbox, $dispatcher, $translator, array_keys($this->mailboxes));
        $fetcher->fetch();
    }
}
