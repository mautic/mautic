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
     * @var MockObject&CompanyRepository
     */
    private MockObject $companyRepository;

    /**
     * @var MockObject&FieldsWithUniqueIdentifier
     */
    private MockObject $fieldsWithUniqueIdentifier;

    protected function setUp(): void
    {
        $this->fieldModel = $this->createMock(FieldModel::class);

        $this->fieldsWithUniqueIdentifier = $this->createMock(FieldsWithUniqueIdentifier::class);

        $this->companyRepository = $this->createMock(CompanyRepository::class);
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
