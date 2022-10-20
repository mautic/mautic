<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * This trait will help with writing unit tests for repositories.
 * However, consider using a functional test instead. It's simpler and adds more value.
 */
trait RepositoryConfiguratorTrait
{
    /**
     * @var MockObject&EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MockObject&ClassMetadata<object>
     */
    private $classMetadata;

    /**
     * @var MockObject&ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var MockObject&Connection
     */
    private $connection;

    /**
     * @var MockObject&ResultStatement<mixed>
     */
    private $resultStatement;

    /**
     * @return object the repository for the entity
     */
    private function configureRepository(string $entityClass)
    {
        $this->classMetadata   = $this->createMock(ClassMetadata::class);
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->connection      = $this->createMock(Connection::class);
        $this->resultStatement = $this->createMock(ResultStatement::class);

        $this->configureMocks($entityClass);

        $repositoryClass = $entityClass.'Repository';

        return new $repositoryClass($this->managerRegistry);
    }

    private function configureMocks(string $entityClass): void
    {
        $this->managerRegistry->method('getManagerForClass')->with($entityClass)->willReturn($this->entityManager);
        $this->entityManager->method('getClassMetadata')->with($entityClass)->willReturn($this->classMetadata);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('getExpressionBuilder')->willReturnCallback(fn () => new ExpressionBuilder($this->connection));
        $this->connection->method('executeQuery')->willReturn($this->resultStatement);
        $this->connection->method('quote')->willReturnCallback(fn ($value) => "'$value'");
    }
}
