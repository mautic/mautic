<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;

class EmailRepositoryIncrementReadTest extends \PHPUnit\Framework\TestCase
{
    use RepositoryConfiguratorTrait;

    private QueryBuilder $queryBuilder;

    private QueryBuilder $subQueryBuilder;

    /**
     * @var EmailRepository|object
     */
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo             = $this->configureRepository(Email::class);
        $this->queryBuilder     = new QueryBuilder($this->connection);
        $this->subQueryBuilder  = new QueryBuilder($this->connection);
        $this->connection->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $this->queryBuilder,
            $this->subQueryBuilder
        );
    }

    public function testIncrementRead(): void
    {
        $this->connection
            ->expects($this->exactly(1))
            ->method('executeStatement')
            ->willReturn(1);

        $this->repo->incrementRead(11, '21');
        $generatedSql = $this->queryBuilder->getSQL();

        // Assert that the generated SQL matches our expectations
        $expectedSql = 'UPDATE test_emails e SET read_count = read_count + 1 WHERE (e.id = :emailId) AND (e.id NOT IN (SELECT es.email_id FROM test_email_stats es WHERE (es.id = :statId) AND (es.is_read = 1)))';
        $this->assertEquals($expectedSql, $generatedSql);
    }

    public function testIncrementReadWithVariant(): void
    {
        $this->connection
            ->expects($this->exactly(1))
            ->method('executeStatement')
            ->willReturn(1);

        $this->repo->incrementRead(11, '21', true);
        $generatedSql = $this->queryBuilder->getSQL();

        // Assert that the generated SQL matches our expectations
        $expectedSql = 'UPDATE test_emails e SET read_count = read_count + 1, variant_read_count = variant_read_count + 1 WHERE (e.id = :emailId) AND (e.id NOT IN (SELECT es.email_id FROM test_email_stats es WHERE (es.id = :statId) AND (es.is_read = 1)))';
        $this->assertEquals($expectedSql, $generatedSql);
    }

    public function testUpCountWithTwoErrors(): void
    {
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

        $this->repo->incrementRead(45, '616');
    }

    public function testUpCountWithFourErrors(): void
    {
        $this->connection
            ->expects($this->exactly(3))
            ->method('executeStatement')
            ->will($this->throwException(new DBALException()));

        $this->expectException(DBALException::class);
        $this->repo->incrementRead(45, '616');
    }
}
