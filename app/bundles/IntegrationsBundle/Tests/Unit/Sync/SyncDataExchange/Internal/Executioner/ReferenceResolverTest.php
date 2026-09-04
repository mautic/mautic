<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\Executioner;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\ReferenceResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ReferenceResolverTest extends TestCase
{
    /**
     * @var MockObject&Connection
     */
    private MockObject $connection;

    private ReferenceResolver $referenceResolver;

    protected function setup(): void
    {
        $this->connection        = $this->createMock(Connection::class);
        $this->referenceResolver = new ReferenceResolver($this->connection);
    }

    public function testResolveLeadReferences(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('fetchOne')
            ->willReturnOnConsecutiveCalls('Company name', false);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->exactly(2))->method('executeQuery')
            ->willReturn($result);

        $this->connection->expects($this->exactly(2))->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $companyReference  = $this->createReference('company', 3);
        $userReference     = $this->createReference('user', 4);
        $notFoundReference = $this->createReference('company', 5);

        $changedObject = new ObjectChangeDAO('integration', 'lead', '1', 'Lead', '00Q4H00000juXes')
            ->addField(new FieldDAO('company', new NormalizedValueDAO('reference', $companyReference, $companyReference)))
            ->addField(new FieldDAO('user', new NormalizedValueDAO('reference', $userReference, $userReference)))
            ->addField(new FieldDAO('city', new NormalizedValueDAO('text', 'Some city', 'Some city')))
            ->addField(new FieldDAO('manager', new NormalizedValueDAO('reference', $notFoundReference, $notFoundReference)));

        $this->referenceResolver->resolveReferences('lead', [$changedObject]);

        $companyField = $changedObject->getField('company');
        $this->assertInstanceOf(FieldDAO::class, $companyField);
        $this->assertSame('Company name', $companyField->getValue()->getOriginalValue());
        $this->assertSame('Company name', $companyField->getValue()->getNormalizedValue());

        $userField = $changedObject->getField('user');
        $this->assertInstanceOf(FieldDAO::class, $userField);
        $this->assertNull($userField->getValue()->getOriginalValue());
        $this->assertNull($userField->getValue()->getNormalizedValue());

        $cityField = $changedObject->getField('city');
        $this->assertInstanceOf(FieldDAO::class, $cityField);
        $this->assertSame('Some city', $cityField->getValue()->getOriginalValue());
        $this->assertSame('Some city', $cityField->getValue()->getNormalizedValue());

        $managerField = $changedObject->getField('manager');
        $this->assertInstanceOf(FieldDAO::class, $managerField);
        $this->assertNull($managerField->getValue()->getOriginalValue());
        $this->assertNull($managerField->getValue()->getNormalizedValue());
    }

    public function testResolveCompanyReferences(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')
            ->willReturn('Company name');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('executeQuery')
            ->willReturn($result);

        $this->connection->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $companyReference  = $this->createReference('company', 3);

        $changedObject = new ObjectChangeDAO('integration', 'company', '1', 'Lead', '00Q4H00000juXes')
            ->addField(new FieldDAO('company', new NormalizedValueDAO('reference', $companyReference, $companyReference)));

        $this->referenceResolver->resolveReferences('company', [$changedObject]);

        $companyField = $changedObject->getField('company');
        $this->assertInstanceOf(FieldDAO::class, $companyField);
        $this->assertSame($companyReference, $companyField->getValue()->getOriginalValue());
        $this->assertSame($companyReference, $companyField->getValue()->getNormalizedValue());
    }

    private function createReference(string $type, int $value): ReferenceValueDAO
    {
        $reference = new ReferenceValueDAO();
        $reference->setType($type);
        $reference->setValue($value);

        return $reference;
    }
}
