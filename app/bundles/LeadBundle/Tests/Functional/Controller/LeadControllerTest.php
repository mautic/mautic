<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Command\ContactScheduledExportCommand;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LeadControllerTest extends MauticMysqlTestCase
{
    public const USERNAME = 'jhony';
    /**
     * @var array<string>
     */
    private array $filePaths = [];

    protected function setUp(): void
    {
        $this->configParams['contact_export_dir'] = '/tmp';
        parent::setUp();
    }

    protected function beforeTearDown(): void
    {
        foreach ($this->filePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function testContactExportIsScheduledForCsvFileType(): void
    {
        $this->createContacts();
        $this->client->request(
            Request::METHOD_POST,
            's/contacts/batchExport',
            ['filetype' => 'csv']
        );
        Assert::assertTrue($this->client->getResponse()->isOk());
        $contactExportSchedulerRows = $this->checkContactExportScheduler(1);
        /** @var ContactExportScheduler $contactExportScheduler */
        $contactExportScheduler     = $contactExportSchedulerRows[0];
        $this->testSymfonyCommand(ContactScheduledExportCommand::COMMAND_NAME, ['--ids' => $contactExportScheduler->getId()]);
        $this->checkContactExportScheduler(0);
        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper    = static::getContainer()->get('mautic.helper.core_parameters');
        $zipFileName             = 'contacts_export_'.$contactExportScheduler->getScheduledDateTime()
                ->format('Y_m_d_H_i_s').'.zip';
        $this->filePaths[] = $filePath = $coreParametersHelper->get('contact_export_dir').'/'.$zipFileName;
        Assert::assertFileExists($filePath);

        $link = $this->router->generate(
            'mautic_contact_export_download',
            ['fileName' => basename($filePath)],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->client->request(Request::METHOD_GET, $link);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $notFoundLink = $this->router->generate(
            'mautic_contact_export_download',
            ['fileName' => 'non_existing.zip'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->client->request(Request::METHOD_GET, $notFoundLink);
        Assert::assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testAdminUsersReceiveSecurityNotificationWhenContactExportIsScheduled(): void
    {
        $this->createContacts();
        $this->setAdminUser();

        $adminRole      = $this->createRole(true, 'Security Admin');
        $secondaryAdmin = $this->createUser($adminRole, 'security-admin', 'security.admin@email.com');
        $secondaryAdmin->setFirstName('Security');
        $secondaryAdmin->setLastName('Admin');
        $secondaryAdmin->setIsPublished(true);

        $userRole = $this->createRole(false, 'Regular User');
        $user     = $this->createUser($userRole, 'regular-user', 'regular.user@email.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setIsPublished(true);

        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            's/contacts/batchExport',
            ['filetype' => 'csv']
        );

        Assert::assertTrue($this->client->getResponse()->isOk());

        /** @var ContactExportScheduler $contactExportScheduler */
        $contactExportScheduler = $this->checkContactExportScheduler(1)[0];
        /** @var DateHelper $dateHelper */
        $dateHelper             = static::getContainer()->get('mautic.helper.twig.date');
        $requestedAt            = $dateHelper->toFull($contactExportScheduler->getScheduledDateTime());
        $requestingAdmin        = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);

        $requesterNotifications = $this->em->getRepository(Notification::class)->findBy(
            [
                'user'   => $requestingAdmin,
                'header' => 'mautic.lead.export.being.prepared.header',
            ]
        );
        Assert::assertCount(1, $requesterNotifications);

        $adminNotifications = $this->em->getRepository(Notification::class)->findBy(
            [
                'user'   => $secondaryAdmin,
                'header' => 'mautic.lead.export.admin.notification.header',
            ]
        );
        Assert::assertCount(1, $adminNotifications);
        Assert::assertStringContainsString($requestingAdmin->getName(), $adminNotifications[0]->getMessage());
        Assert::assertStringContainsString($requestingAdmin->getEmail(), $adminNotifications[0]->getMessage());
        Assert::assertStringContainsString('CSV', $adminNotifications[0]->getMessage());
        Assert::assertStringContainsString($requestedAt, $adminNotifications[0]->getMessage());
        Assert::assertStringNotContainsString('http', $adminNotifications[0]->getMessage());

        $requesterAdminNotifications = $this->em->getRepository(Notification::class)->findBy(
            [
                'user'   => $requestingAdmin,
                'header' => 'mautic.lead.export.admin.notification.header',
            ]
        );
        Assert::assertCount(0, $requesterAdminNotifications);

        $nonAdminNotifications = $this->em->getRepository(Notification::class)->findBy(
            [
                'user'   => $user,
                'header' => 'mautic.lead.export.admin.notification.header',
            ]
        );
        Assert::assertCount(0, $nonAdminNotifications);
    }

    public function testContactExportCompletionEmailsAreSentToRequesterAndAdmins(): void
    {
        $this->createContacts();
        $this->setAdminUser();

        $adminRole      = $this->createRole(true, 'Security Admin');
        $secondaryAdmin = $this->createUser($adminRole, 'security-admin', 'security.admin@email.com');
        $secondaryAdmin->setFirstName('Security');
        $secondaryAdmin->setLastName('Admin');
        $thirdAdmin = $this->createUser($adminRole, 'audit-admin', 'audit.admin@email.com');
        $thirdAdmin->setFirstName('Audit');
        $thirdAdmin->setLastName('Admin');

        $userRole = $this->createRole(false, 'Regular User');
        $user     = $this->createUser($userRole, 'regular-user', 'regular.user@email.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');

        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            's/contacts/batchExport',
            ['filetype' => 'csv']
        );
        Assert::assertTrue($this->client->getResponse()->isOk());

        /** @var ContactExportScheduler $contactExportScheduler */
        $contactExportScheduler = $this->checkContactExportScheduler(1)[0];
        $requestingAdmin        = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        /** @var DateHelper $dateHelper */
        $dateHelper      = static::getContainer()->get('mautic.helper.twig.date');
        $requestedAt     = $dateHelper->toFull($contactExportScheduler->getScheduledDateTime());

        $this->testSymfonyCommand(ContactScheduledExportCommand::COMMAND_NAME, ['--ids' => $contactExportScheduler->getId()]);
        $this->checkContactExportScheduler(0);

        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = static::getContainer()->get('mautic.helper.core_parameters');
        $zipFileName          = 'contacts_export_'.$contactExportScheduler->getScheduledDateTime()->format('Y_m_d_H_i_s').'.zip';
        $this->filePaths[]    = $filePath = $coreParametersHelper->get('contact_export_dir').'/'.$zipFileName;
        $downloadLink         = $this->router->generate(
            'mautic_contact_export_download',
            ['fileName' => basename($filePath)],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $messages = self::getMailerMessages();
        Assert::assertCount(2, $messages);

        $requesterEmail = $this->findMailerMessageByRecipient($requestingAdmin->getEmail());
        Assert::assertNotNull($requesterEmail);
        Assert::assertSame('Your contact export is ready', $requesterEmail->getSubject());
        Assert::assertStringContainsString('Hi '.$requestingAdmin->getName().',', $requesterEmail->getHtmlBody());
        Assert::assertStringContainsString('Your contact export is ready.', $requesterEmail->getHtmlBody());
        Assert::assertStringContainsString($downloadLink, $requesterEmail->getHtmlBody());
        Assert::assertStringContainsString($zipFileName, $requesterEmail->getHtmlBody());
        Assert::assertStringContainsString('Regards,', $requesterEmail->getTextBody());

        $adminEmail = $this->findMailerMessageByRecipient($secondaryAdmin->getEmail());
        Assert::assertNotNull($adminEmail);
        Assert::assertSame('Contact export completed', $adminEmail->getSubject());
        Assert::assertStringContainsString('Hi,', $adminEmail->getHtmlBody());
        Assert::assertStringContainsString('Initiated by: '.$requestingAdmin->getName().' &lt;'.$requestingAdmin->getEmail().'&gt;', $adminEmail->getHtmlBody());
        Assert::assertStringContainsString('Requested at: '.$requestedAt, $adminEmail->getHtmlBody());
        Assert::assertStringContainsString('Completed at:', $adminEmail->getHtmlBody());
        Assert::assertStringContainsString('Status: Completed', $adminEmail->getHtmlBody());
        Assert::assertStringContainsString('Export type: CSV', $adminEmail->getHtmlBody());
        Assert::assertStringContainsString('download link is not included', $adminEmail->getHtmlBody());
        Assert::assertStringNotContainsString($downloadLink, $adminEmail->getHtmlBody());
        Assert::assertStringNotContainsString('href=', $adminEmail->getHtmlBody());
        Assert::assertStringNotContainsString($downloadLink, $adminEmail->getTextBody());
        Assert::assertCount(1, $adminEmail->getTo());
        Assert::assertSame($secondaryAdmin->getEmail(), $adminEmail->getTo()[0]->getAddress());
        Assert::assertCount(1, $adminEmail->getCc());
        Assert::assertSame($thirdAdmin->getEmail(), $adminEmail->getCc()[0]->getAddress());

        Assert::assertNull($this->findMailerMessageByRecipient($user->getEmail()));
    }

    private function createContacts(): void
    {
        $contacts = [];

        for ($i = 1; $i <= 2; ++$i) {
            $contact = new Lead();
            $contact
                ->setFirstname('ContactFirst'.$i)
                ->setLastname('ContactLast'.$i)
                ->setEmail('FirstLast'.$i.'@email.com');
            $contacts[] = $contact;
        }

        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $leadModel->saveEntities($contacts);
    }

    /**
     * @return array<mixed>
     */
    private function checkContactExportScheduler(int $count): array
    {
        $repo    = $this->em->getRepository(ContactExportScheduler::class);
        $allRows = $repo->findAll();
        Assert::assertCount($count, $allRows);

        return $allRows;
    }

    public function testAccessContactQuickAddWithPermission(): void
    {
        $this->setAdminUser();
        $this->client->request(Request::METHOD_GET, '/s/contacts/quickAdd');
        $this->assertResponseStatusCodeSame(200, (string) $this->client->getResponse()->getStatusCode());
    }

    private function setAdminUser(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', 'admin');
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }

    public function testAccessContactQuickAddWithNoPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request(Request::METHOD_GET, '/s/contacts/quickAdd');
        $this->assertResponseStatusCodeSame(403, (string) $this->client->getResponse()->getStatusCode());
    }

    public function testAccessContactBatchOwnersNoPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchOwners');
        $this->assertResponseStatusCodeSame(403, (string) $this->client->getResponse()->getStatusCode());
    }

    public function testAccessContactBatchOwnersPermission(): void
    {
        $this->setAdminUser();
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchOwners');
        $this->assertResponseStatusCodeSame(200, (string) $this->client->getResponse()->getStatusCode());
    }

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);

        $this->client->loginUser($user, 'mautic');
        $this->client->setServerParameter('PHP_AUTH_USER', self::USERNAME);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        return $user;
    }

    private function createRole(bool $isAdmin = false, string $name = 'Role'): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role, string $username = self::USERNAME, string $email = 'john.doe@email.com'): User
    {
        $user = new User();
        $user->setFirstName('Jhony');
        $user->setLastName('Doe');
        $user->setUsername($username);
        $user->setEmail($email);
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);
        $user->setIsPublished(true);

        $this->em->persist($user);

        return $user;
    }

    private function findMailerMessageByRecipient(string $email): ?MauticMessage
    {
        foreach (self::getMailerMessages() as $message) {
            if (!$message instanceof MauticMessage) {
                continue;
            }

            foreach ($message->getTo() as $recipient) {
                if ($recipient->getAddress() === $email) {
                    return $message;
                }
            }
        }

        return null;
    }
}
