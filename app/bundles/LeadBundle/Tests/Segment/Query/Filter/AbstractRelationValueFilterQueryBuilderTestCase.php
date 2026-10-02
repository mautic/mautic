<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractRelationValueFilterQueryBuilderTestCase extends TestCase
{
    use MockedConnectionTrait;

    protected RandomParameterName&MockObject $randomParameter;

    protected EventDispatcherInterface&MockObject $dispatcher;

    protected Connection&MockObject $connectionMock;

    protected function setUp(): void
    {
        $this->randomParameter = $this->createMock(RandomParameterName::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock  = $this->getMockedConnection();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        return $queryBuilder;
    }

    /**
     * @param array<mixed> $batch
     */
    protected function createFilter(string $operator, mixed $value, array $batch = []): ContactSegmentFilter
    {
        return new class($operator, $value, $batch) extends ContactSegmentFilter {
            /**
             * @param array<string, mixed> $batch
             */
            public function __construct(
                private readonly string $operator,
                private readonly mixed $value,
                private readonly array $batch,
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
                        static fn ($value): string => ':'.$value,
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
