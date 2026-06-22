<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSendExampleApiBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class ExampleSendApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected $useCleanupRollback = false;

    public function testSendExampleWithoutContactFillsFakeData(): void
    {
        // Unpublished on purpose: example sends must work on drafts.
        $email = $this->createEmail(false);
        $email->setCustomHtml('Contact email is {contactfield=email}. Company: {contactfield=companyname}, {contactfield=companycity}.');
        $this->em->flush();
        $emailId = $email->getId();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            ['recipients' => ['proof@example.com']]
        );

        self::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertTrue($response['success']);
        Assert::assertSame(['proof@example.com'], $response['sent']);
        Assert::assertSame([], $response['errors']);

        $message = $this->getMailerMessagesByToAddress('proof@example.com')[0];
        \assert($message instanceof MauticMessage);
        Assert::assertSame('[TEST] Email subject', $message->getSubject());
        // Fake contact data renders field tokens as bracketed labels, like the UI action.
        Assert::assertStringContainsString('Contact email is [Email].', $message->getBody()->toString());

        // No stat must be recorded for an example send.
        Assert::assertCount(0, $this->em->getRepository(Stat::class)->findBy(['email' => $emailId]));

        // The stored email must be untouched (subject not prefixed in the DB).
        $this->em->clear();
        $reloaded = $this->em->find(Email::class, $emailId);
        Assert::assertSame('Email subject', $reloaded->getSubject());
    }

    public function testSendExampleWithContactUsesContactData(): void
    {
        $company = $this->createCompany('Mautic', 'hello@mautic.org');
        $company->setCity('Pune');
        $this->em->persist($company);

        $lead = $this->createLead('John', 'Doe', 'john@domain.tld');
        $this->createPrimaryCompanyForLead($lead, $company);

        $email = $this->createEmail();
        $email->setCustomHtml('Contact email is {contactfield=email}. Company: {contactfield=companyname}, {contactfield=companycity}.');
        $this->em->flush();
        $emailId = $email->getId();
        $leadId  = $lead->getId();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            [
                'recipients' => ['proof@example.com'],
                'contactId'  => $leadId,
            ]
        );

        self::assertResponseStatusCodeSame(200);

        $message = $this->getMailerMessagesByToAddress('proof@example.com')[0];
        \assert($message instanceof MauticMessage);
        Assert::assertStringContainsString(
            'Contact email is john@domain.tld. Company: Mautic, Pune.',
            $message->getBody()->toString()
        );
    }

    public function testSendExampleToMultipleRecipients(): void
    {
        $email   = $this->createEmail();
        $this->em->flush();
        $emailId = $email->getId();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            ['recipients' => ['one@example.com', 'two@example.com']]
        );

        self::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertTrue($response['success']);
        Assert::assertSame(['one@example.com', 'two@example.com'], $response['sent']);
        Assert::assertCount(1, $this->getMailerMessagesByToAddress('one@example.com'));
        Assert::assertCount(1, $this->getMailerMessagesByToAddress('two@example.com'));
    }

    public function testNoSubjectPrefixOptionSkipsThePrefix(): void
    {
        $email   = $this->createEmail();
        $this->em->flush();
        $emailId = $email->getId();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            [
                'recipients'      => ['proof@example.com'],
                'noSubjectPrefix' => true,
            ]
        );

        self::assertResponseStatusCodeSame(200);
        $message = $this->getMailerMessagesByToAddress('proof@example.com')[0];
        \assert($message instanceof MauticMessage);
        Assert::assertSame('Email subject', $message->getSubject());
    }

    public function testMissingRecipientsReturnsBadRequest(): void
    {
        $email   = $this->createEmail();
        $this->em->flush();
        $emailId = $email->getId();
        $this->em->clear();

        $this->client->request(Request::METHOD_POST, "/api/emails/{$emailId}/example/send", []);

        self::assertResponseStatusCodeSame(400);
    }

    public function testUnknownEmailReturnsNotFound(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/emails/999999/example/send',
            ['recipients' => ['proof@example.com']]
        );

        self::assertResponseStatusCodeSame(404);
    }

    private function createEmail(bool $isPublished = true): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setCustomHtml('Contact email is {contactfield=email}');
        $email->setIsPublished($isPublished);
        $this->em->persist($email);

        return $email;
    }
}
