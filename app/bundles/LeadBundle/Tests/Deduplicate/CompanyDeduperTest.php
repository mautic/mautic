<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Deduplicate;

use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;

final class CompanyDeduperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FieldModel
     */
    private MockObject $fieldModel;

    protected function setUp(): void
    {
        $this->fieldModel = $this->createMock(FieldModel::class);
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
            $this->createStub(FieldsWithUniqueIdentifier::class),
            $this->createStub(CompanyRepository::class)
        );
    }
}
