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
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Creates and configures mock objects for date value comparison tests.
     *
     * @return array{
     *     builderAlias: MockObject&QueryBuilder,
     *     builderCompare: MockObject&QueryBuilder,
     *     statementAliasResult: MockObject&Result,
     *     statementCompareResult: MockObject&Result,
     *     exprCompare: MockObject&ExpressionBuilder
     * }
     */
    private function createDateValueComparisonMocks(): array
    {
        $builderAlias           = $this->createMock(QueryBuilder::class);
        $builderCompare         = $this->createMock(QueryBuilder::class);
        $statementAliasResult   = $this->createMock(Result::class);
        $statementCompareResult = $this->createMock(Result::class);
        $exprCompare            = $this->createMock(ExpressionBuilder::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($builderCompare, $builderAlias);

        return [
            'builderAlias'           => $builderAlias,
            'builderCompare'         => $builderCompare,
            'statementAliasResult'   => $statementAliasResult,
            'statementCompareResult' => $statementCompareResult,
            'exprCompare'            => $exprCompare,
        ];
    }

    /**
     * Sets up expectations for the alias query builder.
     */
    private function setupAliasQueryBuilderExpectations(
        MockObject $builderAlias,
        MockObject $statementAliasResult,
    ): void {
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
    }

    /**
     * Sets up expectations for the compare query builder for contact fields.
     */
    private function setupCompareQueryBuilderForContactField(
        MockObject $builderCompare,
        MockObject $exprCompare,
        MockObject $statementCompareResult,
        int $contactId,
        string $fieldAlias,
        ?string $value = null,
    ): void {
        $exprCompare->expects($this->exactly(null !== $value ? 2 : 1))
            ->method('eq')
            ->willReturnCallback(function (...$parameters) use ($fieldAlias, $value) {
                static $invocationCount = 0;
                ++$invocationCount;

                if (1 === $invocationCount) {
                    $this->assertSame('l.id', $parameters[0]);
                    $this->assertSame(':lead', $parameters[1]);
                }
                if (2 === $invocationCount && null !== $value) {
                    $this->assertSame("l.{$fieldAlias}", $parameters[0]);
                    $this->assertSame(':value', $parameters[1]);
                }
            });

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

        $parameterCount = null !== $value ? 2 : 1;
        $builderCompare->expects($this->exactly($parameterCount))
            ->method('setParameter')
            ->willReturnCallback(function (...$parameters) use ($contactId, $value, $builderCompare) {
                static $invocationCount = 0;
                ++$invocationCount;

                if (1 === $invocationCount) {
                    $this->assertSame('lead', $parameters[0]);
                    $this->assertSame($contactId, $parameters[1]);
                }
                if (2 === $invocationCount && null !== $value) {
                    $this->assertSame('value', $parameters[0]);
                    $this->assertSame($value, $parameters[1]);
                }

                return $builderCompare;
            });

        $builderCompare->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statementCompareResult);
    }

    public function testCompareDateValueForContactField(): void
    {
        $contactId  = 12;
        $fieldAlias = 'date_field';
        $value      = '2019-04-30';

        $mocks = $this->createDateValueComparisonMocks();

        $this->setupAliasQueryBuilderExpectations(
            $mocks['builderAlias'],
            $mocks['statementAliasResult']
        );

        // No company column found. Therefore it's a contact field.
        $mocks['statementAliasResult']->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->setupCompareQueryBuilderForContactField(
            $mocks['builderCompare'],
            $mocks['exprCompare'],
            $mocks['statementCompareResult'],
            $contactId,
            $fieldAlias,
            $value
        );

        // No contact ID was found by the value so the result should be false.
        $mocks['statementCompareResult']->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([]);

        $this->assertFalse($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }

    public function testCompareDateValueForCompanyField(): void
    {
        $contactId  = 12;
        $fieldAlias = 'date_field';
        $value      = '2019-04-30';

        $mocks = $this->createDateValueComparisonMocks();

        $this->setupAliasQueryBuilderExpectations(
            $mocks['builderAlias'],
            $mocks['statementAliasResult']
        );

        // A company column found. Therefore it's a company field.
        $mocks['statementAliasResult']->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([['alias' => $fieldAlias]]);

        $matcher = $this->exactly(2);
        $mocks['exprCompare']->expects($matcher)
            ->method('eq')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('l.id', $parameters[0]);
                    $this->assertSame(':lead', $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('company.date_field', $parameters[0]);
                    $this->assertSame(':value', $parameters[1]);
                }
            });

        $matcher = $this->exactly(2);
        $mocks['builderCompare']->expects($matcher)
            ->method('leftJoin')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('l', $parameters[0]);
                    $this->assertSame(MAUTIC_TABLE_PREFIX.'companies_leads', $parameters[1]);
                    $this->assertSame('companies_lead', $parameters[2]);
                    $this->assertSame('l.id = companies_lead.lead_id', $parameters[3]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('companies_lead', $parameters[0]);
                    $this->assertSame(MAUTIC_TABLE_PREFIX.'companies', $parameters[1]);
                    $this->assertSame('company', $parameters[2]);
                    $this->assertSame('companies_lead.company_id = company.id', $parameters[3]);
                }
            });

        $mocks['builderCompare']->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $mocks['builderCompare']->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $mocks['builderCompare']->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $matcher = $this->exactly(2);
        $mocks['builderCompare']->expects($matcher)
            ->method('setParameter')->willReturnCallback(function (...$parameters) use ($matcher, $contactId, $value, $mocks) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('lead', $parameters[0]);
                    $this->assertSame($contactId, $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('value', $parameters[0]);
                    $this->assertSame($value, $parameters[1]);
                }

                return $mocks['builderCompare'];
            });

        $mocks['builderCompare']->expects($this->once())
            ->method('executeQuery')
            ->willReturn($mocks['statementCompareResult']);

        // A contact ID was found by the value so the result should be true.
        $mocks['statementCompareResult']->expects($this->once())
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
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setParameters',
                'getSingleResult',
                'getSQL',
                '_doExecute',
                'execute',
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

    /**
     * @return iterable<array{0: string, 1: array<string, int>|array<empty>, 2: bool}>
     */
    public static function dataGetEmptyOperators(): iterable
    {
        yield ['empty', ['id' => 123],  true];
        yield ['!empty', ['id' => 123],  true];
        yield ['empty', [], false];
        yield ['!empty', [], false];
    }

    /**
     * @param array<string, int>|array<empty> $returnValue
     */
    #[DataProvider('dataGetEmptyOperators')]
    public function testCompareEmptyDateValueForContactField(string $operator, array $returnValue, bool $expected): void
    {
        $contactId  = 12;
        $fieldAlias = 'date_field';

        $mocks = $this->createDateValueComparisonMocks();

        $this->setupAliasQueryBuilderExpectations(
            $mocks['builderAlias'],
            $mocks['statementAlias'] = $mocks['statementAliasResult']
        );

        // No company column found. Therefore it's a contact field.
        $mocks['statementAlias']->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $mocks['exprCompare']->expects($this->once())
            ->method('eq')
            ->with('l.id', ':lead');

        $operators = [
            'empty'     => 'isNull',
            '!empty'    => 'isNotNull',
        ];

        $mocks['exprCompare']->expects($this->once())
            ->method($operators[$operator])
            ->with('l.date_field');

        $mocks['builderCompare']->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $mocks['builderCompare']->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $mocks['builderCompare']->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $mocks['builderCompare']->expects($this->once())
            ->method('setParameter')
            ->with('lead', $contactId)
            ->willReturnSelf();

        $mocks['builderCompare']->expects($this->once())
            ->method('executeQuery')
            ->willReturn($mocks['statementCompare'] = $mocks['statementCompareResult']);

        // No contact ID was found by the value so the result should be false.
        $mocks['statementCompare']->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn($returnValue);

        $this->assertSame($expected, $this->repository->compareEmptyDateValue($contactId, $fieldAlias, $operator));
    }
}
