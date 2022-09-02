<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\WebhookBundle\Entity\Webhook;
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
     * @var ClassMetadata<Webhook>|MockObject
     */
    private $classMetadata;

    /**
     * @var WebhookQueueRepository
     */
    private $repository;

    protected function setUp(): void
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

    public function testWebhookExists():void
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

        self::assertTrue($this->repository->exists($id));
    }

    public function testWebhookExistsNotExists(): void
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

        self::assertFalse($this->repository->exists($id));
    }
}
