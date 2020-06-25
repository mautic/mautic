<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Portability\Statement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use PHPUnit\Framework\MockObject\MockObject;

class LeadFieldRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|ClassMetadata
     */
    private $classMetadata;

    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var LeadFieldRepository
     */
    private $repository;

    protected function setUp()
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->repository    = new LeadFieldRepository($this->entityManager, $this->classMetadata);
    }

    public function testCompareDateValueForContactField()
    {
        $contactId        = 12;
        $fieldAlias       = 'date_field';
        $value            = '2019-04-30';
        $builderAlias     = $this->createMock(QueryBuilder::class);
        $builderCompare   = $this->createMock(QueryBuilder::class);
        $statementAlias   = $this->createMock(Statement::class);
        $statementCompare = $this->createMock(Statement::class);
        $exprCompare      = $this->createMock(ExpressionBuilder::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($builderCompare, $builderAlias));

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('setParameter')
            ->with('object', 'company')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('orderBy')
            ->with('f.field_order', 'ASC')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('execute')
            ->willReturn($statementAlias);

        // No company column found. Therefore it's a contact field.
        $statementAlias->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $exprCompare->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['l.id', ':lead'],
                ['l.date_field', ':value'] // See? It's a contact column.
            );

        $builderCompare->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderCompare->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['lead', $contactId],
                ['value', $value]
            )
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('execute')
            ->willReturn($statementCompare);

        // No contact ID was found by the value so the result should be false.
        $statementCompare->expects($this->once())
            ->method('fetch')
            ->willReturn([]);

        $this->assertFalse($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }

    public function testCompareDateValueForCompanyField()
    {
        $contactId        = 12;
        $fieldAlias       = 'date_field';
        $value            = '2019-04-30';
        $builderAlias     = $this->createMock(QueryBuilder::class);
        $builderCompare   = $this->createMock(QueryBuilder::class);
        $statementAlias   = $this->createMock(Statement::class);
        $statementCompare = $this->createMock(Statement::class);
        $exprCompare      = $this->createMock(ExpressionBuilder::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $builderAlias->method('expr')->willReturn(new ExpressionBuilder($this->connection));
        $builderCompare->method('expr')->willReturn($exprCompare);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($builderCompare, $builderAlias));

        $builderAlias->expects($this->once())
            ->method('select')
            ->with('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('setParameter')
            ->with('object', 'company')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('orderBy')
            ->with('f.field_order', 'ASC')
            ->willReturnSelf();

        $builderAlias->expects($this->once())
            ->method('execute')
            ->willReturn($statementAlias);

        // A company column found. Therefore it's a company field.
        $statementAlias->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['alias' => $fieldAlias]]);

        $exprCompare->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['l.id', ':lead'],
                ['company.date_field', ':value'] // See? It's a company column.
            );

        $builderCompare->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                ['l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', 'l.id = companies_lead.lead_id'],
                ['companies_lead', MAUTIC_TABLE_PREFIX.'companies', 'company', 'companies_lead.company_id = company.id']
            );

        $builderCompare->expects($this->once())
            ->method('select')
            ->with('l.id')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $builderCompare->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['lead', $contactId],
                ['value', $value]
            )
            ->willReturnSelf();

        $builderCompare->expects($this->once())
            ->method('execute')
            ->willReturn($statementCompare);

        // A contact ID was found by the value so the result should be true.
        $statementCompare->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => 456]);

        $this->assertTrue($this->repository->compareDateValue($contactId, $fieldAlias, $value));
    }
}
