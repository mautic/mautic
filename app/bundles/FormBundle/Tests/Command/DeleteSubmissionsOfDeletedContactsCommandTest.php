<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Command;

use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Command\DeleteSubmissionsOfDeletedContactsCommand;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Tests\FormTestHelperTrait;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteSubmissionsOfDeletedContactsCommandTest extends MauticMysqlTestCase
{
    use FormTestHelperTrait;

    protected $useCleanupRollback = false;

    /**
     * @var string[]
     */
    private array $uploadDirectories = [];

    protected function beforeTearDown(): void
    {
        (new Filesystem())->remove($this->uploadDirectories);
    }

    public function testSubmissionsOfDeletedContactsAreDeletedInAllForms(): void
    {
        $formA          = $this->createFormViaApi($this->getPayLoad());
        $formB          = $this->createFormViaApi($this->getPayLoad());
        $keptContact    = $this->createContact('kept@example.com');
        $deletedContact = $this->createContact('deleted@example.com');

        $keptSubmissionId = $this->createTrackedSubmission($formA, $keptContact, 'Kept');
        $this->createTrackedSubmission($formA, $deletedContact, 'Deleted A1');
        $this->createTrackedSubmission($formA, $deletedContact, 'Deleted A2');
        $this->createTrackedSubmission($formB, $deletedContact, 'Deleted B');
        $deletedContactId = $deletedContact->getId();
        $this->deleteContactViaApi($deletedContact);

        // Mautic keeps the submissions without a contact and keeps the submitted values, but records the deleted contact.
        $this->assertSame(3, $this->countSubmissionsWithoutContact());
        $this->assertSame(3, $this->countSubmissionsOfRecordedContact($deletedContactId));
        $this->assertSame(['Kept', 'Deleted A1', 'Deleted A2'], $this->getResultsNames($formA));
        $this->assertSame(['Deleted B'], $this->getResultsNames($formB));
        $this->assertSame(3, $this->getSubmissionCount($formA));
        $this->assertSame(1, $this->getSubmissionCount($formB));

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 3 form submissions of deleted contacts, including their form results records.', $commandTester->getDisplay());
        $this->assertSame(1, $this->getSubmissionCount($formA));
        $this->assertSame(0, $this->getSubmissionCount($formB));
        $this->assertSame(0, $this->countSubmissionsWithoutContact());
        $this->assertSame(['Kept'], $this->getResultsNames($formA));
        $this->assertSame([], $this->getResultsNames($formB));
        $this->assertInstanceOf(Submission::class, self::getContainer()->get(SubmissionRepository::class)->find($keptSubmissionId));
    }

    public function testDeletedSubmissionsNoLongerCountTowardTheSubmissionLimit(): void
    {
        $form           = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        $formEntity = $this->em->find(Form::class, $form['id']);
        $this->assertInstanceOf(Form::class, $formEntity);
        $formEntity->setSubmissionLimit(1);
        $this->em->persist($formEntity);
        $this->em->flush();

        $this->createTrackedSubmission($form, $deletedContact, 'Deleted');
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $formEntity = $this->em->find(Form::class, $form['id']);
        $this->assertInstanceOf(Form::class, $formEntity);
        $this->assertTrue($formEntity->isSubmissionLimitReached());

        $this->em->clear();
        $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->em->clear();
        $formEntity = $this->em->find(Form::class, $form['id']);
        $this->assertInstanceOf(Form::class, $formEntity);
        $this->assertSame(0, $formEntity->getSubmissionCount());
        $this->assertFalse($formEntity->isSubmissionLimitReached());
    }

    public function testDryRunCountsSubmissionsForEachFormAndDeletesNothing(): void
    {
        $formA          = $this->createFormViaApi($this->getPayLoad());
        $formB          = $this->createFormViaApi($this->getPayLoad());
        $formWithoutAny = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        $this->createTrackedSubmission($formA, $deletedContact, 'Deleted A1');
        $this->createTrackedSubmission($formA, $deletedContact, 'Deleted A2');
        $this->createTrackedSubmission($formB, $deletedContact, 'Deleted B');
        $this->createTrackedSubmission($formWithoutAny, $this->createContact('kept@example.com'), 'Kept');
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--dry-run' => true]);

        $display = $commandTester->getDisplay();
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString("Form {$formA['id']} (Submission test form): 2 form submissions of deleted contacts", $display);
        $this->assertStringContainsString("Form {$formB['id']} (Submission test form): 1 form submissions of deleted contacts", $display);
        $this->assertStringNotContainsString("Form {$formWithoutAny['id']} ", $display);
        $this->assertStringContainsString('Found 3 form submissions of deleted contacts. Nothing was deleted (dry run).', $display);
        $this->assertSame(3, $this->countSubmissionsWithoutContact());
        $this->assertCount(2, $this->getResultsNames($formA));
    }

    public function testFormIdOptionOnlyDeletesSubmissionsOfThisForm(): void
    {
        $formA          = $this->createFormViaApi($this->getPayLoad());
        $formB          = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        $this->createTrackedSubmission($formA, $deletedContact, 'Deleted A');
        $this->createTrackedSubmission($formB, $deletedContact, 'Deleted B');
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--form-id' => (string) $formA['id']]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame([], $this->getResultsNames($formA));
        $this->assertSame(['Deleted B'], $this->getResultsNames($formB));
    }

    public function testExcludedFormsKeepTheirSubmissions(): void
    {
        $formA          = $this->createFormViaApi($this->getPayLoad());
        $formB          = $this->createFormViaApi($this->getPayLoad());
        $formC          = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        $this->createTrackedSubmission($formA, $deletedContact, 'Deleted A');
        $this->createTrackedSubmission($formB, $deletedContact, 'Deleted B');
        $this->createTrackedSubmission($formC, $deletedContact, 'Deleted C');
        $this->deleteContactViaApi($deletedContact);

        $options = ['--exclude' => [(string) $formA['id'], (string) $formC['id']]];

        $this->em->clear();
        $dryRunTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, $options + ['--dry-run' => true]);

        $dryRunDisplay = $dryRunTester->getDisplay();
        $this->assertStringNotContainsString("Form {$formA['id']} ", $dryRunDisplay);
        $this->assertStringContainsString("Form {$formB['id']} (Submission test form): 1 form submissions of deleted contacts", $dryRunDisplay);
        $this->assertStringNotContainsString("Form {$formC['id']} ", $dryRunDisplay);
        $this->assertStringContainsString('Found 1 form submissions of deleted contacts.', $dryRunDisplay);

        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, $options);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(['Deleted A'], $this->getResultsNames($formA));
        $this->assertSame([], $this->getResultsNames($formB));
        $this->assertSame(['Deleted C'], $this->getResultsNames($formC));
    }

    public function testSubmissionsThatNeverHadAContactAreNotDeleted(): void
    {
        $form           = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        // Kiosk mode without mapped fields, Do Not Track or Global Privacy Control: the submission never had a contact.
        $this->createSubmission($form, null, 'Untracked');
        $this->createTrackedSubmission($form, $deletedContact, 'Deleted');
        $this->deleteContactViaApi($deletedContact);

        $this->assertSame(2, $this->countSubmissionsWithoutContact());

        $this->em->clear();
        $dryRunTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--dry-run' => true]);
        $this->assertStringContainsString('Found 1 form submissions of deleted contacts.', $dryRunTester->getDisplay());

        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(1, $this->countSubmissionsWithoutContact());
        $this->assertSame(['Untracked'], $this->getResultsNames($form));
    }

    public function testAnonymousSubmissionIsKeptWhenTheVisitorOfItsDeviceIsDeleted(): void
    {
        $form    = $this->createFormViaApi($this->getPayLoad());
        $visitor = $this->createContact('visitor@example.com');

        // With the Blocked-Tracking cookie, the submission gets no contact, but the tracking ID of the visitor's device.
        $this->createSubmission($form, null, 'Anonymous', $this->createDevice($visitor)->getTrackingId());
        $this->createTrackedSubmission($form, $visitor, 'Visitor');

        // Deleting the visitor also deletes the device of the visitor.
        $this->deleteContactViaApi($visitor);
        $this->assertSame(2, $this->countSubmissionsWithoutContact());

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(['Anonymous'], $this->getResultsNames($form));
    }

    public function testSubmissionWithoutTrackingIdIsDeletedWhenItsContactIsDeleted(): void
    {
        $form    = $this->createFormViaApi($this->getPayLoad());
        $contact = $this->createContact('kiosk@example.com');

        // Kiosk mode with a mapped email field: the submission has a contact, but no tracking ID.
        $this->createSubmission($form, $contact, 'Kiosk');
        $this->deleteContactViaApi($contact);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame([], $this->getResultsNames($form));
    }

    public function testSubmissionsOfContactsDeletedWithoutTheContactModelAreKept(): void
    {
        $form    = $this->createFormViaApi($this->getPayLoad());
        $contact = $this->createContact('historical@example.com');
        $this->createTrackedSubmission($form, $contact, 'Historical');

        // Like a contact that was deleted before Mautic recorded deleted contacts, or by mautic:maintenance:cleanup.
        $this->connection->executeStatement('DELETE FROM '.MAUTIC_TABLE_PREFIX.'leads WHERE id = ?', [$contact->getId()]);
        $this->assertSame(1, $this->countSubmissionsWithoutContact());

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 0 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(['Historical'], $this->getResultsNames($form));
    }

    public function testSubmissionsOfContactsDeletedByTheGdprCleanupAreDeleted(): void
    {
        $form    = $this->createFormViaApi($this->getPayLoad());
        $contact = $this->createInactiveContact('-4 years', '-4 years');
        $this->createTrackedSubmission($form, $contact, 'Inactive');
        $contactId = $contact->getId();

        $this->testSymfonyCommand('mautic:maintenance:cleanup', ['--gdpr' => 1, '--no-interaction' => true]);

        $this->assertSame(1, $this->countSubmissionsWithoutContact());
        $this->assertSame(1, $this->countSubmissionsOfRecordedContact($contactId));

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame([], $this->getResultsNames($form));
    }

    public function testSubmissionsOfAnonymousContactsDeletedByTheRegularCleanupAreKept(): void
    {
        $form    = $this->createFormViaApi($this->getPayLoad());
        $visitor = $this->createInactiveContact('-1 year');
        $this->createTrackedSubmission($form, $visitor, 'Anonymous answer');

        $this->testSymfonyCommand('mautic:maintenance:cleanup', ['--days-old' => 180, '--no-interaction' => true]);

        $this->assertSame(1, $this->countSubmissionsWithoutContact());

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 0 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(['Anonymous answer'], $this->getResultsNames($form));
    }

    public function testSubmissionsOfAMergedContactAreNotDeleted(): void
    {
        $form   = $this->createFormViaApi($this->getPayLoad());
        $winner = $this->createContact('winner@example.com');
        $loser  = $this->createContact('loser@example.com');
        $this->createTrackedSubmission($form, $winner, 'Winner');
        $this->createTrackedSubmission($form, $loser, 'Loser');

        // The merge moves the submissions of the loser to the winner and then deletes the loser.
        self::getContainer()->get(ContactMerger::class)->merge($winner, $loser);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 0 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(['Winner', 'Loser'], $this->getResultsNames($form));
        $this->assertSame(0, $this->countSubmissionsWithoutContact());
    }

    public function testUploadedFilesOfDeletedContactsAreDeleted(): void
    {
        $form           = $this->createFormViaApi($this->getPayLoad(['fields' => $this->getFieldsWithFileField()]));
        $keptContact    = $this->createContact('kept@example.com');
        $deletedContact = $this->createContact('deleted@example.com');
        $keptFile       = $this->createUploadedFile($form, 'document', 'kept.pdf');
        $deletedFile    = $this->createUploadedFile($form, 'document', 'deleted.pdf');

        $this->createTrackedSubmission($form, $keptContact, 'Kept', ['document' => 'kept.pdf']);
        $this->createTrackedSubmission($form, $deletedContact, 'Deleted', ['document' => 'deleted.pdf']);
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--dry-run' => true]);
        $this->assertFileExists($deletedFile);

        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertFileDoesNotExist($deletedFile);
        $this->assertFileExists($keptFile);
        $this->assertSame(['Kept'], $this->getResultsNames($form));
    }

    public function testSubmissionIsKeptWhenItsUploadedFileCannotBeDeleted(): void
    {
        $form           = $this->createFormViaApi($this->getPayLoad(['fields' => $this->getFieldsWithFileField()]));
        $deletedContact = $this->createContact('deleted@example.com');
        $file           = $this->createUploadedFile($form, 'document', 'locked.pdf');
        $this->createTrackedSubmission($form, $deletedContact, 'Locked', ['document' => 'locked.pdf']);
        $this->deleteContactViaApi($deletedContact);

        // A file uploader that cannot delete the file, for example because of missing permissions.
        $command = new DeleteSubmissionsOfDeletedContactsCommand(
            self::getContainer()->get(FormRepository::class),
            self::getContainer()->get(SubmissionRepository::class),
            self::getContainer()->get(FormUploader::class),
            $this->createStub(FileUploader::class),
            self::getContainer()->get(TranslatorInterface::class),
            new NullLogger()
        );

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, [], $command);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('1 form submissions were kept, because their uploaded files could not be deleted.', $display);
        $this->assertStringContainsString('Deleted 0 form submissions of deleted contacts', $display);
        $this->assertFileExists($file);
        $this->assertSame(['Locked'], $this->getResultsNames($form));
    }

    public function testAllBatchesAreDeleted(): void
    {
        $form           = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        for ($i = 1; $i <= 5; ++$i) {
            $this->createTrackedSubmission($form, $deletedContact, 'Deleted '.$i);
        }
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--batch-limit' => '2']);

        $this->assertStringContainsString('Deleted 5 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(0, $this->countSubmissionsWithoutContact());
        $this->assertSame([], $this->getResultsNames($form));
        $this->assertSame(0, $this->getSubmissionCount($form));
    }

    public function testFormIdThatDoesNotExistFails(): void
    {
        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--form-id' => '999999']);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('The form with ID 999999 does not exist.', $commandTester->getDisplay());
    }

    /**
     * @param array<string, string|string[]> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsAreRejected(array $options, string $expectedMessage): void
    {
        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->em->clear();
        $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, $options);
    }

    /**
     * @return iterable<string, array{array<string, string|string[]>, string}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'form ID is not a number' => [['--form-id' => 'abc'], 'The "--form-id" option must be a positive integer, "abc" given.'];
        yield 'batch limit is zero' => [['--batch-limit' => '0'], 'The "--batch-limit" option must be a positive integer, "0" given.'];
        yield 'excluded form ID is not a number' => [['--exclude' => ['3', 'abc']], 'The "--exclude" option must be a positive integer, "abc" given.'];
        yield 'form ID and excluded form ID together' => [['--form-id' => '3', '--exclude' => ['7']], 'The "--form-id" and "--exclude" options cannot be used together.'];
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    private function createInactiveContact(string $lastActive, ?string $dateIdentified = null): Lead
    {
        $contact = new Lead();
        $contact->setLastActive(new \DateTime($lastActive));

        if (null !== $dateIdentified) {
            $contact->setEmail('inactive@example.com');
            $contact->setDateIdentified(new \DateTime($dateIdentified));
        }

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /**
     * Creates a submission with the tracking ID of a new device of the contact, as for a tracked submitter.
     *
     * @param array<string, mixed>  $form
     * @param array<string, string> $results
     */
    private function createTrackedSubmission(array $form, Lead $contact, string $name, array $results = []): int
    {
        return $this->createSubmission($form, $contact, $name, $this->createDevice($contact)->getTrackingId(), $results);
    }

    /**
     * Creates a submission and its record in the form results table.
     *
     * @param array<string, mixed>  $form
     * @param array<string, string> $results
     */
    private function createSubmission(array $form, ?Lead $contact, string $name, ?string $trackingId = null, array $results = []): int
    {
        $submission = new Submission();
        $submission->setForm($this->em->getReference(Form::class, $form['id']));
        $submission->setLead($contact);
        $submission->setTrackingId($trackingId);
        $submission->setDateSubmitted(new \DateTime());
        $submission->setReferer('https://example.com/form');
        $submission->setResults(['name' => $name] + $results);
        self::getContainer()->get(SubmissionRepository::class)->saveEntity($submission);

        return $submission->getId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFieldsWithFileField(): array
    {
        return [
            ['label' => 'Name', 'type' => 'text', 'alias' => 'name'],
            ['label' => 'Document', 'type' => 'file', 'alias' => 'document', 'properties' => ['allowed_file_size' => 1, 'allowed_file_extensions' => ['pdf']]],
            ['label' => 'Submit', 'type' => 'button'],
        ];
    }

    /**
     * @param array<string, mixed> $form
     */
    private function createUploadedFile(array $form, string $fieldAlias, string $fileName): string
    {
        $fieldIds = array_column($form['fields'], 'id', 'alias');
        $field    = $this->em->find(Field::class, $fieldIds[$fieldAlias]);
        $this->assertInstanceOf(Field::class, $field);

        $filePath = self::getContainer()->get(FormUploader::class)->getCompleteFilePath($field, $fileName);
        (new Filesystem())->dumpFile($filePath, 'Personal data');
        $this->uploadDirectories[] = dirname($filePath, 2);

        return $filePath;
    }

    private function createDevice(Lead $contact): LeadDevice
    {
        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId(bin2hex(random_bytes(12)));
        $device->setDateAdded(new \DateTime());
        $this->em->persist($device);
        $this->em->flush();

        return $device;
    }

    private function deleteContactViaApi(Lead $contact): void
    {
        $this->client->request(Request::METHOD_DELETE, "/api/contacts/{$contact->getId()}/delete");
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }

    /**
     * @param array<string, mixed> $form
     */
    private function getSubmissionCount(array $form): int
    {
        return (int) $this->connection->fetchOne('SELECT submission_count FROM '.MAUTIC_TABLE_PREFIX.'forms WHERE id = ?', [$form['id']]);
    }

    private function countSubmissionsOfRecordedContact(int $contactId): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'form_submissions WHERE deleted_lead_id = ?', [$contactId]);
    }

    private function countSubmissionsWithoutContact(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'form_submissions WHERE lead_id IS NULL');
    }

    /**
     * @param array<string, mixed> $form
     *
     * @return string[]
     */
    private function getResultsNames(array $form): array
    {
        $table = self::getContainer()->get(SubmissionRepository::class)->getResultsTableName($form['id'], $form['alias']);

        return $this->connection->fetchFirstColumn("SELECT name FROM {$table} ORDER BY submission_id");
    }
}
