<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Component\HttpFoundation\Request;

final class ScannerHoneypotLinkFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const CONTACT_EMAIL = 'honeypot-contact@example.com';

    protected function setUp(): void
    {
        $this->configParams['email_scanner_diagnostics_enabled'] = 'testNothingIsAddedWhenDiagnosticsAreOff' !== $this->name();
        $this->configParams['anonymize_ip']                      = false;

        parent::setUp();
    }

    public function testTheHoneypotLinkIsInTheHtmlOfASentEmailAndLeadsToItsSend(): void
    {
        $email   = $this->sendEmailToSegment();
        $message = $this->getSentMessage();
        $hash    = $this->getTrackingHash($email);

        $this->assertStringContainsString('<a href="https://localhost/email/check/'.$hash.'" aria-hidden="true"', (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('{scanner_honeypot_url}', (string) $message->getHtmlBody());
        $this->assertStringContainsString('/email/'.$hash.'.gif', (string) $message->getHtmlBody(), 'the tracking pixel is still there');

        // What a scanner following every link does with it.
        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/email/check/'.$hash, [], [], ['REMOTE_ADDR' => '40.94.12.34', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)']);

        $this->assertResponseIsSuccessful();

        $rows = $this->connection->fetchAllAssociative('SELECT * FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME);
        $this->assertCount(1, $rows);
        $this->assertSame(ScannerDiagnostic::EVENT_HONEYPOT, $rows[0]['event_type']);
        $this->assertSame($email->getId(), (int) $rows[0]['email_id']);
    }

    public function testThePlainTextVersionHasNoHoneypotLink(): void
    {
        $this->sendEmailToSegment();
        $message = $this->getSentMessage();

        $this->assertStringContainsString('/email/check/', (string) $message->getHtmlBody());
        $this->assertNotEmpty($message->getTextBody(), 'a plain text version is generated');
        $this->assertStringNotContainsString('/email/check/', (string) $message->getTextBody());
        $this->assertStringNotContainsString('scanner_honeypot', (string) $message->getTextBody());
    }

    public function testTheHoneypotLinkIsNotTrackedAsALink(): void
    {
        $email = $this->sendEmailToSegment();
        $this->assertStringContainsString('/email/check/'.$this->getTrackingHash($email), (string) $this->getSentMessage()->getHtmlBody());

        $redirects = $this->connection->fetchFirstColumn('SELECT url FROM '.MAUTIC_TABLE_PREFIX.'page_redirects');

        $this->assertSame(['https://example.org/offer'], $redirects, 'the real link is tracked and the honeypot is not');
    }

    public function testTheWebViewDoesNotShowTheHoneypotLink(): void
    {
        $email = $this->sendEmailToSegment();
        $hash  = $this->getTrackingHash($email);

        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/email/view/'.$hash);

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Hello honeypot', $content);
        $this->assertStringNotContainsString('/email/check/', $content);
        $this->assertStringNotContainsString('scanner_honeypot', $content);
        $this->assertStringNotContainsString('aria-hidden', $content);
    }

    public function testTheWebViewOfAnEmailSentBeforeTheHoneypotWasOffersNoLinkEither(): void
    {
        $email = $this->sendEmailToSegment();
        $hash  = $this->getTrackingHash($email);

        $this->connection->executeStatement('UPDATE '.MAUTIC_TABLE_PREFIX.'email_copies SET body = REPLACE(body, :url, :token)', [
            'url'   => 'https://localhost/email/check/'.$hash,
            'token' => '{scanner_honeypot_url}',
        ]);

        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/email/view/'.$hash);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('scanner_honeypot', (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('aria-hidden', (string) $this->client->getResponse()->getContent());
    }

    public function testTheBrowserPreviewDoesNotShowTheHoneypotLink(): void
    {
        $email = $this->sendEmailToSegment();

        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/email/preview/'.$email->getId());

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Hello honeypot', $content);
        $this->assertStringNotContainsString('/email/check/', $content);
        $this->assertStringNotContainsString('scanner_honeypot', $content);
    }

    public function testATestSendDoesNotGetTheHoneypotLink(): void
    {
        $email = $this->createTrackedEmail();
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $form    = $crawler->filter('form[name=example_send]')->form();
        $form->setValues(['example_send[emails][list][0]' => 'admin@yoursite.com']);
        $this->client->submit($form);

        $message = self::getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertStringContainsString('Hello honeypot', (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('/email/check/', (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('scanner_honeypot', (string) $message->getHtmlBody());
    }

    public function testNothingIsAddedWhenDiagnosticsAreOff(): void
    {
        $email   = $this->sendEmailToSegment();
        $message = $this->getSentMessage();

        $this->assertStringContainsString('Hello honeypot', (string) $message->getHtmlBody());
        $this->assertStringContainsString('/email/'.$this->getTrackingHash($email).'.gif', (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('/email/check/', (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('scanner_honeypot', (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('aria-hidden', (string) $message->getHtmlBody());
    }

    private function createTrackedEmail(): Email
    {
        $email = $this->createEmail('Honeypot email');
        $email->setSubject('Honeypot subject');
        $email->setEmailType('list');
        $email->setTemplate('Blank');
        $email->setPublicPreview(true);
        $email->setCustomHtml('<!DOCTYPE html><html><body><p>Hello honeypot</p><a href="https://example.org/offer">Offer</a></body></html>');
        $this->em->persist($email);

        return $email;
    }

    private function sendEmailToSegment(): Email
    {
        $segment = $this->createSegment('honeypot-segment', []);
        $this->em->flush();

        $contact = $this->createLead('', '', self::CONTACT_EMAIL);
        $this->createListLead($segment, $contact);

        $email = $this->createTrackedEmail();
        $email->setLists([$segment]);
        $this->em->flush();
        $this->em->clear();

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax?action=email:sendBatch', ['id' => $email->getId(), 'pending' => 1]);

        self::assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $this->assertSame('{"success":1,"percent":100,"progress":[1,1],"stats":{"sent":1,"failed":0,"failedRecipients":[]}}', $this->client->getResponse()->getContent());

        return $email;
    }

    private function getSentMessage(): MauticMessage
    {
        $message = self::getMailerMessagesByToAddress(self::CONTACT_EMAIL)[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        return $message;
    }

    private function getTrackingHash(Email $email): string
    {
        $hash = $this->connection->fetchOne('SELECT tracking_hash FROM '.MAUTIC_TABLE_PREFIX.'email_stats WHERE email_id = :id', ['id' => $email->getId()]);
        $this->assertIsString($hash);

        return $hash;
    }
}
