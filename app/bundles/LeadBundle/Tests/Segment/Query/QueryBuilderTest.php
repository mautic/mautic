<?php

/** @noinspection SqlResolve SqlAggregates */

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = new QueryBuilder($this->createConnectionFake());
    }

    public function testExpr(): void
    {
        $expr = $this->queryBuilder->expr();

        Assert::assertInstanceOf(ExpressionBuilder::class, $expr);
        Assert::assertSame($expr, $this->queryBuilder->expr());
    }

    public function testSetParameter(): void
    {
        $queryBuilder = $this->queryBuilder->setParameter(':one', 'first');
        Assert::assertSame($queryBuilder, $this->queryBuilder);
        $this->queryBuilder->setParameter('two', true);
        $this->queryBuilder->setParameter(':three', false);
        $this->queryBuilder->setParameter(4, 'fourth');

        Assert::assertSame([
            'one'   => 'first',
            'two'   => 1,
            'three' => 0,
            4       => 'fourth',
        ], $this->queryBuilder->getParameters());
    }

    public function testSetQueryPart(): void
    {
        $this->queryBuilder->select('t.name', 't.enabled')
            ->distinct()
            ->from('table1', 't')
            ->leftJoin('t', 'table2', 'j', 't.id = j.tid')
            ->where('t.enabled = 1');

        $queryBuilder = $this->queryBuilder->setQueryPart('select', 't.name');
        Assert::assertSame($queryBuilder, $this->queryBuilder);
        $this->queryBuilder->setQueryPart('where', 't.enabled = 0');
        $this->queryBuilder->setQueryPart('groupBy', 'j.code');
        $this->queryBuilder->setQueryPart('distinct', null);

        $this->assertSQL('SELECT t.name FROM table1 t LEFT JOIN table2 j ON t.id = j.tid WHERE t.enabled = 0 GROUP BY j.code');
    }

    public function testGetSQLSelectSimple(): void
    {
        $this->queryBuilder->select('1')
            ->from('table1');
        $this->assertSQL('SELECT 1 FROM table1', 2);
    }

    public function testGetSQLSelectComplex(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->leftJoin('t', 'table2', 'j', 't.id = j.fid')
            ->where('t.enabled = 1')
            ->groupBy('t.type')
            ->having('t.salary > 5000')
            ->orderBy('t.id', 'DESC')
            ->setFirstResult(30)
            ->setMaxResults(10);
        $this->assertSQL('SELECT t.name FROM table1 t LEFT JOIN table2 j ON t.id = j.fid WHERE t.enabled = 1 GROUP BY t.type HAVING t.salary > 5000 ORDER BY t.id DESC LIMIT 10 OFFSET 30', 2);
    }

    public function testGetSQLSelectHint(): void
    {
        $this->queryBuilder->select('1')
            ->add('from', [
                'table' => 'table1',
                'alias' => 't',
                'hint'  => 'USE INDEX (`PRIMARY`)',
            ], true)
            ->where('t.enabled = 0');
        $this->assertSQL('SELECT 1 FROM table1 t USE INDEX (`PRIMARY`) WHERE t.enabled = 0', 2);
    }

    public function testGetSQLInsert(): void
    {
        $this->queryBuilder->insert('table1')
            ->values(['name' => 'Jack', 'enabled' => 1]);
        $this->assertSQL('INSERT INTO table1 (name, enabled) VALUES(Jack, 1)', 2);
    }

    public function testGetSQLUpdate(): void
    {
        $this->queryBuilder->update('table1')
            ->set('enabled', 1)
            ->where('enabled = 0');
        $this->assertSQL('UPDATE table1 SET enabled = 1 WHERE enabled = 0', 2);
    }

    public function testGetSQLDelete(): void
    {
        $this->queryBuilder->delete('table1')
            ->where('enabled = 1');
        $this->assertSQL('DELETE FROM table1 WHERE enabled = 1', 2);
    }

    public function testGetJoinCondition(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 'l')
            ->leftJoin('l', 'table2', 'j', 'l.id = j.fid');

        Assert::assertSame('l.id = j.fid', $this->queryBuilder->getJoinCondition('j'));
        Assert::assertFalse($this->queryBuilder->getJoinCondition('k'));
    }

    public function testAddJoinCondition(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->leftJoin('t', 'table2', 'j', 't.id = j.fid');
        $this->queryBuilder->addJoinCondition('j', $this->queryBuilder->expr()->eq('j.removed', 1));

        $this->assertSQL('SELECT t.name FROM table1 t LEFT JOIN table2 j ON t.id = j.fid and (j.removed = 1)');
    }

    public function testAddJoinConditionNonExistentJoin(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->leftJoin('t', 'table2', 'j', 't.id = j.fid');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Inserting condition to nonexistent join x');
        $this->queryBuilder->addJoinCondition('x', $this->queryBuilder->expr()->eq('j.removed', 1));
    }

    public function testReplaceJoinCondition(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 'l')
            ->leftJoin('l', 'table2', 'j', 'l.id = j.fid');
        $this->queryBuilder->replaceJoinCondition('j', $this->queryBuilder->expr()->eq('j.removed', 1));

        $this->assertSQL('SELECT t.name FROM table1 l LEFT JOIN table2 j ON j.removed = 1');
    }

    private function assertSQL(string $sql, int $repeat = 1): void
    {
        for ($i = 0; $i < $repeat; ++$i) {
            Assert::assertSame($sql, $this->queryBuilder->getSQL());
        }
    }

    private function createConnectionFake(): Connection
    {
        return new class() extends Connection {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
            }

            public function getDatabasePlatform()
            {
                return new MySqlPlatform();
            }
        };
    }
}
