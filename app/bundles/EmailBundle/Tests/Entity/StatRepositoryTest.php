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

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Portability\Statement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\StatRepository;
use PHPUnit\Framework\TestCase;

class StatRepositoryTest extends TestCase
{
    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var MockObject|ClassMetadata
     */
    private $classMetadata;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var StatRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->repository    = new StatRepository($this->entityManager, $this->classMetadata);
    }

    public function testGetSentCountForContactsFromEmail()
    {
        $emailIds           = [1, 3];
        $contactIds         = [2, 4];
        $result             = [
            ['lead_id' => 1, 'sent_count' => 10],
        ];

        $builderAlias     = $this->createMock(QueryBuilder::class);
        $statementAlias   = $this->createMock(Statement::class);
        $emailMock        = $this->createMock(Email::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderAlias->expects($this->once())
            ->method('execute')
            ->willReturn($statementAlias);
        $statementAlias->expects($this->once())
            ->method('fetchAll')
            ->willReturn($result);

        $this->connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($builderAlias);

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('count(s.id) as sent_count, s.lead_id')
            ->willReturnSelf();

        $builderAlias->expects($this->exactly(3))
            ->method('andWhere')
            ->willReturnSelf();

        $builderAlias->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                [':emailIds', $emailIds],
                [':contacts', $contactIds]
            )
            ->willReturnSelf();

        $emailMock->expects($this->once())
        ->method('getRelatedEntityIds')
        ->willReturn($emailIds);

        $this->assertEquals([1 => 10], $this->repository->getSentCountForContactsFromEmail($contactIds, $emailMock));
    }
}
