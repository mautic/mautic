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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReferenceResolverTest extends TestCase
{
    /**
     * @var Connection|MockObject
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
        $this->connection->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilder('Company name', false));

        $companyReference  = $this->createReference('company', 3);
        $userReference     = $this->createReference('user', 4);
        $notFoundReference = $this->createReference('company', 5);

        $changedObject = (new ObjectChangeDAO('integration', 'lead', '1', 'Lead', '00Q4H00000juXes'))
            ->addField(new FieldDAO('company', new NormalizedValueDAO('reference', $companyReference, $companyReference)))
            ->addField(new FieldDAO('user', new NormalizedValueDAO('reference', $userReference, $userReference)))
            ->addField(new FieldDAO('city', new NormalizedValueDAO('text', 'Some city', 'Some city')))
            ->addField(new FieldDAO('manager', new NormalizedValueDAO('reference', $notFoundReference, $notFoundReference)));

        $this->referenceResolver->resolveReferences('lead', [$changedObject]);

        $companyField = $changedObject->getField('company');
        Assert::assertInstanceOf(FieldDAO::class, $companyField);
        Assert::assertSame('Company name', $companyField->getValue()->getOriginalValue());
        Assert::assertSame('Company name', $companyField->getValue()->getNormalizedValue());

        $userField = $changedObject->getField('user');
        Assert::assertInstanceOf(FieldDAO::class, $userField);
        Assert::assertNull($userField->getValue()->getOriginalValue());
        Assert::assertNull($userField->getValue()->getNormalizedValue());

        $cityField = $changedObject->getField('city');
        Assert::assertInstanceOf(FieldDAO::class, $cityField);
        Assert::assertSame('Some city', $cityField->getValue()->getOriginalValue());
        Assert::assertSame('Some city', $cityField->getValue()->getNormalizedValue());

        $managerField = $changedObject->getField('manager');
        Assert::assertInstanceOf(FieldDAO::class, $managerField);
        Assert::assertNull($managerField->getValue()->getOriginalValue());
        Assert::assertNull($managerField->getValue()->getNormalizedValue());
    }

    public function testResolveCompanyReferences(): void
    {
        $this->connection->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilder('Company name'));

        $companyReference  = $this->createReference('company', 3);

        $changedObject = (new ObjectChangeDAO('integration', 'company', '1', 'Lead', '00Q4H00000juXes'))
            ->addField(new FieldDAO('company', new NormalizedValueDAO('reference', $companyReference, $companyReference)));

        $this->referenceResolver->resolveReferences('company', [$changedObject]);

        $companyField = $changedObject->getField('company');
        Assert::assertInstanceOf(FieldDAO::class, $companyField);
        Assert::assertSame($companyReference, $companyField->getValue()->getOriginalValue());
        Assert::assertSame($companyReference, $companyField->getValue()->getNormalizedValue());
    }

    private function createReference(string $type, int $value): ReferenceValueDAO
    {
        $reference = new ReferenceValueDAO();
        $reference->setType($type);
        $reference->setValue($value);

        return $reference;
    }

    /**
     * @param mixed ...$returnValues
     *
     * @return QueryBuilder|MockObject
     */
    private function createQueryBuilder(...$returnValues)
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')
            ->willReturnOnConsecutiveCalls(...$returnValues);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('executeQuery')
            ->willReturn($result);

        return $queryBuilder;
    }
}
