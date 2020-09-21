<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookQueueRepositoryTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var ClassMetadata|MockObject
     */
    private $classMetadata;

    /**
     * @var WebhookQueueRepository
     */
    private $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);

        $this->repository = new WebhookQueueRepository(
            $this->entityManager,
            $this->classMetadata
        );
    }

    public function testDeleteQueuesByIdEmptyArgument(): void
    {
        $this->entityManager->expects(self::never())
            ->method('getConnection');

        self::assertNull($this->repository->deleteQueuesById([]));
    }

    public function testDeleteQueuesById(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $ids        = [1, 2, 3];
        $expression = 'expression';

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->expects(self::once())
            ->method('in')
            ->with('id', $ids)
            ->willReturn($expression);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $queryBuilder->expects(self::once())
            ->method('delete')
            ->with(MAUTIC_TABLE_PREFIX.'webhook_queue')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with($expression)
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('execute');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertNull($this->repository->deleteQueuesById($ids));
    }

    public function testGetQueueCountByWebhookIdIdNotSet(): void
    {
        $this->entityManager->expects(self::never())
            ->method('getConnection');

        self::assertSame(0, $this->repository->getQueueCountByWebhookId(0));
    }

    public function testWebhookExistsExists()
    {
        $id = 1;

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $statement = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
        $statement->expects(self::once())
            ->method('fetch')
            ->willReturn([0 => ['id' => $id]]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e.id')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'webhook_queue', 'e')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.webhook_id = :id')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', $id)
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('execute')
            ->willReturn($statement);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertTrue($this->repository->webhookExists($id));
    }

    public function testWebhookExistsNotExists()
    {
        $id = 1;

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $statement = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
        $statement->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e.id')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'webhook_queue', 'e')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.webhook_id = :id')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', $id)
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('execute')
            ->willReturn($statement);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertFalse($this->repository->webhookExists($id));
    }

    public function testGetConsecutiveIDsAsRanges()
    {
        $webhookId      = 1;
        $expectedResult = [];

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $statement = $this->createMock(DriverStatement::class);
        $statement->expects(self::once())
            ->method('execute')
            ->with([
                ':webhookId' => $webhookId,
            ])
            ->willReturn($statement);
        $statement->expects(self::once())
            ->method('fetchAll')
            ->willReturn($expectedResult);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('prepare')
            ->willReturn($statement);

        $this->entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->assertSame($expectedResult, $this->repository->getConsecutiveIDsAsRanges((string) $webhookId));
    }

    public function testGetConsecutiveIDsAsRangesInvalidArgumentException()
    {
        self::expectException(\InvalidArgumentException::class);

        $this->repository->getConsecutiveIDsAsRanges(0);
    }
}
