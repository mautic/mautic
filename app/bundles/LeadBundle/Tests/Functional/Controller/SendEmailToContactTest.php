<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\SMimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class SendEmailToContactTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;
    private SMimeHelper $sMimeHelper;

    protected function setUp(): void
    {
        $this->configParams['smime_signing_enabled']   = true;
        $this->configParams['smime_certificates_path'] = '%kernel.project_dir%/app/bundles/EmailBundle/Tests/Mocks/Certificates/SMime';

        parent::setUp();

        $this->sMimeHelper = self::getContainer()->get(SMimeHelper::class);
    }

    protected function beforeTearDown(): void
    {
        parent::beforeTearDown();

        $certPath = $this->sMimeHelper->getSMimeCertificatePath();

        // Rename the backup back to the original
        if (file_exists($certPath.'/admin@test-beta.mautibot.com.pem.bak')) {
            rename($certPath.'/admin@test-beta.mautibot.com.pem.bak', $certPath.'/admin@test-beta.mautibot.com.pem');
        }

        // Delete the encrypted file
        if (file_exists($certPath.'/admin@test-beta.mautibot.com.pem.enc')) {
            unlink($certPath.'/admin@test-beta.mautibot.com.pem.enc');
        }
    }

    public function testSMimeWithUnecryptedPrivateKey(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $contact->setFirstname('John');
        $this->em->persist($contact);
        $this->em->flush();

        // Fetch the form
        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]' => 'Admin',
            'lead_quickemail[from]'     => 'admin@test-beta.mautibot.com',
            'lead_quickemail[subject]'  => 'Some interesting subject for {contactfield=firstname}',
            'lead_quickemail[body]'     => '<html><body><p>Hey {contactfield=firstname}...</p></body></html>',
            'lead_quickemail[list]'     => 0,
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $email = self::getMailerMessages()[0]->toString();
        Assert::assertStringContainsString('Hey John...', $email);
        Assert::assertStringContainsString('Subject: Some interesting subject for John', $email);
        Assert::assertStringContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        Assert::assertStringContainsString('Content-Type: application/x-pkcs7-signature; name="smime.p7s"', $email);
        Assert::assertStringContainsString('Content-Disposition: attachment; filename="smime.p7s"', $email);
    }

    public function testSMimeWithEncryptedPrivateKey(): void
    {
        $encryptionHelper = self::getContainer()->get('mautic.helper.encryption');
        \assert($encryptionHelper instanceof EncryptionHelper);

        $certPath       = $this->sMimeHelper->getSMimeCertificatePath();
        $privateKeyPath = $certPath.'/admin@test-beta.mautibot.com.pem';

        // Create the encrypted private key
        file_put_contents($privateKeyPath.'.enc', $encryptionHelper->encrypt(file_get_contents($privateKeyPath)));

        // Rename the original private key so it is clear it is not being used here
        rename($privateKeyPath, $privateKeyPath.'.bak');

        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $contact->setFirstname('John');
        $this->em->persist($contact);
        $this->em->flush();

        // Fetch the form
        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]' => 'Admin',
            'lead_quickemail[from]'     => 'admin@test-beta.mautibot.com',
            'lead_quickemail[subject]'  => 'Some interesting subject for {contactfield=firstname}',
            'lead_quickemail[body]'     => '<html><body><p>Hey {contactfield=firstname}...</p></body></html>',
            'lead_quickemail[list]'     => 0,
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $email = self::getMailerMessages()[0]->toString();
        Assert::assertStringContainsString('Hey John...', $email);
        Assert::assertStringContainsString('Subject: Some interesting subject for John', $email);
        Assert::assertStringContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        Assert::assertStringContainsString('Content-Type: application/x-pkcs7-signature; name="smime.p7s"', $email);
        Assert::assertStringContainsString('Content-Disposition: attachment; filename="smime.p7s"', $email);
    }

    public function testSendingEmailIfCertificateIsMissing(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $contact->setFirstname('John');
        $this->em->persist($contact);
        $this->em->flush();

        // Fetch the form
        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]' => 'Admin',
            'lead_quickemail[from]'     => 'unicorn@test-beta.mautibot.com',
            'lead_quickemail[subject]'  => 'Some interesting subject for {contactfield=firstname}',
            'lead_quickemail[body]'     => '<html><body><p>Hey {contactfield=firstname}...</p></body></html>',
            'lead_quickemail[list]'     => 0,
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $email = self::getMailerMessages()[0]->toString();
        Assert::assertStringContainsString('Hey John...', $email);
        Assert::assertStringContainsString('Subject: Some interesting subject for John', $email);
        Assert::assertStringNotContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        Assert::assertStringNotContainsString('Content-Type: application/x-pkcs7-signature; name="smime.p7s"', $email);
        Assert::assertStringNotContainsString('Content-Disposition: attachment; filename="smime.p7s"', $email);
    }

    public function testPreheaderConfigIsApplied(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $contact->setFirstname('John');

        $emailEntity = new Email();
        $emailEntity->setName('Email A');
        $emailEntity->setFromAddress('overwrite@address.com');
        $emailEntity->setFromName('Overwrite Name');
        $emailEntity->setSubject('Subject to overwrite');
        $emailEntity->setCustomHtml('<html><body><p>This should be overwritten by the form content</p></body></html>');
        $emailEntity->setPreheaderText('This is a preheader text');

        $this->em->persist($contact);
        $this->em->persist($emailEntity);
        $this->em->flush();

        // Fetch the form
        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]'  => 'Admin',
            'lead_quickemail[from]'      => 'admin@test-beta.mautibot.com',
            'lead_quickemail[subject]'   => 'Some interesting subject for {contactfield=firstname}',
            'lead_quickemail[body]'      => '<html><body><p>Hey {contactfield=firstname}...</p></body></html>',
            'lead_quickemail[list]'      => 0,
            'lead_quickemail[templates]' => $emailEntity->getId(),
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $messages = self::getMailerMessages();
        Assert::assertCount(1, $messages, 'Expected exactly one email message to be sent');
        $rawMessage = $messages[0];
        Assert::assertInstanceOf(\Symfony\Component\Mime\Message::class, $rawMessage);
        \assert($rawMessage instanceof \Symfony\Component\Mime\Message);

        // For signed messages, use toString() instead of getBody()
        $email   = $rawMessage->toString();
        Assert::assertStringContainsString('Hey John...', $email);
        Assert::assertStringContainsString('<title>Some interesting subject for John</title>', $email);
        Assert::assertStringContainsString('Some interesting subject for John', $email);
        Assert::assertStringContainsString('preheader text', $email);
        Assert::assertStringContainsString('admin@test-beta.mautibot.com', $email);
        Assert::assertStringContainsString('Admin', $email);
        Assert::assertStringNotContainsString('This should be overwritten by the form content', $email);

        Assert::assertFalse($rawMessage->getHeaders()->has('List-Unsubscribe'));
        Assert::assertFalse($rawMessage->getHeaders()->has('List-Unsubscribe-Post'));
    }
}
