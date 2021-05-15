<?php

declare(strict_types=1);

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\EmailRepository;
use PHPUnit\Framework\MockObject\MockObject;

class EmailRepositoryUpCountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Connection
     */
    private $mockConnection;

    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilderMock;

    /**
     * @var MockObject|EntityManager
     */
    private $em;

    /**
     * @var MockObject|ClassMetadata
     */
    private $cm;

    /**
     * @var EmailRepository
     */
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->mockConnection   = $this->createMock(Connection::class);
        $this->em               = $this->createMock(EntityManager::class);
        $this->cm               = $this->createMock(ClassMetadata::class);
        $this->repo             = new EmailRepository($this->em, $this->cm);

        $this->mockConnection->method('createQueryBuilder')->willReturn($this->queryBuilderMock);
        $this->em->method('getConnection')->willReturn($this->mockConnection);
    }

    public function testUpCountWithNoIncrease(): void
    {
        $this->queryBuilderMock->expects($this->never())
            ->method('update');

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
            ->method('execute');

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
            ->method('execute');

        $this->repo->upCount(45, 'read', 2, true);
    }

    public function testUpCountWithTwoErrors(): void
    {
        $this->queryBuilderMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new DBALException()),
                $this->throwException(new DBALException())
            );

        $this->repo->upCount(45);
    }

    public function testUpCountWithFourErrors(): void
    {
        $this->queryBuilderMock->expects($this->exactly(3))
            ->method('execute')
            ->will($this->throwException(new DBALException()));

        $this->expectException(DBALException::class);
        $this->repo->upCount(45);
    }
}
