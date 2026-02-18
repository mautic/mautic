<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ComplexRelationValueFilterQueryBuilderTest extends TestCase
{
    use MockedConnectionTrait;

    /**
     * @var MockObject|RandomParameterName
     */
    private MockObject $randomParameter;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var Connection&MockObject
     */
    private MockObject $connectionMock;

    private ComplexRelationValueFilterQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->randomParameter = $this->createMock(RandomParameterName::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock  = $this->getMockedConnection();

        $this->queryBuilder = new ComplexRelationValueFilterQueryBuilder(
            $this->randomParameter,
            $this->dispatcher
        );
    }

    public function testGetServiceId(): void
    {
        Assert::assertSame(
            'mautic.lead.query.builder.complex_relation.value',
            $this->queryBuilder::getServiceId()
        );
    }

    public function testApplyQueryCreatesJoinAndReturnsExpectedExpression(): void
    {
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'cmp',
            'rel'
        );

        $filter = $this->createFilter('empty', 'ignored');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString('SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'leads l', $debugOutput);
        Assert::assertStringContainsString(
            'LEFT JOIN '.MAUTIC_TABLE_PREFIX.'companies_leads rel ON rel.lead_id = l.id',
            $debugOutput
        );
        Assert::assertStringContainsString(
            'LEFT JOIN '.MAUTIC_TABLE_PREFIX.'companies cmp ON cmp.id = rel.company_id',
            $debugOutput
        );
        Assert::assertStringContainsString(
            '(cmp.company_name IS NULL) OR (cmp.company_name = )',
            $debugOutput
        );
    }

    public function testApplyQueryThrowsOnUnsupportedOperator(): void
    {
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'cmp',
            'rel'
        );

        $filter = $this->createFilter('unsupported', 'value');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported filter operator "unsupported".');

        $this->queryBuilder->applyQuery($queryBuilder, $filter);
    }

    /**
     * @param array<string, mixed> $batch
     */
    private function createFilter(string $operator, mixed $value, array $batch = []): ContactSegmentFilter
    {
        return new class($operator, $value, $batch) extends ContactSegmentFilter {
            /**
             * @param array<string, mixed> $batch
             */
            public function __construct(
                private string $operator,
                private mixed $value,
                private array $batch,
            ) {
            }

            public function getOperator(): string
            {
                return $this->operator;
            }

            public function getParameterValue(): mixed
            {
                return $this->value;
            }

            /**
             * @return array<string>|string
             */
            public function getParameterHolder(mixed $argument): array|string
            {
                if (is_array($argument)) {
                    return array_map(
                        static fn ($value) => ':'.$value,
                        $argument
                    );
                }

                return ':'.$argument;
            }

            public function getField(): string
            {
                return 'company_name';
            }

            public function getTable(): string
            {
                return MAUTIC_TABLE_PREFIX.'companies';
            }

            public function getRelationJoinTable(): string
            {
                return MAUTIC_TABLE_PREFIX.'companies_leads';
            }

            public function getRelationJoinTableField(): string
            {
                return 'company_id';
            }

            public function getGlue(): string
            {
                return 'and';
            }

            public function getBatchLimiters(): array
            {
                return $this->batch;
            }
        };
    }
}
