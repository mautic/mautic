<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
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

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('Jhony');
        $user->setLastName('Doe');
        $user->setUsername(self::USERNAME);
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
