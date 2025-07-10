<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\ImportCommand;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\ImportRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class ImportControllerTest extends MauticMysqlTestCase
{
    public function testImportWithoutFile(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $form    = $crawler->selectButton('Upload')->form();
        $crawler = $this->client->submit($form);

        Assert::assertStringContainsString('Please select a CSV file to upload', $crawler->html(), $crawler->html());
    }

    /**
     * Setting the phone field as required to test the validation.
     * Phone is not part of the csv fixture so it won't be auto-mapped.
     */
    public function testImportMappingRequiredFieldValidation(): void
    {
        $this->setPhoneFieldIsRequired(true);

        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadForm = $crawler->selectButton('Upload')->form();
        $file       = new UploadedFile(__DIR__.'/../Fixtures/contacts.csv', 'contacs.csv', 'text/csv');

        $uploadForm['lead_import[file]']->setValue((string) $file);

        $crawler     = $this->client->submit($uploadForm);
        $mappingForm = $crawler->selectButton('Import')->form();
        $crawler     = $this->client->submit($mappingForm);

        Assert::assertStringContainsString('Some required fields are missing. You must map the field "Phone."', $crawler->html());
    }

    /**
     *  @dataProvider validateDataProvider
     */
    public function testImportMappingAndImport(string $skipIfExist, string $expectedName): void
    {
        $this->createLead('john@doe.email', 'Johny');
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadForm = $crawler->selectButton('Upload')->form();
        $file       = new UploadedFile(__DIR__.'/../Fixtures/contacts.csv', 'contacs.csv', 'text/csv');

        $uploadForm['lead_import[file]']->setValue((string) $file);

        $crawler     = $this->client->submit($uploadForm);
        $mappingForm = $crawler->selectButton('Import')->form();
        $mappingForm['lead_field_import[skip_if_exists]']->setValue($skipIfExist);
        $crawler     = $this->client->submit($mappingForm);

        Assert::assertStringContainsString('Import process was successfully created. You will be notified when finished.', $crawler->html());

        /** @var ImportRepository $importRepository */
        $importRepository = $this->em->getRepository(Import::class);

        /** @var Import $importEntity */
        $importEntity = $importRepository->findOneBy(['originalFile' => 'contacts.csv']);

        $fields = ['email' => 'email', 'firstname' => 'firstname', 'lastname' => 'lastname'];

        Assert::assertNotNull($importEntity);
        Assert::assertSame(2, $importEntity->getLineCount());
        Assert::assertSame(Import::QUEUED, $importEntity->getStatus());
        Assert::assertSame('lead', $importEntity->getObject());
        Assert::assertSame($fields, $importEntity->getProperties()['fields']);
        Assert::assertSame(array_values($fields), $importEntity->getProperties()['headers']);

        $this->testSymfonyCommand(ImportCommand::COMMAND_NAME);

        $this->em->clear();

        /** @var Import $importEntity */
        $importEntity = $importRepository->findOneBy(['originalFile' => 'contacts.csv']);

        Assert::assertNotNull($importEntity);
        Assert::assertSame(2, $importEntity->getLineCount());
        Assert::assertSame(1, $importEntity->getInsertedCount());
        Assert::assertSame(1, $importEntity->getUpdatedCount());
        Assert::assertSame(Import::IMPORTED, $importEntity->getStatus());

        /** @var LeadRepository $importRepository */
        $leadRepository = $this->em->getRepository(Lead::class);

        /** @var Lead[] $contacts */
        $contacts = $leadRepository->findBy(['email' => ['john@doe.email', 'ferda@mravenec.email']], ['email' => 'desc']);
        Assert::assertSame($expectedName, $contacts[0]->getFirstname());
        Assert::assertCount(2, $contacts);
    }

    public function testContactPermissionsAreFollowedDuringImport(): void
    {
        $filename   = 'import-contact-permissions.csv';
        $permission = [
            'lead:leads'   => ['viewown', 'viewother', 'editown'],
            'lead:imports' => ['view', 'create', 'edit'],
        ];
        $role = $this->createRole(false, $permission);
        $this->createPermission('lead:imports', $role, 1024);
        $this->createPermission('lead:leads', $role, 14);
        $user = $this->createUser($role);

        $this->createLead('existing-other@email.tld', 'Existing-other-before');
        $lead = $this->createLead('existing-owned@email.tld', 'Existing-owned-before');
        $lead->setOwner($user);
        $this->em->persist($lead);
        $this->createCompanyForLead($lead, 'Company One');

        $this->em->flush();
        $this->em->clear();

        // Login newly created non-admin user
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadForm = $crawler->selectButton('Upload')->form();
        $file       = new UploadedFile(dirname(__FILE__).'/../Fixtures/'.$filename, 'contacts.csv', 'text/csv');

        $uploadForm['lead_import[file]']->setValue((string) $file);

        $crawler     = $this->client->submit($uploadForm);
        $mappingForm = $crawler->selectButton('Import')->form();
        $this->selectCompanyMapping($crawler, $mappingForm);
        $crawler = $this->client->submit($mappingForm);

        Assert::assertStringContainsString('Import process was successfully created.', $crawler->html());

        $importRepository = $this->em->getRepository(Import::class);
        \assert($importRepository instanceof ImportRepository);
        $importEntity = $importRepository->findOneBy(['originalFile' => $filename]);

        Assert::assertInstanceOf(Import::class, $importEntity);
        Assert::assertSame($user->getId(), $importEntity->getCreatedBy());
        Assert::assertSame($user->getId(), $importEntity->getModifiedBy());
        Assert::assertSame(Import::QUEUED, $importEntity->getStatus());

        $this->testSymfonyCommand(ImportCommand::COMMAND_NAME);

        $this->em->clear();

        $importEntity = $importRepository->findOneBy(['originalFile' => $filename]);

        Assert::assertInstanceOf(Import::class, $importEntity);
        Assert::assertSame(3, $importEntity->getLineCount(), '3 rows should be processed as the CSV file contains 3 rows.');
        Assert::assertSame(0, $importEntity->getInsertedCount(), 'No row should be inserter as the user does not have permission to create contacts.');
        Assert::assertSame(1, $importEntity->getUpdatedCount(), 'There should be one update as the user has the permission to edit his own contacts.');
        Assert::assertSame(Import::IMPORTED, $importEntity->getStatus());

        $leadRepository = $this->em->getRepository(Lead::class);
        \assert($leadRepository instanceof LeadRepository);

        /** @var Lead[] $contacts */
        $contacts = $leadRepository->findBy([], ['id' => 'asc']);
        Assert::assertCount(2, $contacts, 'There should not be any contact inserted as the user does not have permission to create contacts.');
        Assert::assertSame('Existing-other-before', $contacts[0]->getFirstname(), 'This contact should not be updated as the user does not have permission to edit others.');
        Assert::assertSame('Existing-owned-after', $contacts[1]->getFirstname(), 'This contact should be updated as the user has permission to edit own.');

        $eventLogRepository = $this->em->getRepository(LeadEventLog::class);
        \assert($eventLogRepository instanceof LeadEventLogRepository);

        /** @var LeadEventLog[] $logs */
        $logs = $eventLogRepository->findBy(['bundle' => 'lead', 'object' => 'import'], ['id' => 'asc']);
        Assert::assertCount(3, $logs, 'There should be 3 logs connected with the import.');
        $this->assertInsufficientPermissionError($logs[0], $user);
        $this->assertInsufficientPermissionError($logs[1], $user);
        Assert::assertSame('updated', $logs[2]->getAction());
        Assert::assertArrayNotHasKey('error', $logs[2]->getProperties());
    }

    public function testImportFailedWithImportFailedException(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadForm = $crawler->selectButton('Upload')->form();
        $file       = new UploadedFile(
            dirname(__FILE__).'/../Fixtures/contacts.csv',
            'contacs.csv',
            'itext/csv'
        );

        $uploadForm['lead_import[file]']->setValue((string) $file);

        $crawler     = $this->client->submit($uploadForm);
        $mappingForm = $crawler->selectButton('Import')->form();
        $crawler     = $this->client->submit($mappingForm);

        Assert::assertStringContainsString(
            'Import process was successfully created. You will be notified when finished.',
            $crawler->html(),
            $crawler->html()
        );

        /** @var ImportRepository $importRepository */
        $importRepository = $this->em->getRepository(Import::class);

        /** @var Import $importEntity */
        $importEntity = $importRepository->findOneBy(['originalFile' => 'contacts.csv']);

        $importEntity->setStatus(4);
        $importRepository->saveEntity($importEntity);

        $applicationTester = $this->testSymfonyCommand(ImportCommand::COMMAND_NAME, ['--id' => $importEntity->getId()]);

        $this->em->clear();

        $expectedString = 'Reason: Import could not be triggered since it is not queued nor delayed';

        Assert::assertStringContainsString($expectedString, $applicationTester->getDisplay());
    }

    private function setPhoneFieldIsRequired(bool $required): void
    {
        /** @var LeadFieldRepository $fieldRepository */
        $fieldRepository = $this->em->getRepository(LeadField::class);

        /** @var LeadField $phoneField */
        $phoneField = $fieldRepository->findOneBy(['alias' => 'phone']);

        $phoneField->setIsRequired($required);
        $fieldRepository->saveEntity($phoneField);
    }

    private function createLead(string $email = null, string $firstName = ''): Lead
    {
        $lead = new Lead();
        if (!empty($email)) {
            $lead->setEmail($email);
        }
        $lead->setFirstname($firstName);
        $this->em->persist($lead);

        return $lead;
    }

    private function createCompanyForLead(Lead $lead, string $companyName): void
    {
        $company = new Company();
        $company->setName($companyName);
        $this->em->persist($company);

        // add company to lead
        $lead->setCompany($companyName);
        $this->em->persist($lead);

        // set primary company for lead
        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setDateAdded(new \DateTime());
        $companyLead->setPrimary(true);
        $this->em->persist($companyLead);
    }

    /**
     * @param array<mixed> $permission
     */
    private function createRole(bool $isAdmin = false, array $permission = []): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);
        $role->setRawPermissions($permission);
        $this->em->persist($role);

        return $role;
    }

    private function createPermission(string $rawPermission, Role $role, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);
        $this->em->persist($user);

        return $user;
    }

    /**
     * @return mixed[]
     */
    public function validateDataProvider(): iterable
    {
        yield ['0', 'John'];
        yield ['1', 'Johny'];
    }

    private function assertInsufficientPermissionError(LeadEventLog $log, User $user): void
    {
        Assert::assertSame('failed', $log->getAction(), 'The insertion should fail as the user does not have permission to create contacts.');
        Assert::assertSame(sprintf('User \'%s\' has insufficient permissions', $user->getUserIdentifier()), $log->getProperties()['error'], 'There should be an insufficient permission error.');
    }

    private function selectCompanyMapping(Crawler $crawler, Form $mappingForm): void
    {
        $options = $crawler->filter("#lead_field_import_company > optgroup[label='Primary company']")->filter('option');
        $values  = array_filter($options->each(function ($node) {
            if ('Company Name' === $node->text()) {
                return $node->attr('value');
            }
        }));
        $mappingForm['lead_field_import[company]']->setValue(end($values));
    }
}
