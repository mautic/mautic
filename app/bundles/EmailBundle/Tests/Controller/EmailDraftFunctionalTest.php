<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailDraft;
use Symfony\Component\HttpFoundation\Request;

final class EmailDraftFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['email_draft_enabled'] = 'testEmailDraftNotConfigured' !== $this->name();

        parent::setUp();
    }

    public function testEmailDraftNotConfigured(): void
    {
        $email   = $this->createNewEmail();
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $this->assertCount(0, $crawler->selectButton('Save as Draft'));
        $this->assertCount(0, $crawler->selectButton('Apply Draft'));
        $this->assertCount(0, $crawler->selectButton('Discard Draft'));
    }

    public function testEmailDraftConfigured(): void
    {
        $email   = $this->createNewEmail();
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");

        $this->assertCount(1, $crawler->selectButton('Save as Draft'));
        $this->assertCount(0, $crawler->selectButton('Apply Draft'));
        $this->assertCount(0, $crawler->selectButton('Discard Draft'));
    }

    public function testCheckDraftInList(): void
    {
        $email   = $this->createNewEmail();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $this->assertStringNotContainsString('Has Draft', $crawler->filter('#app-content a[href="/s/emails/view/'.$email->getId().'"]')->html());
        $this->saveDraft($email);
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $this->assertStringContainsString('Has Draft', $crawler->filter('#app-content a[href="/s/emails/view/'.$email->getId().'"]')->html());
    }

    public function testPreviewDraft(): void
    {
        $email = $this->createNewEmail();
        $this->saveDraft($email);
        $crawler = $this->client->request(Request::METHOD_GET, "/email/preview/{$email->getId()}");
        $this->assertSame('Test html', $crawler->text());

        $crawler = $this->client->request(Request::METHOD_GET, "/email/preview/{$email->getId()}/draft");
        $this->assertSame('Test html Draft', $crawler->text());
    }

    public function testSaveDraftAndApplyDraftForLegacy(): void
    {
        $email = $this->createNewEmail();
        $this->applyDraft($email);
    }

    public function testDiscardDraftForLegacy(): void
    {
        $email       = $this->createNewEmail();
        $publishUp   = new \DateTime('2031-01-02 03:04:00');
        $publishDown = new \DateTime('2031-02-03 04:05:00');

        $email->setPlainText('Plain text live');
        $email->setPublishUp($publishUp);
        $email->setPublishDown($publishDown);
        $email->setContinueSending(true);
        $this->em->flush();

        $this->discardDraft($email);
        $this->em->refresh($email);

        $this->assertSame('Plain text live', $email->getPlainText());
        $this->assertSame(
            $publishUp->format('Y-m-d H:i:s'),
            $email->getPublishUp()?->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $publishDown->format('Y-m-d H:i:s'),
            $email->getPublishDown()?->format('Y-m-d H:i:s')
        );
        $this->assertTrue($email->getContinueSending());
    }

    public function testEmailDeleteCascade(): void
    {
        $email = $this->createNewEmail();
        $this->saveDraft($email);
        $this->client->request(Request::METHOD_POST, "/s/emails/delete/{$email->getId()}");
        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);
        $this->assertNotInstanceOf(EmailDraft::class, $emailDraft);
    }

    private function applyDraft(Email $email): void
    {
        $this->saveDraft($email);
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $form    = $crawler->selectButton('Apply Draft')->form();
        $this->client->submit($form);
        self::assertResponseIsSuccessful();

        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);

        $this->assertNotInstanceOf(EmailDraft::class, $emailDraft);
        $this->assertSame('Test html Draft', $email->getCustomHtml());
    }

    private function discardDraft(Email $email): void
    {
        $this->saveDraft($email);
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $form    = $crawler->selectButton('Discard Draft')->form();
        $this->client->submit($form);
        self::assertResponseIsSuccessful();

        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);

        $this->assertNotInstanceOf(EmailDraft::class, $emailDraft);
        $this->assertSame('Test html', $email->getCustomHtml());
    }

    private function saveDraft(Email $email): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");

        $form                          = $crawler->selectButton('Save as Draft')->form();
        $form['emailform[customHtml]'] = 'Test html Draft';
        $this->client->submit($form);
        self::assertResponseIsSuccessful();

        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(EmailDraft::class, $emailDraft);
        $this->assertSame('Test html Draft', $emailDraft->getHtml());
        $this->assertSame('Test html', $email->getCustomHtml());
    }

    private function createNewEmail(string $templateName = 'blank', string $templateContent = 'Test html'): Email
    {
        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('template');
        $email->setTemplate($templateName);
        $email->setCustomHtml($templateContent);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
