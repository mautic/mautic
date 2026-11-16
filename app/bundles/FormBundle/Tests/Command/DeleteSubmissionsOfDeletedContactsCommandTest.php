<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Command\DeleteSubmissionsOfDeletedContactsCommand;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Tests\FormTestHelperTrait;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DeleteSubmissionsOfDeletedContactsCommandTest extends MauticMysqlTestCase
{
    use FormTestHelperTrait;

    protected $useCleanupRollback = false;

    public function testSubmissionsOfDeletedContactsAreDeletedInAllForms(): void
    {
        $formA          = $this->createFormViaApi($this->getPayLoad());
        $formB          = $this->createFormViaApi($this->getPayLoad());
        $keptContact    = $this->createContact('kept@example.com');
        $deletedContact = $this->createContact('deleted@example.com');

        $keptSubmissionId = $this->createSubmission($formA, $keptContact, 'Kept');
        $this->createSubmission($formA, $deletedContact, 'Deleted A1');
        $this->createSubmission($formA, $deletedContact, 'Deleted A2');
        $this->createSubmission($formB, $deletedContact, 'Deleted B');
        $this->deleteContactViaApi($deletedContact);

        // Mautic keeps the submissions without a contact and keeps the submitted values.
        $this->assertSame(3, $this->countSubmissionsWithoutContact());
        $this->assertSame(['Kept', 'Deleted A1', 'Deleted A2'], $this->getResultsNames($formA));
        $this->assertSame(['Deleted B'], $this->getResultsNames($formB));

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 3 form submissions of deleted contacts, including their form results records.', $commandTester->getDisplay());
        $this->assertSame(0, $this->countSubmissionsWithoutContact());
        $this->assertSame(['Kept'], $this->getResultsNames($formA));
        $this->assertSame([], $this->getResultsNames($formB));
        $this->assertInstanceOf(Submission::class, self::getContainer()->get(SubmissionRepository::class)->find($keptSubmissionId));
    }

    public function testDryRunCountsSubmissionsForEachFormAndDeletesNothing(): void
    {
        $formA          = $this->createFormViaApi($this->getPayLoad());
        $formB          = $this->createFormViaApi($this->getPayLoad());
        $formWithoutAny = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        $this->createSubmission($formA, $deletedContact, 'Deleted A1');
        $this->createSubmission($formA, $deletedContact, 'Deleted A2');
        $this->createSubmission($formB, $deletedContact, 'Deleted B');
        $this->createSubmission($formWithoutAny, $this->createContact('kept@example.com'), 'Kept');
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

        $this->createSubmission($formA, $deletedContact, 'Deleted A');
        $this->createSubmission($formB, $deletedContact, 'Deleted B');
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--form-id' => (string) $formA['id']]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Deleted 1 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame([], $this->getResultsNames($formA));
        $this->assertSame(['Deleted B'], $this->getResultsNames($formB));
    }

    public function testAllBatchesAreDeleted(): void
    {
        $form           = $this->createFormViaApi($this->getPayLoad());
        $deletedContact = $this->createContact('deleted@example.com');

        for ($i = 1; $i <= 5; ++$i) {
            $this->createSubmission($form, $deletedContact, 'Deleted '.$i);
        }
        $this->deleteContactViaApi($deletedContact);

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, ['--batch-limit' => '2']);

        $this->assertStringContainsString('Deleted 5 form submissions of deleted contacts', $commandTester->getDisplay());
        $this->assertSame(0, $this->countSubmissionsWithoutContact());
        $this->assertSame([], $this->getResultsNames($form));
    }

    /**
     * @param array<string, string> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsFail(array $options, string $expectedMessage): void
    {
        $this->em->clear();
        $commandTester = $this->testSymfonyCommand(DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME, $options);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString($expectedMessage, $commandTester->getDisplay());
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'form ID that does not exist' => [['--form-id' => '999999'], 'The form with ID 999999 does not exist.'];
        yield 'form ID is zero' => [['--form-id' => '0'], 'The option --form-id must be a positive integer.'];
        yield 'form ID is not a number' => [['--form-id' => 'abc'], 'The option --form-id must be a positive integer.'];
        yield 'batch limit is zero' => [['--batch-limit' => '0'], 'The option --batch-limit must be a positive integer.'];
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /**
     * Creates a submission and its record in the form results table.
     *
     * @param array<string, mixed> $form
     */
    private function createSubmission(array $form, Lead $contact, string $name): int
    {
        $submission = new Submission();
        $submission->setForm($this->em->getReference(Form::class, $form['id']));
        $submission->setLead($contact);
        $submission->setDateSubmitted(new \DateTime());
        $submission->setReferer('https://example.com/form');
        $submission->setResults(['name' => $name]);
        self::getContainer()->get(SubmissionRepository::class)->saveEntity($submission);

        return $submission->getId();
    }

    private function deleteContactViaApi(Lead $contact): void
    {
        $this->client->request(Request::METHOD_DELETE, "/api/contacts/{$contact->getId()}/delete");
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
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
