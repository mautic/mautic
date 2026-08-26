<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Deduplicate;

use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use PHPUnit\Framework\MockObject\MockObject;

final class CompanyDeduperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FieldList
     */
    private MockObject $fieldList;

    protected function setUp(): void
    {
        $this->fieldList = $this->createMock(FieldList::class);
    }

    public function testUniqueFieldNotFoundException(): void
    {
        $this->expectException(UniqueFieldNotFoundException::class);
        $this->fieldList->method('getFieldList')->willReturn([]);
        $this->getDeduper()->checkForDuplicateCompanies([]);
    }

    private function getDeduper(): CompanyDeduper
    {
        return new CompanyDeduper(
            $this->fieldList,
            $this->createStub(FieldsWithUniqueIdentifier::class),
            $this->createStub(CompanyRepository::class)
        );
    }
}
