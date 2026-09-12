<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\SMimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Message;

#[Group('non-parallel')]
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
        $this->assertResponseIsSuccessful();
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
        $this->assertResponseIsSuccessful();

        $email = self::getMailerMessages()[0]->toString();
        $this->assertStringContainsString('Hey John...', $email);
        $this->assertStringContainsString('Subject: Some interesting subject for John', $email);
        $this->assertStringContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        $this->assertStringContainsString('Content-Type: application/x-pkcs7-signature; name="smime.p7s"', $email);
        $this->assertStringContainsString('Content-Disposition: attachment; filename="smime.p7s"', $email);
    }

    public function testSMimeWithEncryptedPrivateKey(): void
    {
        /** @var EncryptionHelper $encryptionHelper */
        $encryptionHelper = self::getContainer()->get(EncryptionHelper::class);
        $this->assertInstanceOf(EncryptionHelper::class, $encryptionHelper);

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
        $this->assertResponseIsSuccessful();
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
        $this->assertResponseIsSuccessful();

        $email = self::getMailerMessages()[0]->toString();
        $this->assertStringContainsString('Hey John...', $email);
        $this->assertStringContainsString('Subject: Some interesting subject for John', $email);
        $this->assertStringContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        $this->assertStringContainsString('Content-Type: application/x-pkcs7-signature; name="smime.p7s"', $email);
        $this->assertStringContainsString('Content-Disposition: attachment; filename="smime.p7s"', $email);
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
        $this->assertResponseIsSuccessful();
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
        $this->assertResponseIsSuccessful();

        $email = self::getMailerMessages()[0]->toString();
        $this->assertStringContainsString('Hey John...', $email);
        $this->assertStringContainsString('Subject: Some interesting subject for John', $email);
        $this->assertStringNotContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        $this->assertStringNotContainsString('Content-Type: application/x-pkcs7-signature; name="smime.p7s"', $email);
        $this->assertStringNotContainsString('Content-Disposition: attachment; filename="smime.p7s"', $email);
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
        $this->assertResponseIsSuccessful();
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
        $this->assertResponseIsSuccessful();

        $messages = self::getMailerMessages();
        $this->assertCount(1, $messages, 'Expected exactly one email message to be sent');
        $rawMessage = $messages[0];
        $this->assertInstanceOf(Message::class, $rawMessage);

        // For signed messages, use toString() instead of getBody()
        $email   = $rawMessage->toString();
        $this->assertStringContainsString('Hey John...', $email);
        $this->assertStringContainsString('<title>Some interesting subject for John</title>', $email);
        $this->assertStringContainsString('Some interesting subject for John', $email);
        $this->assertStringContainsString('preheader text', $email);
        $this->assertStringContainsString('admin@test-beta.mautibot.com', $email);
        $this->assertStringContainsString('Admin', $email);
        $this->assertStringNotContainsString('This should be overwritten by the form content', $email);

        $this->assertFalse($rawMessage->getHeaders()->has('List-Unsubscribe'));
        $this->assertFalse($rawMessage->getHeaders()->has('List-Unsubscribe-Post'));
    }

    public function testEmailSendWhenSubjectOrBodyIsMissing(): void
    {
        $lead = new Lead();
        $lead->setEmail('lead@email.com');
        $lead->setFirstname('Lead1');

        $this->em->persist($lead);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$lead->getId());
        $this->assertResponseIsSuccessful();

        $content     = json_decode($this->client->getResponse()->getContent())->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $form        = $crawler->filter('form')->form();

        $subjectErrorMessage = 'A subject is required.';
        $bodyErrorMessage    = 'A message is required.';

        $form->setValues([
            'lead_quickemail[fromname]'  => 'Admin',
            'lead_quickemail[from]'      => 'admin@yoursite.com',
            'lead_quickemail[body]'      => '<html><body><p>Hello</p></body></html>',
        ]);
        $this->client->submit($form);
        $responseContent = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($subjectErrorMessage, (string) $responseContent, 'The missing subject line should show an error');
        $this->assertStringNotContainsString($bodyErrorMessage, (string) $responseContent, 'There should be no error about the email body');

        $form->setValues([
            'lead_quickemail[fromname]'  => 'Admin',
            'lead_quickemail[from]'      => 'admin@yoursite.com',
            'lead_quickemail[subject]'   => 'Subject',
            'lead_quickemail[body]'      => '<html><body></body></html>',
        ]);
        $this->client->submit($form);
        $responseContent = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($bodyErrorMessage, (string) $responseContent, 'The missing body should show an error');
        $this->assertStringNotContainsString($subjectErrorMessage, (string) $responseContent, 'There should be no error about the subject line');

        $form->setValues([
            'lead_quickemail[fromname]'  => 'Admin',
            'lead_quickemail[from]'      => 'admin@yoursite.com',
            'lead_quickemail[subject]'   => 'Subject',
            'lead_quickemail[body]'      => '<html><body><p>Hello</p></body></html>',
        ]);
        $this->client->submit($form);
        $responseContent = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString($subjectErrorMessage, (string) $responseContent, 'There should be no error after adding the subject line');
        $this->assertStringNotContainsString($bodyErrorMessage, (string) $responseContent, 'There should be no error after adding the body');
    }
}
