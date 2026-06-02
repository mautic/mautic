<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\RawMessage;

final class SMimeFunctionalTest extends MauticMysqlTestCase
{
    private string $certPath;

    protected function setUp(): void
    {
        $this->configParams['smime_signing_enabled']   = true;
        $this->configParams['smime_certificates_path'] = '%kernel.project_dir%/app/bundles/EmailBundle/Tests/Mocks/Certificates/SMime';
        $this->configParams['mailer_from_email']       = 'admin@test-beta.mautibot.com';
        $this->configParams['messenger_dsn_email']     = 'sync://';
        $this->configParams['mailer_dsn']              = 'null://null';
        $this->configParams['secret_key']              = 'test_secret_key_for_encryption';

        parent::setUp();

        $this->certPath = $this->getContainer()->getParameter('kernel.project_dir').'/app/bundles/EmailBundle/Tests/Mocks/Certificates/SMime';
    }

    protected function beforeTearDown(): void
    {
        $this->cleanupEncryptedCertificate();
        parent::beforeTearDown();
    }

    /**
     * @return iterable<string, array{encrypted: bool}>
     */
    public static function certificateTypeProvider(): iterable
    {
        yield 'unencrypted certificate' => ['encrypted' => false];
        yield 'encrypted certificate' => ['encrypted' => true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('certificateTypeProvider')]
    public function testSendingSegmentEmailWithSMime(bool $encrypted): void
    {
        if ($encrypted) {
            $this->createEncryptedCertificate();
        }

        $segment  = $this->createSegment('Test Segment', 'test-segment');
        $contacts = ['john@doe.email', 'anna@doe.email'];

        foreach ($contacts as $contactEmail) {
            $contact = $this->createContact($contactEmail);
            $this->createSegmentMember($contact, $segment);
        }

        $this->em->flush();

        $email = $this->createEmail(
            'Test Email',
            'Test Subject',
            'list',
            'blank',
            '<h1>Hey {contactfield=email}</h1>',
            [$segment->getId() => $segment]
        );

        $this->em->flush();

        $this->sendEmailBatchAndAssertSuccess($email, count($contacts));

        // Verify all contacts received signed emails
        foreach ($contacts as $contactEmail) {
            $message = $this->getMailerMessagesByToAddress($contactEmail)[0];
            Assert::assertStringContainsString('Hey '.$contactEmail, $message->toString());
            $this->assertMessageIsSigned($message, 'Test Subject');
        }
    }

    private function sendEmailBatchAndAssertSuccess(Email $email, int $expectedCount): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/s/ajax?action=email:sendBatch',
            ['id' => $email->getId(), 'pending' => $expectedCount],
            [],
            $this->createAjaxHeaders()
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert that emails were sent successfully
        Assert::assertEquals(1, $response['success']);
        Assert::assertEquals([$expectedCount, $expectedCount], $response['progress']);
        Assert::assertEquals(100, $response['percent']);
        Assert::assertEquals($expectedCount, $response['stats']['sent']);
        Assert::assertEquals(0, $response['stats']['failed']);
        Assert::assertEmpty($response['stats']['failedRecipients']);

        // With sync messenger, emails are sent immediately
        $this->assertEmailCount($expectedCount);
    }

    private function createEncryptedCertificate(): void
    {
        $privateKeyPath          = $this->certPath.'/admin@test-beta.mautibot.com.pem';
        $privateKeyEncryptedPath = $this->certPath.'/admin@test-beta.mautibot.com.pem.enc';

        // Read the unencrypted private key
        $privateKeyContent = file_get_contents($privateKeyPath);

        // Encrypt it using the EncryptionHelper
        $encryptionHelper = $this->getContainer()->get(\Mautic\CoreBundle\Helper\EncryptionHelper::class);
        $encryptedContent = $encryptionHelper->encrypt($privateKeyContent);

        // Write the encrypted content to .pem.enc file
        file_put_contents($privateKeyEncryptedPath, $encryptedContent);
    }

    private function cleanupEncryptedCertificate(): void
    {
        $privateKeyEncryptedPath = $this->certPath.'/admin@test-beta.mautibot.com.pem.enc';
        if (file_exists($privateKeyEncryptedPath)) {
            @unlink($privateKeyEncryptedPath);
        }
    }

    private function createSegment(string $name, string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);
        $this->em->persist($segment);

        return $segment;
    }

    private function createSegmentMember(Lead $contact, LeadList $segment): ListLead
    {
        $member = new ListLead();
        $member->setLead($contact);
        $member->setList($segment);
        $member->setDateAdded(new \DateTime('5 sec ago')); // Falis in CI otherwise.
        $member->setManuallyRemoved(false);
        $this->em->persist($member);

        return $member;
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    /**
     * @param array<int, mixed> $segments
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function createEmail(string $name, string $subject, string $emailType, string $template, string $customHtml, array $segments = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setLists($segments);
        $email->setPublishUp(new \DateTime('1 second ago'));
        $email->setIsPublished(true);
        $this->em->persist($email);

        return $email;
    }

    private function assertMessageIsSigned(RawMessage $message, string $expectedSubject): void
    {
        $email = $message->toString();
        Assert::assertStringContainsString('Subject: '.$expectedSubject, $email);
        Assert::assertStringContainsString('Content-Type: multipart/signed; protocol="application/x-pkcs7-signature";', $email);
        Assert::assertSame(1, substr_count($email, 'Content-Disposition: attachment; filename="smime.p7s"'), $email);
        Assert::assertSame(1, substr_count($email, 'Content-Type: application/x-pkcs7-signature; name="smime.p7s'), $email);
    }
}
