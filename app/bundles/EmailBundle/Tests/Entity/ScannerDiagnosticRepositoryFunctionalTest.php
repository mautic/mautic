<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\ScannerDiagnosticRepository;
use Mautic\PageBundle\Entity\Redirect;

final class ScannerDiagnosticRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testTheRepositoryIsTheCustomOne(): void
    {
        $this->assertInstanceOf(ScannerDiagnosticRepository::class, $this->em->getRepository(ScannerDiagnostic::class));
    }

    public function testAClickIsStoredAndReadBack(): void
    {
        $email    = $this->createEmail();
        $redirect = $this->createRedirect();
        $headers  = [
            'accept'         => 'text/html',
            'sec-fetch-mode' => 'navigate',
        ];

        $diagnostic = new ScannerDiagnostic(
            new \DateTimeImmutable('2026-11-25 10:00:00'),
            ScannerDiagnostic::EVENT_CLICK,
            $email,
            'a1b2c3d4e5f60718',
            $redirect,
            42,
            '0123456789abcdef',
            '40.94.12.0/24',
            'Mozilla/5.0',
            $headers,
            true,
            false,
        );
        $this->em->persist($diagnostic);
        $this->em->flush();
        $this->em->clear();

        $stored = $this->em->getRepository(ScannerDiagnostic::class)->find($diagnostic->getId());

        $this->assertInstanceOf(ScannerDiagnostic::class, $stored);
        $this->assertSame('2026-11-25 10:00:00', $stored->getDateAdded()->format('Y-m-d H:i:s'));
        $this->assertSame(ScannerDiagnostic::EVENT_CLICK, $stored->getEventType());
        $this->assertSame($email->getId(), $stored->getEmail()?->getId());
        $this->assertSame('a1b2c3d4e5f60718', $stored->getSendKey());
        $this->assertSame($redirect->getId(), $stored->getRedirect()?->getId());
        $this->assertSame(42, $stored->getSecondsAfterSend());
        $this->assertSame('0123456789abcdef', $stored->getIpHash());
        $this->assertSame('40.94.12.0/24', $stored->getIpNetwork());
        $this->assertSame('Mozilla/5.0', $stored->getUserAgent());
        $this->assertSame($headers, $stored->getHeaders());
        $this->assertTrue($stored->isHttps());
        $this->assertFalse($stored->isBotRatioFlagged());
    }

    public function testAnOpenWithoutOptionalValuesIsStored(): void
    {
        $diagnostic = new ScannerDiagnostic(
            new \DateTimeImmutable(),
            ScannerDiagnostic::EVENT_OPEN,
            null,
            null,
            null,
            null,
            '0123456789abcdef',
            null,
            null,
            [],
            false,
            null,
        );
        $this->em->persist($diagnostic);
        $this->em->flush();
        $this->em->clear();

        $stored = $this->em->getRepository(ScannerDiagnostic::class)->find($diagnostic->getId());

        $this->assertInstanceOf(ScannerDiagnostic::class, $stored);
        $this->assertNotInstanceOf(Email::class, $stored->getEmail());
        $this->assertNull($stored->getSendKey());
        $this->assertNotInstanceOf(Redirect::class, $stored->getRedirect());
        $this->assertNull($stored->getSecondsAfterSend());
        $this->assertNull($stored->getIpNetwork());
        $this->assertNull($stored->getUserAgent());
        $this->assertSame([], $stored->getHeaders());
        $this->assertFalse($stored->isHttps());
        $this->assertNull($stored->isBotRatioFlagged());
    }

    public function testDeletingTheEmailDeletesItsDiagnostics(): void
    {
        $email      = $this->createEmail();
        $otherEmail = $this->createEmail();
        $this->createDiagnostic($email, null);
        $this->createDiagnostic($otherEmail, null);

        $this->connection->delete(MAUTIC_TABLE_PREFIX.Email::TABLE_NAME, ['id' => $email->getId()]);

        $this->assertSame(0, $this->countDiagnostics('email_id = '.$email->getId()));
        $this->assertSame(1, $this->countDiagnostics('email_id = '.$otherEmail->getId()));
    }

    public function testDeletingTheRedirectKeepsTheDiagnosticWithoutIt(): void
    {
        $email    = $this->createEmail();
        $redirect = $this->createRedirect();
        $this->createDiagnostic($email, $redirect);

        $this->connection->delete(MAUTIC_TABLE_PREFIX.'page_redirects', ['id' => $redirect->getId()]);

        $this->assertSame(1, $this->countDiagnostics('email_id = '.$email->getId().' AND redirect_id IS NULL'));
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Scanner diagnostics');
        $email->setSubject('Scanner diagnostics');
        $email->setEmailType('template');
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createRedirect(): Redirect
    {
        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl('https://example.com');
        $this->em->persist($redirect);
        $this->em->flush();

        return $redirect;
    }

    private function createDiagnostic(Email $email, ?Redirect $redirect): void
    {
        $this->em->persist(new ScannerDiagnostic(
            new \DateTimeImmutable(),
            null === $redirect ? ScannerDiagnostic::EVENT_HONEYPOT : ScannerDiagnostic::EVENT_CLICK,
            $email,
            'a1b2c3d4e5f60718',
            $redirect,
            5,
            '0123456789abcdef',
            '40.94.12.0/24',
            'Mozilla/5.0',
            [],
            true,
            null,
        ));
        $this->em->flush();
    }

    private function countDiagnostics(string $where): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME.' WHERE '.$where
        );
    }
}
