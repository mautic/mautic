<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LeadFieldRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private LeadFieldRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(LeadField::class);
    }

    public function testCompareDateValueForContactField(): void
    {
        $contactId                = 12;
        $fieldAlias               = 'date_field';
        $value                    = '2019-04-30';
        $builderAlias             = $this->createMock(QueryBuilder::class);
        $builderCompare           = $this->createMock(QueryBuilder::class);
        $statementAliasResult     = $this->createMock(Result::class);
        $statementCompareResult   = $this->createMock(Result::class);
        $exprCompare              = $this->createMock(ExpressionBuilder::class);

        // $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($builderCompare, $builderAlias));

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('setParameter')
            ->with('object', 'company')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('orderBy')
            ->with('f.field_order', 'ASC')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statementAliasResult);

        // No company column found. Therefore it's a contact field.
        $statementAliasResult->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $exprCompare->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['l.id', ':lead'],
                ['l.date_field', ':value'] // See? It's a contact column.
            );

        $builderCompare->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderCompare->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['lead', $contactId],
                ['value', $value]
            )
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statementCompareResult);

        // No contact ID was found by the value so the result should be false.
        $statementCompareResult->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([]);

        $this->assertFalse($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }

    public function testCompareDateValueForCompanyField(): void
    {
        $contactId                = 12;
        $fieldAlias               = 'date_field';
        $value                    = '2019-04-30';
        $builderAlias             = $this->createMock(QueryBuilder::class);
        $builderCompare           = $this->createMock(QueryBuilder::class);
        $statementAliasResult     = $this->createMock(Result::class);
        $statementCompareResult   = $this->createMock(Result::class);
        $exprCompare              = $this->createMock(ExpressionBuilder::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($builderCompare, $builderAlias));

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('setParameter')
            ->with('object', 'company')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('orderBy')
            ->with('f.field_order', 'ASC')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statementAliasResult);

        // A company column found. Therefore it's a company field.
        $statementAliasResult->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([['alias' => $fieldAlias]]);

        $exprCompare->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['l.id', ':lead'],
                ['company.date_field', ':value'] // See? It's a company column.
            );

        $builderCompare->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                ['l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', 'l.id = companies_lead.lead_id'],
                ['companies_lead', MAUTIC_TABLE_PREFIX.'companies', 'company', 'companies_lead.company_id = company.id']
            );

        $builderCompare->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderCompare->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['lead', $contactId],
                ['value', $value]
            )
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statementCompareResult);

        // A contact ID was found by the value so the result should be true.
        $statementCompareResult->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(['id' => 456]);

        $this->assertTrue($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }

    public function testGetListablePublishedFields(): void
    {
        $query = $this->createQueryMock();
        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('SELECT f FROM  f INDEX BY f.id WHERE f.isListable = 1 AND f.isPublished = 1 ORDER BY f.object ASC')
            ->willReturn($query);

        $query->method('execute')->willReturn([]);

        $this->repository->getListablePublishedFields();
    }

    public function testGetFieldSchemaData(): void
    {
        $query = $this->createQueryMock();
        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('SELECT f.alias, f.label, f.type, f.isUniqueIdentifer, f.charLengthLimit FROM  f INDEX BY f.alias WHERE f.object = :object')
            ->willReturn($query);

        $result = [];
        $query->method('execute')->willReturn($result);

        $this->assertSame($result, $this->repository->getFieldSchemaData('lead'));
    }

    public function testGetFieldThatIsMissingColumnWhenMutlipleColumsMissing(): void
    {
        $queryBuilder = $this->createMock(OrmQueryBuilder::class);

        $this->entityManager->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('from')
            ->willReturnSelf();

        $expr = $this->createMock(Query\Expr::class);
        $queryBuilder->expects(self::once())
            ->method('expr')
            ->willReturn($expr);

        $comparison = $this->createMock(Query\Expr\Comparison::class);
        $expr->expects(self::once())
            ->method('eq')
            ->willReturn($comparison);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with($comparison)
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $leadField = $this->createMock(LeadField::class);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($leadField);

        self::assertSame(
            $leadField,
            $this->repository->getFieldThatIsMissingColumn()
        );
    }

    private function createQueryMock(): MockObject
    {
        // This is terrible, but the Query class is final and AbstractQuery doesn't have some methods used.
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setParameters',
                'getSingleResult',
                'getSQL',
                '_doExecute',
                'execute',
            ])
            ->addMethods([
                'setFirstResult',
                'setMaxResults',
            ])
            ->getMock();

        $ormBuilder = new OrmQueryBuilder($this->entityManager);
        $this->entityManager->method('createQueryBuilder')->willReturn($ormBuilder);
        $this->entityManager->method('createQuery')->willReturn($query);
        $query->method('setParameters')->willReturnSelf();
        $query->method('setFirstResult')->willReturnSelf();
        $query->method('setMaxResults')->willReturnSelf();

        return $query;
    }
}
