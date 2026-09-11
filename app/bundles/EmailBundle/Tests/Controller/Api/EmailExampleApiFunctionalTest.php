<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailExampleApiFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const RECIPIENT     = 'proof@example.com';
    private const RECIPIENT_ONE = 'one@example.com';
    private const RECIPIENT_TWO = 'two@example.com';
    private const EMAIL_SUBJECT = 'Email subject';

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
            ['recipients' => [self::RECIPIENT]]
        );

        self::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertTrue($response['success']);
        Assert::assertSame([self::RECIPIENT], $response['sent']);
        Assert::assertSame([], $response['errors']);

        $message = $this->getMailerMessagesByToAddress(self::RECIPIENT)[0];
        \assert($message instanceof MauticMessage);
        Assert::assertSame('[TEST] '.self::EMAIL_SUBJECT, $message->getSubject());
        // Fake contact data renders field tokens as bracketed labels, like the UI action.
        Assert::assertStringContainsString('Contact email is [Email].', $message->getBody()->toString());

        // No stat must be recorded for an example send.
        Assert::assertCount(0, $this->em->getRepository(Stat::class)->findBy(['email' => $emailId]));

        // The stored email must be untouched (subject not prefixed in the DB).
        $this->em->clear();
        $reloaded = $this->em->find(Email::class, $emailId);
        Assert::assertSame(self::EMAIL_SUBJECT, $reloaded->getSubject());
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
                'recipients' => [self::RECIPIENT],
                'contactId'  => $leadId,
            ]
        );

        self::assertResponseStatusCodeSame(200);

        $message = $this->getMailerMessagesByToAddress(self::RECIPIENT)[0];
        \assert($message instanceof MauticMessage);
        Assert::assertStringContainsString(
            'Contact email is john@domain.tld. Company: Mautic, Pune.',
            $message->getBody()->toString()
        );
    }

    public function testSendExampleToMultipleRecipients(): void
    {
        $email = $this->createEmail();
        $this->em->flush();
        $emailId = $email->getId();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            ['recipients' => [self::RECIPIENT_ONE, self::RECIPIENT_TWO]]
        );

        self::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertTrue($response['success']);
        Assert::assertSame([self::RECIPIENT_ONE, self::RECIPIENT_TWO], $response['sent']);
        Assert::assertCount(1, $this->getMailerMessagesByToAddress(self::RECIPIENT_ONE));
        Assert::assertCount(1, $this->getMailerMessagesByToAddress(self::RECIPIENT_TWO));
    }

    public function testNoSubjectPrefixOptionSkipsThePrefix(): void
    {
        $email = $this->createEmail();
        $this->em->flush();
        $emailId = $email->getId();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            [
                'recipients'      => [self::RECIPIENT],
                'noSubjectPrefix' => true,
            ]
        );

        self::assertResponseStatusCodeSame(200);
        $message = $this->getMailerMessagesByToAddress(self::RECIPIENT)[0];
        \assert($message instanceof MauticMessage);
        Assert::assertSame(self::EMAIL_SUBJECT, $message->getSubject());
    }

    public function testMissingRecipientsReturnsBadRequest(): void
    {
        $email = $this->createEmail();
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
            ['recipients' => [self::RECIPIENT]]
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeniedWithoutEmailViewPermission(): void
    {
        $email = $this->createEmail();
        $this->em->flush();
        $emailId = $email->getId();

        // This endpoint can send to arbitrary addresses, so the permission check is the main
        // guard: a non-admin user without Email view access must be denied.
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        \assert($user instanceof User);
        $this->setPermission($user->getRole(), ['email:emails' => []]);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(
            Request::METHOD_POST,
            "/api/emails/{$emailId}/example/send",
            ['recipients' => [self::RECIPIENT]]
        );

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function setPermission(Role $role, array $permissions): void
    {
        $roleModel = static::getContainer()->get('mautic.user.model.role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();
    }

    private function createEmail(bool $isPublished = true): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject(self::EMAIL_SUBJECT);
        $email->setTemplate('Blank');
        $email->setCustomHtml('Contact email is {contactfield=email}');
        $email->setIsPublished($isPublished);
        $this->em->persist($email);

        return $email;
    }
}
