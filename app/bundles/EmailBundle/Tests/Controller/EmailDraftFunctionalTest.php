<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailDraft;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailDraftFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['email_draft_enabled'] = 'testEmailDraftNotConfigured' !== $this->getName();

        parent::setUp();
    }

    public function testEmailDraftNotConfigured(): void
    {
        $email   = $this->createNewEmail();
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        Assert::assertEquals(0, $crawler->selectButton('Save as Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Apply Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Discard Draft')->count());
    }

    public function testEmailDraftConfigured(): void
    {
        $email   = $this->createNewEmail();
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");

        Assert::assertEquals(1, $crawler->selectButton('Save as Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Apply Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Discard Draft')->count());
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
        $this->assertEquals('Test html', $crawler->text());

        $crawler = $this->client->request(Request::METHOD_GET, "/email/preview/{$email->getId()}/draft");
        $this->assertEquals('Test html Draft', $crawler->text());
    }

    public function testSaveDraftAndApplyDraftForLegacy(): void
    {
        $email = $this->createNewEmail();
        $this->applyDraft($email);
    }

    public function testDiscardDraftForLegacy(): void
    {
        $email = $this->createNewEmail();
        $this->discardDraft($email);
    }

    public function testEmailDeleteCascade(): void
    {
        $email = $this->createNewEmail();
        $this->saveDraft($email);
        $this->client->request(Request::METHOD_POST, "/s/emails/delete/{$email->getId()}");
        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);
        Assert::assertNull($emailDraft);
    }

    private function applyDraft(Email $email): void
    {
        $this->saveDraft($email);
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $form    = $crawler->selectButton('Apply Draft')->form();
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);

        Assert::assertNull($emailDraft);
        Assert::assertSame('Test html Draft', $email->getCustomHtml());
    }

    private function discardDraft(Email $email): void
    {
        $this->saveDraft($email);
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $form    = $crawler->selectButton('Discard Draft')->form();
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);

        Assert::assertNull($emailDraft);
        Assert::assertSame('Test html', $email->getCustomHtml());
    }

    private function saveDraft(Email $email): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");

        $form                          = $crawler->selectButton('Save as Draft')->form();
        $form['emailform[customHtml]'] = 'Test html Draft';
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $emailDraft = $this->em->getRepository(EmailDraft::class)->findOneBy(['email' => $email]);
        Assert::assertEquals('Test html Draft', $emailDraft->getHtml());
        Assert::assertSame('Test html', $email->getCustomHtml());
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
