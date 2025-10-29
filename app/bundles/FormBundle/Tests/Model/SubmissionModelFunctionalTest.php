<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SubmissionModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private SubmissionModel $submissionModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->submissionModel = static::getContainer()->get('mautic.form.model.submission');
    }

    public function testSaveSubmissionChangeCompanyField(): void
    {
        [$formId, $formAlias] = $this->createFormWithCompanies();

        $this->submitFormWithCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Doe', 'Acquia', 'Keplerova');

        // Check the address.
        $companyRepository = $this->em->getRepository(Company::class);
        $companiesOriginal = $companyRepository->findBy(['address1' => 'Keplerova']);
        Assert::assertCount(1, $companiesOriginal);

        // Create contact with the same company but different address.
        $this->submitFormWithCompanies($formId, $formAlias, 'test2@acquia.cz', 'Luk', 'Syk', 'Acquia', 'Krejpskeho');

        // Check that the address is changed.
        $companiesOld = $companyRepository->findBy(['address1' => 'Keplerova']);
        Assert::assertCount(0, $companiesOld);
        $companiesNew = $companyRepository->findBy(['address1' => 'Krejpskeho']);
        Assert::assertCount(1, $companiesNew);
    }

    public function testSaveSubmissionChangeContactField(): void
    {
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        $this->submitFormWithoutCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Doe Smith');

        // Check the contact.
        $contactRepository = $this->em->getRepository(Lead::class);
        $contactsOriginal  = $contactRepository->findBy(['lastname' => 'Doe Smith']);
        Assert::assertCount(1, $contactsOriginal);

        // Create contact with the same email but different lastname.
        $this->submitFormWithoutCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Sykora');

        // Check that the address is changed.
        $contactsOld = $contactRepository->findBy(['lastname' => 'Doe Smith']);
        Assert::assertCount(0, $contactsOld);
        $contactsNew = $contactRepository->findBy(['lastname' => 'Sykora']);
        Assert::assertCount(1, $contactsNew);
    }

    public function testGetSubmissionsByFormReturnsCorrectData(): void
    {
        // Create form and submit
        [$formId, $formAlias] = $this->createFormWithoutCompanies();
        $email                = 'test.submissions@example.com';
        $firstname            = 'John';
        $lastname             = 'Doe';

        $this->submitFormWithoutCompanies($formId, $formAlias, $email, $firstname, $lastname);

        // Get the submission
        $submissionRepository = $this->em->getRepository(Submission::class);
        $submissions          = $submissionRepository->findBy(['form' => $formId]);
        Assert::assertNotEmpty($submissions, 'Should have at least one submission');

        $submission = $submissions[0];

        // Test getSubmissionsByForm
        $results = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);

        Assert::assertIsArray($results);
        Assert::assertNotEmpty($results, 'Should return form submission results');

        // Check that the results contain the submitted data
        $resultRow = $results[0];
        Assert::assertArrayHasKey('submission_id', $resultRow);
        Assert::assertEquals($submission->getId(), $resultRow['submission_id']);

        // Verify submitted values are in the results
        Assert::assertEquals($email, $resultRow['email']);
        Assert::assertEquals($firstname, $resultRow['firstname']);
        Assert::assertEquals($lastname, $resultRow['lastname']);
    }

    public function testGetSubmissionsByFormWithNoResults(): void
    {
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        // Create a submission that won't be found (don't actually submit the form)
        $form       = $this->em->getRepository(Form::class)->find($formId);
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setDateSubmitted(new \DateTime());
        $submission->setResults([]);

        // Don't persist/flush - we're just using it to call the method
        // with an ID that doesn't exist in the results table

        // Mock a submission with non-existent ID
        $mockSubmission = $this->createMock(Submission::class);
        $mockSubmission->method('getId')->willReturn(999999);

        $results = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $mockSubmission);

        Assert::assertIsArray($results);
        Assert::assertEmpty($results, 'Should return empty array for non-existent submission');
    }

    public function testUpdateSubmissionAnonymizeByLeadWithEmptyDataForm(): void
    {
        // Create form and submit
        [$formId, $formAlias] = $this->createFormWithoutCompanies();
        $email                = 'test.anonymize1@example.com';
        $firstname            = 'Jane';
        $lastname             = 'Smith';

        $this->submitFormWithoutCompanies($formId, $formAlias, $email, $firstname, $lastname);

        // Get the submission
        $submissionRepository = $this->em->getRepository(Submission::class);
        $submissions          = $submissionRepository->findBy(['form' => $formId]);
        $submission           = $submissions[0];

        // Get original data
        $originalResults = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);
        Assert::assertNotEmpty($originalResults);

        // Update with empty dataForm (should anonymize all text fields)
        $this->submissionModel->updateSubmissionAnonymizeByLead($formId, $formAlias, $submission, []);

        // Get updated data
        $updatedResults = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);
        Assert::assertNotEmpty($updatedResults);

        $originalRow = $originalResults[0];
        $updatedRow  = $updatedResults[0];

        // Verify submission_id is unchanged (integer field)
        Assert::assertEquals($originalRow['submission_id'], $updatedRow['submission_id']);

        // Verify text fields are anonymized (changed)
        Assert::assertNotEquals($originalRow['email'], $updatedRow['email'], 'Email should be anonymized');
        Assert::assertNotEquals($originalRow['firstname'], $updatedRow['firstname'], 'Firstname should be anonymized');
        Assert::assertNotEquals($originalRow['lastname'], $updatedRow['lastname'], 'Lastname should be anonymized');
    }

    public function testUpdateSubmissionAnonymizeByLeadWithProvidedDataForm(): void
    {
        // Create form and submit
        [$formId, $formAlias] = $this->createFormWithoutCompanies();
        $email                = 'test.anonymize2@example.com';
        $firstname            = 'Bob';
        $lastname             = 'Johnson';

        $this->submitFormWithoutCompanies($formId, $formAlias, $email, $firstname, $lastname);

        // Get the submission
        $submissionRepository = $this->em->getRepository(Submission::class);
        $submissions          = $submissionRepository->findBy(['form' => $formId]);
        $submission           = $submissions[0];

        // Prepare custom anonymized data
        $customAnonymizedEmail = 'anonymized_email_'.uniqid();
        $dataForm              = [
            $email => $customAnonymizedEmail,
        ];

        // Update with provided dataForm
        $this->submissionModel->updateSubmissionAnonymizeByLead($formId, $formAlias, $submission, $dataForm);

        // Get updated data
        $updatedResults = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);
        $updatedRow     = $updatedResults[0];

        // Verify email was replaced with our custom value
        Assert::assertEquals($customAnonymizedEmail, $updatedRow['email'], 'Email should use provided anonymized value');

        // Verify other fields are still anonymized (not in dataForm)
        Assert::assertNotEquals($firstname, $updatedRow['firstname'], 'Firstname should be auto-anonymized');
        Assert::assertNotEquals($lastname, $updatedRow['lastname'], 'Lastname should be auto-anonymized');
    }

    public function testUpdateSubmissionAnonymizeByLeadDoesNotUpdateIntegerFields(): void
    {
        // Create form and submit
        [$formId, $formAlias] = $this->createFormWithoutCompanies();
        $email                = 'test.anonymize3@example.com';
        $firstname            = 'Alice';
        $lastname             = 'Williams';

        $this->submitFormWithoutCompanies($formId, $formAlias, $email, $firstname, $lastname);

        // Get the submission
        $submissionRepository = $this->em->getRepository(Submission::class);
        $submissions          = $submissionRepository->findBy(['form' => $formId]);
        $submission           = $submissions[0];

        $originalResults      = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);
        $originalSubmissionId = $originalResults[0]['submission_id'];
        $originalFormId       = $originalResults[0]['form_id'] ?? null;

        // Update submission
        $this->submissionModel->updateSubmissionAnonymizeByLead($formId, $formAlias, $submission, []);

        // Get updated data
        $updatedResults = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);
        $updatedRow     = $updatedResults[0];

        // Verify integer fields remain unchanged
        Assert::assertEquals($originalSubmissionId, $updatedRow['submission_id'], 'Submission ID should not be anonymized');
        if (null !== $originalFormId) {
            Assert::assertEquals($originalFormId, $updatedRow['form_id'], 'Form ID should not be anonymized');
        }
    }

    public function testUpdateSubmissionAnonymizeByLeadWithMultipleSubmissions(): void
    {
        // Create form
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        // Submit form multiple times
        $this->submitFormWithoutCompanies($formId, $formAlias, 'user1@example.com', 'User', 'One');
        $this->submitFormWithoutCompanies($formId, $formAlias, 'user2@example.com', 'User', 'Two');

        // Get submissions
        $submissionRepository = $this->em->getRepository(Submission::class);
        $submissions          = $submissionRepository->findBy(['form' => $formId]);
        Assert::assertCount(2, $submissions, 'Should have 2 submissions');

        // Anonymize first submission only
        $firstSubmission  = $submissions[0];
        $secondSubmission = $submissions[1];

        $this->submissionModel->updateSubmissionAnonymizeByLead($formId, $formAlias, $firstSubmission, []);

        // Verify first submission is anonymized
        $firstResults = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $firstSubmission);
        Assert::assertNotEquals('user1@example.com', $firstResults[0]['email'] ?? null, 'First submission email should be anonymized');

        // Verify second submission is NOT anonymized
        $secondResults = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $secondSubmission);
        Assert::assertTrue(
            'user2@example.com' === $secondResults[0]['email'],
            'Second submission should not be affected'
        );
    }

    public function testUpdateSubmissionAnonymizeByLeadHandlesEmptyResults(): void
    {
        // Create form but don't submit anything
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        // Create a mock submission
        $mockSubmission = $this->createMock(Submission::class);
        $mockSubmission->method('getId')->willReturn(999999);

        // Should not throw exception with non-existent submission
        $this->submissionModel->updateSubmissionAnonymizeByLead($formId, $formAlias, $mockSubmission, []);

        // If we get here without exception, test passes
        $this->assertTrue(true);
    }

    public function testGetSubmissionsByFormReturnsAllColumns(): void
    {
        [$formId, $formAlias] = $this->createFormWithoutCompanies();
        $this->submitFormWithoutCompanies($formId, $formAlias, 'test.columns@example.com', 'Column', 'Test');

        $submissionRepository = $this->em->getRepository(Submission::class);
        $submissions          = $submissionRepository->findBy(['form' => $formId]);
        $submission           = $submissions[0];

        $results = $this->submissionModel->getSubmissionsByForm($formId, $formAlias, $submission);

        Assert::assertNotEmpty($results);
        $resultRow = $results[0];

        // Verify expected columns exist
        Assert::assertArrayHasKey('submission_id', $resultRow);
        Assert::assertArrayHasKey('form_id', $resultRow);
        Assert::assertArrayHasKey('email', $resultRow);
        Assert::assertArrayHasKey('firstname', $resultRow);
        Assert::assertArrayHasKey('lastname', $resultRow);
    }

    /**
     * @return mixed[]
     */
    private function createFormWithCompanies(): array
    {
        $payload = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
                [
                    'label'        => 'Company',
                    'type'         => 'text',
                    'alias'        => 'companyname',
                    'mappedObject' => 'company',
                    'mappedField'  => 'companyname',
                ],
                [
                    'label'        => 'Company Address',
                    'type'         => 'text',
                    'alias'        => 'companyaddress1',
                    'mappedObject' => 'company',
                    'mappedField'  => 'companyaddress1',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        return $this->createForm($payload);
    }

    /**
     * @return mixed[]
     */
    private function createFormWithoutCompanies(): array
    {
        $payload = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
            ],
        ];

        return $this->createForm($payload);
    }

    /**
     * @param mixed[] $payload
     *
     * @return array{int,string}
     */
    private function createForm(array $payload): array
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];
        $formAlias      = $response['form']['alias'];
        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        return [$formId, $formAlias];
    }

    private function submitFormWithCompanies(int $formId, string $formAlias, string $email, string $firstname, string $lastname, string $company, string $companyAddress): void
    {
        $values = [
            'mauticform[email]'           => $email,
            'mauticform[firstname]'       => $firstname,
            'mauticform[lastname]'        => $lastname,
            'mauticform[companyname]'     => $company,
            'mauticform[companyaddress1]' => $companyAddress,
        ];
        $this->submitForm($formId, $formAlias, $values);
    }

    private function submitFormWithoutCompanies(int $formId, string $formAlias, string $email, string $firstname, string $lastname): void
    {
        $values = [
            'mauticform[email]'           => $email,
            'mauticform[firstname]'       => $firstname,
            'mauticform[lastname]'        => $lastname,
        ];
        $this->submitForm($formId, $formAlias, $values);
    }

    /**
     * @param array<string,string> $values
     */
    private function submitForm(int $formId, string $formAlias, array $values): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues($values);
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
    }
}
