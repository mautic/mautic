<?php

namespace Mautic\CoreBundle\Test\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\TestCase;

final class DBALMocker
{
    private ?\PHPUnit\Framework\MockObject\MockObject $mockEm = null;

    private ?\PHPUnit\Framework\MockObject\MockObject $mockConnection = null;

    private ?\PHPUnit\Framework\MockObject\MockObject $mockQueryBuilder = null;

    private $queryResponse;

    private array $queryParts = [
        'select'     => [],
        'from'       => [],
        'where'      => [],
        'parameters' => [],
    ];

    public function __construct(
        private readonly TestCase $testCase,
    ) {
    }

    public function setQueryResponse($queryResponse): void
    {
        $this->queryResponse = $queryResponse;
    }

    public function getQueryParts(): array
    {
        return $this->queryParts;
    }

    public function getQueryPart($part)
    {
        if (array_key_exists($part, $this->queryParts)) {
            return $this->queryParts[$part];
        }

        throw new \UnexpectedValueException(sprintf('The requested query part (%s) does not exist. It must be one of %s.', $part, implode(', ', array_keys($this->queryParts))));
    }

    public function resetQueryParts(): void
    {
        $this->queryParts = [
            'select'     => [],
            'from'       => [],
            'where'      => [],
            'parameters' => [],
        ];
    }

    public function resetUpdated(): void
    {
    }

    public function resetInserted(): void
    {
    }

    public function reset(): void
    {
        $this->resetQueryParts();
        $this->resetUpdated();
        $this->resetInserted();
    }

    public function getMockEm(): \PHPUnit\Framework\MockObject\MockObject
    {
        if (null === $this->mockEm) {
            $entityManagerMockBuilder = new MockBuilder($this->testCase, EntityManager::class);

            $mock = $entityManagerMockBuilder->disableOriginalConstructor()
                ->onlyMethods(
                    [
                        'getConnection',
                        'getReference',
                    ]
                )
                ->getMock();

            $mock->expects(new AnyInvokedCount())
                ->method('getConnection')
                ->willReturn($this->getMockConnection());

            $mock->expects(new AnyInvokedCount())
                ->method('getReference')
                ->willReturnCallback(function (): Lead {
                    if (Lead::class === func_get_arg(0)) {
                        $entity = new Lead();
                    }

                    $entity->setId(func_get_arg(1));

                    return $entity;
                });

            $this->mockEm = $mock;
        }

        return $this->mockEm;
    }

    public function getMockConnection(): \PHPUnit\Framework\MockObject\MockObject
    {
        if (null === $this->mockConnection) {
            $connectionMockBuilder = new MockBuilder($this->testCase, Connection::class);

            $mock = $connectionMockBuilder->disableOriginalConstructor()
                ->onlyMethods([
                    'createQueryBuilder',
                    'quote',
                    'update',
                    'insert',
                ])
                ->getMock();

            $mock->expects(new AnyInvokedCount())
                ->method('createQueryBuilder')
                ->willReturn($this->getMockQueryBuilder());

            $mock->expects(new AnyInvokedCount())
                ->method('quote')
                ->willReturnArgument(0);

            $mock->expects(new AnyInvokedCount())
                ->method('update');

            $mock->expects(new AnyInvokedCount())
                ->method('insert');

            $this->mockConnection = $mock;
        }

        return $this->mockConnection;
    }

    public function getMockQueryBuilder(): \PHPUnit\Framework\MockObject\MockObject
    {
        if (null === $this->mockQueryBuilder) {
            $queryBuilderMockBuilder = new MockBuilder($this->testCase, QueryBuilder::class);

            $mock = $queryBuilderMockBuilder->disableOriginalConstructor()
                ->onlyMethods(
                    [
                        'select',
                        'from',
                        'expr',
                        'where',
                        'andWhere',
                        'setParameter',
                        'executeQuery',
                    ]
                )
                ->getMock();

            $mock->expects(new AnyInvokedCount())
                ->method('select')
                ->willReturnCallback(
                    function () use ($mock): \PHPUnit\Framework\MockObject\MockObject {
                        $this->queryParts['select'][] = func_get_args();

                        return $mock;
                    }
                );

            $mock->expects(new AnyInvokedCount())
                ->method('from')
                ->willReturnCallback(
                    function () use ($mock): \PHPUnit\Framework\MockObject\MockObject {
                        $this->queryParts['from'][] = func_get_args();

                        return $mock;
                    }
                );

            $mock->expects(new AnyInvokedCount())
                ->method('expr')
                ->willReturnCallback(
                    fn (): ExpressionBuilder => new ExpressionBuilder($this->getMockConnection())
                );

            $mock->expects(new AnyInvokedCount())
                ->method('where')
                ->willReturnCallback(
                    function () use ($mock): \PHPUnit\Framework\MockObject\MockObject {
                        $this->queryParts['where'][] = func_get_args();

                        return $mock;
                    }
                );

            $mock->expects(new AnyInvokedCount())
                ->method('andWhere')
                ->willReturnCallback(
                    function () use ($mock): \PHPUnit\Framework\MockObject\MockObject {
                        $this->queryParts['where'][] = func_get_args();

                        return $mock;
                    }
                );

            $mock->expects(new AnyInvokedCount())
                ->method('setParameter')
                ->willReturnCallback(
                    function () use ($mock): \PHPUnit\Framework\MockObject\MockObject {
                        $this->queryParts['parameters'][] = func_get_args();

                        return $mock;
                    }
                );

            $mock->expects(new AnyInvokedCount())
                ->method('executeQuery')
                ->willReturnCallback($this->getMockResultStatement(...));

            $this->mockQueryBuilder = $mock;
        }

        return $this->mockQueryBuilder;
    }

    public function getMockResultStatement()
    {
        $resultMockBuilder = new MockBuilder($this->testCase, Result::class);

        $mock = $resultMockBuilder->disableOriginalConstructor()
            ->onlyMethods([
                'fetchNumeric',
                'fetchAssociative',
                'fetchOne',
                'fetchAllNumeric',
                'fetchAllAssociative',
                'fetchFirstColumn',
                'rowCount',
                'columnCount',
                'free',
            ])
            ->getMock();

        $mock->method('columnCount')
            ->willReturnCallback(function (): int {
                if (isset($this->queryResponse[0]) && is_array($this->queryResponse[0])) {
                    return count($this->queryResponse[0]);
                }

                return count($this->queryResponse);
            });

        $mock->expects(new AnyInvokedCount())
            ->method('fetchOne')
            ->willReturn($this->queryResponse);

        $mock->expects(new AnyInvokedCount())
            ->method('fetchAllAssociative')
            ->willReturn($this->queryResponse);

        return $mock;
    }
}
