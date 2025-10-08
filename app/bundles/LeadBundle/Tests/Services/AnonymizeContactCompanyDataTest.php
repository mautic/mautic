<?php

namespace Mautic\LeadBundle\Tests\Services;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Services\AnonymizeContactCompanyData;
use Mautic\LeadBundle\Model\FieldModel;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class AnonymizeContactCompanyDataTest extends TestCase
{
    private FieldModel $fieldModel;
    private LoggerInterface $logger;
    private EmailModel $emailModel;
    private SubmissionModel $submissionModel;

    protected function setUp(): void
    {
        $this->fieldModel     = $this->createMock(FieldModel::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->emailModel     = $this->createMock(EmailModel::class);
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
        $alias = 'email';
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
}