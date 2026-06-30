<?php

namespace Mautic\LeadBundle\Tests\Deduplicate;

use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;

class CompanyDeduperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FieldModel
     */
    private MockObject $fieldModel;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&CompanyRepository
     */
    private \PHPUnit\Framework\MockObject\Stub $companyRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&FieldsWithUniqueIdentifier
     */
    private \PHPUnit\Framework\MockObject\Stub $fieldsWithUniqueIdentifier;

    protected function setUp(): void
    {
        $this->fieldModel = $this->createMock(FieldModel::class);

        $this->fieldsWithUniqueIdentifier = $this->createStub(FieldsWithUniqueIdentifier::class);

        $this->companyRepository = $this->createStub(CompanyRepository::class);
    }

    public function testUniqueFieldNotFoundException(): void
    {
        $this->expectException(UniqueFieldNotFoundException::class);
        $this->fieldModel->method('getFieldList')->willReturn([]);
        $this->getDeduper()->checkForDuplicateCompanies([]);
    }

    private function getDeduper(): CompanyDeduper
    {
        return new CompanyDeduper(
            $this->fieldModel,
            $this->fieldsWithUniqueIdentifier,
            $this->companyRepository
        );
    }
}
