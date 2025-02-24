<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use PHPUnit\Framework\MockObject\MockObject;

class EmailRepositoryUpCountSentTest extends \PHPUnit\Framework\TestCase
{
    use RepositoryConfiguratorTrait;

    /**
     * @var MockObject|QueryBuilder
     */
    private MockObject $queryBuilderMock;

    private QueryBuilder $queryBuilder;

    private EmailRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo             = $this->configureRepository(Email::class);
        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->queryBuilder     = new QueryBuilder($this->connection);
    }

    public function testUpCountSentWithNoIncrease(): void
    {
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
        $this->queryBuilderMock->expects($this->never())
            ->method('update');

        $this->repo->upCountSent(45, 0);
    }

    public function testUpCountSentWithId(): void
    {
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->connection
            ->expects($this->exactly(1))
            ->method('executeStatement')
            ->willReturn(1);

        $this->repo->upCountSent(11);
        $generatedSql = $this->queryBuilder->getSQL();

        // Assert that the generated SQL matches our expectations
        $expectedSql = 'UPDATE test_emails SET sent_count = sent_count + :increaseBy WHERE id = :id';
        $this->assertEquals($expectedSql, $generatedSql);

        // Assert parameters are properly set up
        $this->assertEquals(11, $this->queryBuilder->getParameter('id'));
        $this->assertEquals(1, $this->queryBuilder->getParameter('increaseBy'));
    }

    public function testUpCountWithVariant(): void
    {
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->connection
            ->expects($this->exactly(1))
            ->method('executeStatement')
            ->willReturn(1);

        $this->repo->upCountSent(11, 2, true);
        $generatedSql = $this->queryBuilder->getSQL();

        // Assert that the generated SQL matches our expectations
        $expectedSql = 'UPDATE test_emails SET sent_count = sent_count + :increaseBy, variant_sent_count = variant_sent_count + :increaseBy WHERE id = :id';
        $this->assertEquals($expectedSql, $generatedSql);
    }

    public function testUpCountWithTwoErrors(): void
    {
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->connection
            ->expects($this->exactly(3))
            ->method('executeStatement')
            ->will(
                $this->onConsecutiveCalls(
                    $this->throwException(new DBALException()),
                    $this->throwException(new DBALException()),
                    1
                )
            );

        $this->repo->upCountSent(45);
    }

    public function testUpCountWithFourErrors(): void
    {
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->connection
            ->expects($this->exactly(3))
            ->method('executeStatement')
            ->will($this->throwException(new DBALException()));

        $this->expectException(DBALException::class);
        $this->repo->upCountSent(45);
    }
}
