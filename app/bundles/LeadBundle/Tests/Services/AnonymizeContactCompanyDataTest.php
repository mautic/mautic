<?php

namespace Mautic\LeadBundle\Tests\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Services\AnonymizeContactCompanyData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AnonymizeContactCompanyDataTest extends TestCase
{
    /** @var FieldModel&MockObject */
    private FieldModel $fieldModel;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    /** @var EmailModel&MockObject */
    private EmailModel $emailModel;
    /** @var SubmissionModel&MockObject */
    private SubmissionModel $submissionModel;

    protected function setUp(): void
    {
        $this->fieldModel      = $this->createMock(FieldModel::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->emailModel      = $this->createMock(EmailModel::class);
        $this->submissionModel = $this->createMock(SubmissionModel::class);

        // Use the actual StatRepository class so the mocked return type matches the declared return type
        $statRepo = $this->getMockBuilder(StatRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'saveEntity'])
            ->getMock();

        $statRepo->method('findBy')->willReturn([]);
        $this->emailModel->method('getStatRepository')->willReturn($statRepo);
    }

    public function testSetHashesAnonymizesEmailAndCallsAddUpdatedField(): void
    {
        $alias          = 'email';
        $leadFieldArray = [
            'id'    => 123,
            'type'  => 'email',
            'value' => 'user@example.com',
        ];

        // The requested LeadField passed into setHashes (to read the alias)
        $requestedField = $this->createMock(LeadField::class);
        $requestedField->method('getAlias')->willReturn($alias);

        // Repository returned by FieldModel::getRepository()->getEntity(...)
        $fieldEntity = $this->createMock(LeadField::class);
        $fieldEntity->method('getCharLengthLimit')->willReturn(0);
        // Ensure the repository entity returns the alias so addUpdatedField receives the correct key
        $fieldEntity->method('getAlias')->willReturn($alias);

        // Use the real repository class for correct return type
        $repo = $this->getMockBuilder(LeadFieldRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntity'])
            ->getMock();
        $repo->method('getEntity')->with($leadFieldArray['id'])->willReturn($fieldEntity);
        $this->fieldModel->method('getRepository')->willReturn($repo);

        // Create a Lead mock that will respond to getField() and expect addUpdatedField() to be called
        $lead = $this->getMockBuilder(Lead::class)
            ->onlyMethods(['getField', 'addUpdatedField'])
            ->getMock();

        $lead->method('getField')->with($alias)->willReturn($leadFieldArray);
        $lead->expects($this->once())
            ->method('addUpdatedField')
            ->with($alias, $this->isType('string'));

        $service = new AnonymizeContactCompanyData(
            $this->fieldModel,
            $this->logger,
            $this->emailModel,
            $this->submissionModel
        );

        $result = $service->setHashes([$lead], $requestedField, true);

        // Ensure the returned array contains the same lead instance (method replaces element with returned object)
        $this->assertSame($lead, $result[0]);
    }

    public function testSetLeadsCompaniesFieldNullSetsFieldToNullAndReturnsSameInstance(): void
    {
        $alias = 'custom_field';

        $requestedField = $this->createMock(LeadField::class);
        $requestedField->method('getAlias')->willReturn($alias);

        $leadCompany = $this->getMockBuilder(Lead::class)
            ->onlyMethods(['getField', 'addUpdatedField'])
            ->getMock();

        // getField must return a truthy value for the alias so the method sets it to null
        $leadCompany->method('getField')->with($alias)->willReturn(['id' => 1, 'value' => 'foo']);
        $leadCompany->expects($this->once())
            ->method('addUpdatedField')
            ->with($alias, null);

        $service = new AnonymizeContactCompanyData(
            $this->fieldModel,
            $this->logger,
            $this->emailModel,
            $this->submissionModel
        );

        $result = $service->setLeadsCompaniesFieldNull([$leadCompany], $requestedField);
        $this->assertSame($leadCompany, $result[0]);
    }

    public function testUpdateFormResultsWithPseudonymizeFalse(): void
    {
        $lead       = $this->createLead();
        $submission = $this->createFormSubmission(1, 'test_form');
        $this->mockSubmissionRepoForLead($lead, [$submission]);

        $this->submissionModel->expects($this->never())->method('getSubmissionsByForm');
        $this->submissionModel->expects($this->once())
            ->method('updateSubmissionAnonymizeByLead')
            ->with(1, 'test_form', $submission, []);

        $service = $this->createService();
        $service->updateFormResults(new ArrayCollection([$lead]), false);
    }

    public function testUpdateFormResultsWithPseudonymizeTrueAnonymizesForms(): void
    {
        $lead       = $this->createLead();
        $submission = $this->createFormSubmission(1, 'test_form');
        $this->mockSubmissionRepoForLead($lead, [$submission]);

        $formData = [
            [
                'submission_id' => 123,
                'form_id'       => 1,
                'email'         => 'test@example.com',
                'name'          => 'John Doe',
                'message'       => 'Test message',
            ],
        ];

        $this->submissionModel->expects($this->once())
            ->method('getSubmissionsByForm')
            ->with(1, 'test_form', $submission)
            ->willReturn($formData);

        $this->submissionModel->expects($this->once())
            ->method('updateSubmissionAnonymizeByLead')
            ->with(
                1,
                'test_form',
                $submission,
                $this->callback(function ($valueSubmissionForm) {
                    $this->assertArrayHasKey('test@example.com', $valueSubmissionForm);
                    $this->assertArrayHasKey('John Doe', $valueSubmissionForm);
                    $this->assertArrayHasKey('Test message', $valueSubmissionForm);

                    $this->assertNotEquals('test@example.com', $valueSubmissionForm['test@example.com']);
                    $this->assertNotEquals('John Doe', $valueSubmissionForm['John Doe']);
                    $this->assertNotEquals('Test message', $valueSubmissionForm['Test message']);

                    return true;
                })
            );

        $service = $this->createService();
        $service->updateFormResults(new ArrayCollection([$lead]), true);
    }

    public function testUpdateFormResultsWithMultipleLeadsAndForms(): void
    {
        $lead1 = $this->createMock(Lead::class);
        $lead2 = $this->createMock(Lead::class);
        $leads = new ArrayCollection([$lead1, $lead2]);

        // Setup first form for lead1
        $form1 = $this->createMock(Form::class);
        $form1->method('getId')->willReturn(1);
        $form1->method('getAlias')->willReturn('form1');

        $submissionForm1 = $this->createMock(Submission::class);
        $submissionForm1->method('getForm')->willReturn($form1);

        // Setup second form for lead2
        $form2 = $this->createMock(Form::class);
        $form2->method('getId')->willReturn(2);
        $form2->method('getAlias')->willReturn('form2');

        $submissionForm2 = $this->createMock(Submission::class);
        $submissionForm2->method('getForm')->willReturn($form2);

        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->method('findBy')
            ->willReturnCallback(function ($criteria) use ($submissionForm1, $submissionForm2, $lead1, $lead2) {
                if ($criteria['lead'] === $lead1) {
                    return [$submissionForm1];
                }
                if ($criteria['lead'] === $lead2) {
                    return [$submissionForm2];
                }

                return [];
            });

        $this->submissionModel->method('getRepository')->willReturn($submissionRepo);

        $formData1 = [['email' => 'test1@example.com']];
        $formData2 = [['name' => 'Jane Doe']];

        $this->submissionModel->expects($this->exactly(2))
            ->method('getSubmissionsByForm')
            ->willReturnCallback(function ($id) use ($formData1, $formData2) {
                return 1 === $id ? $formData1 : $formData2;
            });

        // Both forms should be updated
        $this->submissionModel->expects($this->exactly(2))
            ->method('updateSubmissionAnonymizeByLead');

        $service = new AnonymizeContactCompanyData(
            $this->fieldModel,
            $this->logger,
            $this->emailModel,
            $this->submissionModel
        );

        $service->updateFormResults($leads, true);
    }

    public function testUpdateFormResultsWithEmptySubmissions(): void
    {
        $lead = $this->createLead();
        $this->mockSubmissionRepoForLead($lead, []); // no submissions

        $this->submissionModel->expects($this->never())->method('getSubmissionsByForm');
        $this->submissionModel->expects($this->never())->method('updateSubmissionAnonymizeByLead');

        $service = $this->createService();
        $service->updateFormResults(new ArrayCollection([$lead]), true);
    }

    public function testUpdateFormResultsWithEmptyFormData(): void
    {
        $lead  = $this->createMock(Lead::class);
        $leads = new ArrayCollection([$lead]);

        $form = $this->createMock(Form::class);
        $form->method('getId')->willReturn(1);
        $form->method('getAlias')->willReturn('test_form');

        $submissionForm = $this->createMock(Submission::class);
        $submissionForm->method('getForm')->willReturn($form);

        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->method('findBy')
            ->with(['lead' => $lead])
            ->willReturn([$submissionForm]);

        $this->submissionModel->method('getRepository')->willReturn($submissionRepo);

        // Return empty array for form data
        $this->submissionModel->expects($this->once())
            ->method('getSubmissionsByForm')
            ->with(1, 'test_form', $submissionForm)
            ->willReturn([]);

        // Should still call updateSubmissionAnonymizeByLead with empty array
        $this->submissionModel->expects($this->once())
            ->method('updateSubmissionAnonymizeByLead')
            ->with(1, 'test_form', $submissionForm, []);

        $service = new AnonymizeContactCompanyData(
            $this->fieldModel,
            $this->logger,
            $this->emailModel,
            $this->submissionModel
        );

        $service->updateFormResults($leads, true);
    }

    public function testUpdateFormResultsAccumulatesAnonymizedDataAcrossMultipleForms(): void
    {
        $lead = $this->createLead();
        $sub1 = $this->createFormSubmission(1, 'form1');
        $sub2 = $this->createFormSubmission(2, 'form2');
        $this->mockSubmissionRepoForLead($lead, [$sub1, $sub2]);

        $formData1 = [['email' => 'test1@example.com']];
        $formData2 = [['email' => 'test2@example.com']];

        $this->submissionModel->expects($this->exactly(2))
            ->method('getSubmissionsByForm')
            ->willReturnOnConsecutiveCalls($formData1, $formData2);

        $callCount = 0;
        $this->submissionModel->expects($this->exactly(2))
            ->method('updateSubmissionAnonymizeByLead')
            ->willReturnCallback(function ($id, $alias, $submission, $data) use (&$callCount) {
                ++$callCount;
                if (2 === $callCount) {
                    $this->assertCount(2, $data);
                    $this->assertArrayHasKey('test1@example.com', $data);
                    $this->assertArrayHasKey('test2@example.com', $data);
                }
            });

        $service = $this->createService();
        $service->updateFormResults(new ArrayCollection([$lead]), true);
    }

    private function createLead(): Lead
    {
        return $this->createMock(Lead::class);
    }

    private function createFormSubmission(int $formId, string $formAlias): Submission
    {
        $form = $this->createMock(Form::class);
        $form->method('getId')->willReturn($formId);
        $form->method('getAlias')->willReturn($formAlias);

        $submission = $this->createMock(Submission::class);
        $submission->method('getForm')->willReturn($form);

        return $submission;
    }

    /**
     * @param array<Submission> $submissions
     */
    private function mockSubmissionRepoForLead(Lead $lead, array $submissions): void
    {
        $submissionRepo = $this->createMock(SubmissionRepository::class);
        $submissionRepo->method('findBy')
            ->with(['lead' => $lead])
            ->willReturn($submissions);

        $this->submissionModel->method('getRepository')->willReturn($submissionRepo);
    }

    private function createService(): AnonymizeContactCompanyData
    {
        return new AnonymizeContactCompanyData(
            $this->fieldModel,
            $this->logger,
            $this->emailModel,
            $this->submissionModel
        );
    }
}
