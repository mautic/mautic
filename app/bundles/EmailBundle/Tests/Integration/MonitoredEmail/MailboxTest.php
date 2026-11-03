<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Integration\MonitoredEmail;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

/**
 * Exercises the real mailbox protocol without requiring a Mautic database.
 */
#[Group('imap')]
final class MailboxTest extends TestCase
{
    private ?Mailbox $mailbox = null;
    private ?string $temporaryDirectory = null;
    private string $host;
    private string $address;

    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('MAUTIC_TEST_IMAP_HOST');
        if (false === $host || '' === $host) {
            self::markTestSkipped('Set MAUTIC_TEST_IMAP_HOST to a disposable GreenMail server; see the adjacent README.md.');
        }

        // An explicitly configured test environment must fail rather than silently skip.
        $this->assertTrue(extension_loaded('imap'), 'The native mailbox baseline requires ext-imap.');
        $this->host               = $host;
        $this->address            = 'mautic-'.bin2hex(random_bytes(12)).'@example.test';
        $this->temporaryDirectory = sys_get_temp_dir().'/'.$this->address;

        $parameters = $this->createStub(CoreParametersHelper::class);
        $parameters->method('get')->willReturn([
            'general' => [
                'host'            => $this->host,
                'port'            => (int) (getenv('MAUTIC_TEST_IMAP_PORT') ?: 3143),
                'encryption'      => '/notls',
                'user'            => $this->address,
                'password'        => 'test-only',
                'use_attachments' => true,
            ],
            'EmailBundle_bounces' => ['folder' => 'INBOX', 'override_settings' => false],
        ]);
        $paths = $this->createStub(PathsHelper::class);
        $paths->method('getSystemPath')->willReturn($this->temporaryDirectory);

        $this->mailbox = new Mailbox($parameters, $paths);
        $this->mailbox->switchMailbox('EmailBundle', 'bounces');
    }

    protected function tearDown(): void
    {
        // Every test gets its own account; never purge a shared inbox.
        $this->mailbox = null;
        if (null !== $this->temporaryDirectory) {
            (new Filesystem())->remove($this->temporaryDirectory);
        }

        parent::tearDown();
    }

    public function testDecodesMimeHeadersBodiesAndAttachmentsWithoutMarkingRead(): void
    {
        $this->deliverFixture();
        $ids = $this->mailbox->fetchUnread();
        $this->assertCount(1, $ids);

        $message = $this->mailbox->getMail($ids[0], false);
        $this->assertSame($ids[0], $message->id);
        $this->assertSame('Příliš žluťoučký', $message->subject);
        $this->assertSame('René', $message->fromName);
        $this->assertSame('sender@example.test', $message->fromAddress);
        $this->assertSame(['recipient@example.test' => 'Test Inbox'], $message->to);
        $this->assertSame(['reply@example.test' => 'Replies'], $message->replyTo);
        $this->assertSame('<original@example.test>', $message->inReplyTo);
        $this->assertSame('mailbox-contract', $message->xHeaders['x-mautic-test']);
        $this->assertSame('Hello, Příliš!', trim($message->textPlain));
        $this->assertSame('<p>Hello</p>', trim($message->textHtml));
        $this->assertSame($ids, $this->mailbox->fetchUnread());

        $attachments = array_values($message->getAttachments());
        $this->assertCount(1, $attachments);
        $this->assertSame('report.txt', $attachments[0]->name);
        $this->assertSame("attachment content\n", file_get_contents($attachments[0]->filePath));
    }

    public function testReadUnreadAndImportantFlags(): void
    {
        $this->deliverFixture();
        $ids = $this->mailbox->fetchUnread();
        $this->assertCount(1, $ids);
        $id = $ids[0];

        $this->mailbox->getMail($id);
        $this->assertSame([], $this->mailbox->fetchUnread());
        $this->assertTrue($this->mailbox->markMailAsUnread($id));
        $this->assertSame([$id], $this->mailbox->fetchUnread());
        $this->assertTrue($this->mailbox->markMailAsImportant($id));
        $this->assertSame([$id], $this->mailbox->searchMailbox('FLAGGED'));
        $this->assertTrue($this->mailbox->clearFlag([$id], '\\Flagged'));
        $this->assertSame([], $this->mailbox->searchMailbox('FLAGGED'));
        $this->assertTrue($this->mailbox->markMailAsRead($id));
        $this->assertSame([], $this->mailbox->fetchUnread());
    }

    public function testSearchReturnsStableUidsAfterExpunge(): void
    {
        $this->deliverFixture();
        $this->deliverFixture();
        $ids = $this->mailbox->searchMailbox();
        $this->assertCount(2, $ids);

        $this->assertTrue($this->mailbox->deleteMail($ids[0]));
        $this->assertTrue($this->mailbox->expungeDeletedMails());
        $this->assertSame([$ids[1]], $this->mailbox->searchMailbox());
        $this->assertSame([$ids[1]], $this->mailbox->searchMailbox('UID '.$ids[1].':*'));
        $this->assertSame([$ids[1]], $this->mailbox->searchMailbox('FROM "sender@example.test"'));
        $this->assertSame([], $this->mailbox->searchMailbox('FROM "missing@example.test"'));
        $this->assertSame($ids[1], $this->mailbox->getMail($ids[1], false)->id);
        $this->assertSame(1, $this->mailbox->countMails());
    }

    public function testMoveToAnotherFolderAndSaveRawMessage(): void
    {
        $this->deliverFixture();
        $ids = $this->mailbox->searchMailbox();
        $this->assertCount(1, $ids);

        // Provision a destination through the native API, as an external client would.
        $settings           = $this->mailbox->getMailboxSettings();
        $settings['folder'] = 'Processed';
        $path               = $this->mailbox->getImapPath($settings);
        $this->assertTrue(imap_createmailbox($this->mailbox->getImapStream(), $path['full']));
        $this->assertContains('Processed', $this->mailbox->getListingFolders());
        $this->assertTrue($this->mailbox->moveMail($ids[0], 'Processed'));
        $this->assertSame([], $this->mailbox->searchMailbox());

        $this->mailbox->switchFolder('Processed');
        $movedIds = $this->mailbox->searchMailbox();
        $this->assertCount(1, $movedIds);
        $this->assertSame('Příliš žluťoučký', $this->mailbox->getMail($movedIds[0], false)->subject);
        $filename = $this->temporaryDirectory.'/saved.eml';
        $this->assertTrue($this->mailbox->saveMail($movedIds[0], $filename));
        $this->assertStringContainsString('Message-ID: <fixture@example.test>', (string) file_get_contents($filename));
        $this->assertTrue($this->mailbox->deleteMail($movedIds[0]));
        $this->assertTrue($this->mailbox->expungeDeletedMails());
        $this->assertSame([], $this->mailbox->searchMailbox());
    }

    public function testLegacyConnectionRemainsUsableByNativeImapFunctions(): void
    {
        $this->deliverFixture();
        $this->mailbox->setConnectionArgs(0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        $connection = $this->mailbox->getImapStream();

        $this->assertInstanceOf(\IMAP\Connection::class, $connection);
        $this->assertTrue(imap_ping($connection));
        $this->assertSame($connection, $this->mailbox->getImapStream());
        $this->assertSame(1, imap_num_msg($connection));
    }

    private function deliverFixture(): void
    {
        $fixture = file_get_contents(__DIR__.'/Fixtures/multipart.eml');
        $this->assertNotFalse($fixture);
        $fixture = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $fixture));

        $transport = new EsmtpTransport($this->host, (int) (getenv('MAUTIC_TEST_SMTP_PORT') ?: 3025), false);
        $transport->setAutoTls(false);
        try {
            $transport->send(new RawMessage($fixture), new Envelope(new Address('sender@example.test'), [new Address($this->address)]));
        } finally {
            $transport->stop();
        }
    }
}
