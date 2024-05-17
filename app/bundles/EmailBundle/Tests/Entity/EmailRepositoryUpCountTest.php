<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use PHPUnit\Framework\MockObject\MockObject;

class EmailRepositoryUpCountTest extends \PHPUnit\Framework\TestCase
{
    use RepositoryConfiguratorTrait;

    /**
     * @var MockObject|QueryBuilder
     */
    private MockObject $queryBuilderMock;

    private EmailRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->repo             = $this->configureRepository(Email::class);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
    }

    public function testUpCountWithNoIncrease(): void
    {
        $this->queryBuilderMock->expects($this->never())
            ->method('update');

        /** @phpstan-ignore-next-line */
        $this->repo->upCount(45, 'sent', 0);
    }

    public function testUpCountWithId(): void
    {
        $this->queryBuilderMock->expects($this->once())
            ->method('update')
            ->with(MAUTIC_TABLE_PREFIX.'emails');

        $this->queryBuilderMock->expects($this->once())
            ->method('set')
            ->with('sent_count', 'sent_count + 1');

        $this->queryBuilderMock->expects($this->once())
            ->method('where')
            ->with('id = 45');

        $this->queryBuilderMock->expects($this->once())
            ->method('executeStatement');

        /** @phpstan-ignore-next-line */
        $this->repo->upCount(45);
    }

    public function testUpCountWithVariant(): void
    {
        $this->queryBuilderMock->expects($this->once())
            ->method('update')
            ->with(MAUTIC_TABLE_PREFIX.'emails');

        $this->queryBuilderMock->method('set')
            ->withConsecutive(
                ['read_count', 'read_count + 2'],
                ['variant_read_count', 'variant_read_count + 2']
            );

        $this->queryBuilderMock->expects($this->once())
            ->method('where')
            ->with('id = 45');

        $this->queryBuilderMock->expects($this->once())
            ->method('executeStatement');

        /** @phpstan-ignore-next-line */
        $this->repo->upCount(45, 'read', 2, true);
    }

    public function testUpCountWithTwoErrors(): void
    {
        $this->queryBuilderMock->expects($this->exactly(3))
            ->method('executeStatement')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DBALException()),
                $this->throwException(new DBALException()),
                0
            );

        /** @phpstan-ignore-next-line */
        $this->repo->upCount(45);
    }

    public function testUpCountWithFourErrors(): void
    {
        $this->queryBuilderMock->expects($this->exactly(3))
            ->method('executeStatement')
            ->will($this->throwException(new DBALException()));

        $this->expectException(DBALException::class);
        /** @phpstan-ignore-next-line */
        $this->repo->upCount(45);
    }
}
