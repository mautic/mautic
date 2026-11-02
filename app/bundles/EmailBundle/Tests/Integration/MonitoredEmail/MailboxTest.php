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
        self::assertTrue(extension_loaded('imap'), 'The native mailbox baseline requires ext-imap.');
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
        self::assertCount(1, $ids);

        $message = $this->mailbox->getMail($ids[0], false);
        self::assertSame($ids[0], $message->id);
        self::assertSame('Příliš žluťoučký', $message->subject);
        self::assertSame('René', $message->fromName);
        self::assertSame('sender@example.test', $message->fromAddress);
        self::assertSame(['recipient@example.test' => 'Test Inbox'], $message->to);
        self::assertSame(['reply@example.test' => 'Replies'], $message->replyTo);
        self::assertSame('<original@example.test>', $message->inReplyTo);
        self::assertSame('mailbox-contract', $message->xHeaders['x-mautic-test']);
        self::assertSame('Hello, Příliš!', trim($message->textPlain));
        self::assertSame('<p>Hello</p>', trim($message->textHtml));
        self::assertSame($ids, $this->mailbox->fetchUnread());

        $attachments = array_values($message->getAttachments());
        self::assertCount(1, $attachments);
        self::assertSame('report.txt', $attachments[0]->name);
        self::assertSame("attachment content\n", file_get_contents($attachments[0]->filePath));
    }

    public function testReadUnreadAndImportantFlags(): void
    {
        $this->deliverFixture();
        $ids = $this->mailbox->fetchUnread();
        self::assertCount(1, $ids);
        $id = $ids[0];

        $this->mailbox->getMail($id);
        self::assertSame([], $this->mailbox->fetchUnread());
        self::assertTrue($this->mailbox->markMailAsUnread($id));
        self::assertSame([$id], $this->mailbox->fetchUnread());
        self::assertTrue($this->mailbox->markMailAsImportant($id));
        self::assertSame([$id], $this->mailbox->searchMailbox('FLAGGED'));
        self::assertTrue($this->mailbox->clearFlag([$id], '\\Flagged'));
        self::assertSame([], $this->mailbox->searchMailbox('FLAGGED'));
        self::assertTrue($this->mailbox->markMailAsRead($id));
        self::assertSame([], $this->mailbox->fetchUnread());
    }

    public function testSearchReturnsStableUidsAfterExpunge(): void
    {
        $this->deliverFixture();
        $this->deliverFixture();
        $ids = $this->mailbox->searchMailbox();
        self::assertCount(2, $ids);

        self::assertTrue($this->mailbox->deleteMail($ids[0]));
        self::assertTrue($this->mailbox->expungeDeletedMails());
        self::assertSame([$ids[1]], $this->mailbox->searchMailbox());
        self::assertSame([$ids[1]], $this->mailbox->searchMailbox('UID '.$ids[1].':*'));
        self::assertSame([$ids[1]], $this->mailbox->searchMailbox('FROM "sender@example.test"'));
        self::assertSame([], $this->mailbox->searchMailbox('FROM "missing@example.test"'));
        self::assertSame($ids[1], $this->mailbox->getMail($ids[1], false)->id);
        self::assertSame(1, $this->mailbox->countMails());
    }

    public function testMoveToAnotherFolderAndSaveRawMessage(): void
    {
        $this->deliverFixture();
        $ids = $this->mailbox->searchMailbox();
        self::assertCount(1, $ids);

        // Provision a destination through the native API, as an external client would.
        $settings           = $this->mailbox->getMailboxSettings();
        $settings['folder'] = 'Processed';
        $path               = $this->mailbox->getImapPath($settings);
        self::assertTrue(imap_createmailbox($this->mailbox->getImapStream(), $path['full']));
        self::assertContains('Processed', $this->mailbox->getListingFolders());
        self::assertTrue($this->mailbox->moveMail($ids[0], 'Processed'));
        self::assertSame([], $this->mailbox->searchMailbox());

        $this->mailbox->switchFolder('Processed');
        $movedIds = $this->mailbox->searchMailbox();
        self::assertCount(1, $movedIds);
        self::assertSame('Příliš žluťoučký', $this->mailbox->getMail($movedIds[0], false)->subject);
        $filename = $this->temporaryDirectory.'/saved.eml';
        self::assertTrue($this->mailbox->saveMail($movedIds[0], $filename));
        self::assertStringContainsString('Message-ID: <fixture@example.test>', file_get_contents($filename));
        self::assertTrue($this->mailbox->deleteMail($movedIds[0]));
        self::assertTrue($this->mailbox->expungeDeletedMails());
        self::assertSame([], $this->mailbox->searchMailbox());
    }

    public function testLegacyConnectionRemainsUsableByNativeImapFunctions(): void
    {
        $this->deliverFixture();
        $this->mailbox->setConnectionArgs(0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        $connection = $this->mailbox->getImapStream();

        self::assertInstanceOf(\IMAP\Connection::class, $connection);
        self::assertTrue(imap_ping($connection));
        self::assertSame($connection, $this->mailbox->getImapStream());
        self::assertSame(1, imap_num_msg($connection));
    }

    private function deliverFixture(): void
    {
        $fixture = file_get_contents(__DIR__.'/Fixtures/multipart.eml');
        self::assertNotFalse($fixture);
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
