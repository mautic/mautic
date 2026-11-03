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

        if (false !== $expectedExtension = getenv('MAUTIC_TEST_IMAP_EXTENSION')) {
            $this->assertSame('imap' === $expectedExtension, extension_loaded('imap'));
        }

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

        $settings = $this->mailbox->getMailboxSettings();
        $provisioner = new \Horde_Imap_Client_Socket([
            'hostspec' => $settings['host'], 'port' => $settings['port'],
            'username' => $settings['user'], 'password' => $settings['password'],
            'secure' => false,
        ]);
        $provisioner->createMailbox('Processed');
        $provisioner->logout();
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
        if (!extension_loaded('imap')) {
            $this->expectException(\Mautic\EmailBundle\Exception\MailboxException::class);
            $this->expectExceptionMessage('Native IMAP connections require ext-imap');
            $this->mailbox->getImapStream();

            return;
        }

        $this->deliverFixture();
        $this->mailbox->setConnectionArgs(0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        $connection = $this->mailbox->getImapStream();

        $this->assertInstanceOf(\IMAP\Connection::class, $connection);
        $this->assertTrue(imap_ping($connection));
        $this->assertSame($connection, $this->mailbox->getImapStream());
        $this->assertSame(1, imap_num_msg($connection));
    }

    public function testEmptyMailboxAndStatus(): void
    {
        $this->assertSame([], $this->mailbox->searchMailbox());
        $this->assertSame(0, $this->mailbox->countMails());
        $this->assertSame(0, $this->mailbox->statusMailbox()->messages);
        $this->assertSame(0, $this->mailbox->checkMailbox()->Nmsgs);
        $this->assertSame(0, $this->mailbox->getMailboxInfo()->Size);
    }

    public function testOverviewSortingAndMailboxInfo(): void
    {
        $this->deliverFixture();
        $ids = $this->mailbox->searchMailbox();
        $this->assertSame($ids, $this->mailbox->sortMails());
        $overview = $this->mailbox->getMailsInfo($ids);
        $this->assertCount(1, $overview);
        $this->assertSame($ids[0], $overview[0]->uid);
        $this->assertSame('Příliš žluťoučký', $overview[0]->subject);
        $this->assertSame(1, $overview[0]->msgno);
        $this->assertEquals(0, $overview[0]->seen);
        $this->assertGreaterThan(0, $overview[0]->size);
        $info = $this->mailbox->getMailboxInfo();
        $this->assertSame(1, $info->Nmsgs);
        $this->assertSame(1, $info->Unread);
        $this->assertSame(0, $info->Deleted);
        $this->assertSame($overview[0]->size, $info->Size);
        $this->assertSame($ids, $this->mailbox->fetchUnread());
    }

    public function testAttachmentsCanBeDisabled(): void
    {
        $this->deliverFixture();
        $this->mailbox->setMailboxSettings(['use_attachments' => false]);
        $message = $this->mailbox->getMail($this->mailbox->searchMailbox()[0], false);
        $this->assertSame([], $message->getAttachments());
        $this->assertSame('Hello, Příliš!', trim($message->textPlain));
    }

    public function testDeliveryStatusReachesTheExistingBounceParser(): void
    {
        $this->deliverFixture('delivery-status');
        $message = $this->mailbox->getMail($this->mailbox->searchMailbox()[0], false);
        $bounce = (new \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser())->getBounce($message);
        $this->assertSame('failed@example.test', $bounce->getContactEmail());
        $this->assertStringContainsString('Action: failed', $message->dsnReport);
    }

    public function testFeedbackReportReachesTheExistingComplaintParser(): void
    {
        $this->deliverFixture('feedback-report');
        $message = $this->mailbox->getMail($this->mailbox->searchMailbox()[0], false);
        $parser = new \Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop\Parser($message);
        $this->assertSame('complaint@example.test', $parser->parse());
        $this->assertStringContainsString('Feedback-Type: abuse', (string) $message->fblReport);
    }

    public function testSearchCannotInjectAnotherProtocolCommand(): void
    {
        if (extension_loaded('imap')) {
            self::markTestSkipped('The native extension validates its own protocol input.');
        }
        $this->expectException(\Mautic\EmailBundle\Exception\MailboxException::class);
        $this->expectExceptionMessage('Invalid IMAP search criteria');
        $this->mailbox->searchMailbox("ALL\r\nA2 LOGOUT");
    }

    public function testNativeConnectionOptionsAreNotSilentlyIgnoredWithoutExtension(): void
    {
        if (extension_loaded('imap')) {
            self::markTestSkipped('Native connection options are covered by the legacy connection test.');
        }
        $this->expectException(\Mautic\EmailBundle\Exception\MailboxException::class);
        $this->expectExceptionMessage('Native IMAP connection arguments require ext-imap');
        $this->mailbox->setConnectionArgs(1);
    }

    public function testLibrarySupportsImplicitTlsWithExplicitSelfSignedCertificates(): void
    {
        $this->requireLibraryBackend();
        $port = (int) getenv('MAUTIC_TEST_IMAPS_PORT');
        if (!$port) {
            self::markTestSkipped('Set MAUTIC_TEST_IMAPS_PORT to enable TLS integration tests.');
        }
        $this->deliverFixture();
        $this->mailbox->setMailboxSettings(['encryption' => '/ssl/novalidate-cert', 'port' => $port]);
        $this->assertCount(1, $this->mailbox->searchMailbox());
    }

    public function testRequiredStartTlsCannotSilentlyFallBackToPlaintext(): void
    {
        $this->requireLibraryBackend();
        // GreenMail offers implicit TLS, but does not implement STARTTLS.
        $this->mailbox->setMailboxSettings(['encryption' => '/tls']);
        $this->expectException(\Mautic\EmailBundle\Exception\MailboxException::class);
        $this->expectExceptionMessage('Server does not support TLS connections');
        $this->mailbox->searchMailbox();
    }

    public function testLibraryRejectsUntrustedTlsCertificates(): void
    {
        $this->requireLibraryBackend();
        $port = (int) getenv('MAUTIC_TEST_IMAPS_PORT');
        if (!$port) {
            self::markTestSkipped('Set MAUTIC_TEST_IMAPS_PORT to enable TLS integration tests.');
        }
        $this->mailbox->setMailboxSettings(['encryption' => '/ssl', 'port' => $port]);
        $this->expectException(\Mautic\EmailBundle\Exception\MailboxException::class);
        $this->mailbox->searchMailbox();
    }

    public function testLibraryCreatesAndSelectsUnicodeFolders(): void
    {
        $this->requireLibraryBackend();
        $this->deliverFixture();
        $ids = $this->mailbox->searchMailbox();
        $settings = $this->mailbox->getMailboxSettings();
        $settings['folder'] = 'Příchozí';
        $this->mailbox->setImapPath($settings);
        $this->assertTrue($this->mailbox->createMailbox());
        $this->mailbox->setImapPath();
        $this->assertContains('Příchozí', $this->mailbox->getListingFolders());
        $this->assertTrue($this->mailbox->moveMail($ids[0], 'Příchozí'));
        $this->mailbox->switchFolder('Příchozí');
        $this->assertSame(1, $this->mailbox->countMails());
        $this->mailbox->switchFolder('INBOX');
        $this->assertSame([], $this->mailbox->searchMailbox());
    }

    public function testLibraryWrapsConnectionFailuresInMailboxException(): void
    {
        $this->requireLibraryBackend();
        $this->mailbox->setMailboxSettings(['port' => 1]);
        $this->expectException(\Mautic\EmailBundle\Exception\MailboxException::class);
        $this->mailbox->searchMailbox();
    }

    private function requireLibraryBackend(): void
    {
        if (extension_loaded('imap')) {
            self::markTestSkipped('This test exercises the extension-free backend.');
        }
    }

    private function deliverFixture(string $name = 'multipart'): void
    {
        $fixture = file_get_contents(__DIR__.'/Fixtures/'.$name.'.eml');
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
