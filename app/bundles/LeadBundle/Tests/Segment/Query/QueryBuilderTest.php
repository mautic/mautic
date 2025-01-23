<?php

/** @noinspection SqlResolve SqlAggregates */

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private QueryBuilder $queryBuilder;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection    = $this->createConnectionFake();
        $this->queryBuilder  = new QueryBuilder($this->connection);
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
            ->set('enabled', '1')
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

    public function testSetParametersPairsNonArray(): void
    {
        $queryBuilder = $this->queryBuilder->setParametersPairs('one', 'first');
        Assert::assertSame($queryBuilder, $this->queryBuilder);
        $this->queryBuilder->setParametersPairs('two', 'second');
        $this->queryBuilder->setParametersPairs(':three', 'third');

        Assert::assertSame([
            'one'   => 'first',
            'two'   => 'second',
            'three' => 'third',
        ], $this->queryBuilder->getParameters());
    }

    public function testSetParametersPairsWithArray(): void
    {
        $queryBuilder     = $this->queryBuilder->setParametersPairs(['one', 'three', ':five'], ['first', 'third', 'fifth']);
        Assert::assertSame($queryBuilder, $this->queryBuilder);
        Assert::assertSame([
            'one'   => 'first',
            'three' => 'third',
            'five'  => 'fifth',
        ], $this->queryBuilder->getParameters());
    }

    public function testGetTableAlias(): void
    {
        $this->queryBuilder->select('1')
            ->from('tableFrom', 'f')
            ->leftJoin('f', 'leftJoinTable', 'l', 'f.id = l.fid')
            ->rightJoin('l', 'rightJoinTable', 'r', 'l.id = r.lid')
            ->innerJoin('f', 'innerJoinTable', 'i', 'f.id = i.fid')
            ->where('t.enabled = 1')
            ->groupBy('t.type')
            ->having('t.salary > 5000')
            ->orderBy('t.id', 'DESC')
            ->setFirstResult(30)
            ->setMaxResults(10);

        Assert::assertFalse($this->queryBuilder->getTableAlias('nonExistent'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('nonExistent', 'inner'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('nonExistent', 'left'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('nonExistent', 'right'));

        Assert::assertSame('f', $this->queryBuilder->getTableAlias('tableFrom'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('tableFrom', 'inner'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('tableFrom', 'left'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('tableFrom', 'right'));

        Assert::assertSame('l', $this->queryBuilder->getTableAlias('leftJoinTable'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('leftJoinTable', 'inner'));
        Assert::assertSame('l', $this->queryBuilder->getTableAlias('leftJoinTable', 'left'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('leftJoinTable', 'right'));

        Assert::assertSame('r', $this->queryBuilder->getTableAlias('rightJoinTable'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('rightJoinTable', 'inner'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('rightJoinTable', 'left'));
        Assert::assertSame('r', $this->queryBuilder->getTableAlias('rightJoinTable', 'right'));

        Assert::assertSame('i', $this->queryBuilder->getTableAlias('innerJoinTable'));
        Assert::assertSame('i', $this->queryBuilder->getTableAlias('innerJoinTable', 'inner'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('innerJoinTable', 'left'));
        Assert::assertFalse($this->queryBuilder->getTableAlias('innerJoinTable', 'right'));
    }

    public function testGetTableJoins(): void
    {
        $this->queryBuilder->select('1')
            ->from('tableFrom', 'f')
            ->leftJoin('f', 'leftJoinTable', 'l', 'f.id = l.fid')
            ->rightJoin('l', 'rightJoinTable', 'r', 'l.id = r.lid')
            ->innerJoin('f', 'innerJoinTable', 'i', 'f.id = i.fid')
            ->innerJoin('f', 'innerJoinTable', 'i2', 'f.id = i2.fid')
            ->where('t.enabled = 1')
            ->groupBy('t.type')
            ->having('t.salary > 5000')
            ->orderBy('t.id', 'DESC')
            ->setFirstResult(30)
            ->setMaxResults(10);

        Assert::assertSame([], $this->queryBuilder->getTableJoins('nonExistent'));
        Assert::assertSame([], $this->queryBuilder->getTableJoins('tableFrom'));
        Assert::assertSame([
            [
                'joinType'      => 'left',
                'joinTable'     => 'leftJoinTable',
                'joinAlias'     => 'l',
                'joinCondition' => 'f.id = l.fid',
            ],
        ], $this->queryBuilder->getTableJoins('leftJoinTable'));
        Assert::assertSame([
            [
                'joinType'      => 'right',
                'joinTable'     => 'rightJoinTable',
                'joinAlias'     => 'r',
                'joinCondition' => 'l.id = r.lid',
            ],
        ], $this->queryBuilder->getTableJoins('rightJoinTable'));
        Assert::assertSame([
            [
                'joinType'      => 'inner',
                'joinTable'     => 'innerJoinTable',
                'joinAlias'     => 'i',
                'joinCondition' => 'f.id = i.fid',
            ],
            [
                'joinType'      => 'inner',
                'joinTable'     => 'innerJoinTable',
                'joinAlias'     => 'i2',
                'joinCondition' => 'f.id = i2.fid',
            ],
        ], $this->queryBuilder->getTableJoins('innerJoinTable'));
    }

    public function testGuessPrimaryLeadContactIdColumnWithOrphanedLeads(): void
    {
        $this->queryBuilder->select('1')
            ->from('lead_lists_leads', 'orp');

        Assert::assertSame('orp.lead_id', $this->queryBuilder->guessPrimaryLeadContactIdColumn());
    }

    public function testGuessPrimaryLeadContactIdColumnWithoutJoins(): void
    {
        $this->queryBuilder->select('1')
            ->from('leads', 'l');

        Assert::assertSame('l.id', $this->queryBuilder->guessPrimaryLeadContactIdColumn());
    }

    public function testGuessPrimaryLeadContactIdColumnWithNonRightJoin(): void
    {
        $this->queryBuilder->select('1')
            ->from('leads', 'l')
            ->leftJoin('l', 'leftJoinTable', 'lj', 'l.id = lj.lid')
            ->innerJoin('l', 'innerJoinTable', 'ij', 'l.id = ij.lid');

        Assert::assertSame('l.id', $this->queryBuilder->guessPrimaryLeadContactIdColumn());
    }

    public function testGuessPrimaryLeadContactIdColumnWithNonMatchingRightJoin(): void
    {
        $this->queryBuilder->select('1')
            ->from('leads', 'l')
            ->rightJoin('l', 'rightJoinTable', 'r', 'l.name = r.name');

        Assert::assertSame('l.id', $this->queryBuilder->guessPrimaryLeadContactIdColumn());
    }

    public function testGuessPrimaryLeadContactIdColumnWithMatchingRightJoin(): void
    {
        $this->queryBuilder->select('1')
            ->from('leads', 'l')
            ->rightJoin('l', 'rightJoinTable', 'r', 'l.id = r.lid');

        Assert::assertSame('r.lid', $this->queryBuilder->guessPrimaryLeadContactIdColumn());
    }

    public function testIsJoinTable(): void
    {
        $this->queryBuilder->select('1')
            ->from('leads', 'l')
            ->leftJoin('l', 'leftJoinTable', 'lj', 'l.id = lj.lid')
            ->rightJoin('l', 'rightJoinTable', 'rj', 'l.id = rj.lid')
            ->innerJoin('l', 'innerJoinTable', 'ij', 'l.id = ij.lid');

        Assert::assertFalse($this->queryBuilder->isJoinTable('nonExistent'));
        Assert::assertFalse($this->queryBuilder->isJoinTable('leads'));
        Assert::assertTrue($this->queryBuilder->isJoinTable('leftJoinTable'));
        Assert::assertTrue($this->queryBuilder->isJoinTable('rightJoinTable'));
        Assert::assertTrue($this->queryBuilder->isJoinTable('innerJoinTable'));
    }

    public function testGetDebugOutput(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->leftJoin('t', 'table2', 'j', 't.id = j.fid')
            ->where('t.enabled = :enabled')
            ->andWhere('t.state IN (:states)')
            ->groupBy('t.type')
            ->having('t.salary > :salary AND t.flag = :flag')
            ->orderBy('t.id', 'DESC')
            ->setParameter('enabled', true)
            ->setParameter(':salary', 5000)
            ->setParameter('states', ['new', 'active'], ArrayParameterType::STRING)
            ->setParameter('flag', 'internal')
            ->setFirstResult(30)
            ->setMaxResults(10);

        Assert::assertSame("SELECT t.name FROM table1 t LEFT JOIN table2 j ON t.id = j.fid WHERE (t.enabled = 1) AND (t.state IN ('new', 'active')) GROUP BY t.type HAVING t.salary > 5000 AND t.flag = 'internal' ORDER BY t.id DESC LIMIT 10 OFFSET 30", $this->queryBuilder->getDebugOutput());
    }

    public function testHasLogicStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        Assert::assertFalse($this->queryBuilder->hasLogicStack());

        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        Assert::assertTrue($this->queryBuilder->hasLogicStack());
    }

    public function testGetLogicStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        Assert::assertSame([], $this->queryBuilder->getLogicStack());

        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->lt('a.salary', 3000), 'AND');
        Assert::assertSame([
            'a.name = John',
            'a.salary < 3000',
        ], $this->queryBuilder->getLogicStack());
    }

    public function testPopLogicStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->lt('a.salary', 3000), 'AND');
        Assert::assertSame([
            'a.name = John',
            'a.salary < 3000',
        ], $this->queryBuilder->popLogicStack());
        Assert::assertSame([], $this->queryBuilder->getLogicStack());
    }

    public function testAddLogicOrWithEmptyWhere(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        Assert::assertSame([], $this->queryBuilder->getLogicStack());
        $this->assertSQL('SELECT t.name FROM table1 t WHERE a.name = John');
        $this->queryBuilder->applyStackLogic();
        $this->assertSQL('SELECT t.name FROM table1 t WHERE a.name = John');
    }

    public function testAddLogicOrWithExistingWhereWithEmptyStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        Assert::assertSame(['a.name = John'], $this->queryBuilder->getLogicStack());
        $this->assertSQL('SELECT t.name FROM table1 t WHERE t.enabled = 1');
        $this->queryBuilder->applyStackLogic();
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) OR (a.name = John)');
    }

    public function testAddLogicOrWithExistingWhereWithExistingStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.flag', 'active'), 'OR');
        Assert::assertSame(['a.flag = active'], $this->queryBuilder->getLogicStack());
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) OR (a.name = John)');
        $this->queryBuilder->applyStackLogic();
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) OR (a.name = John) OR (a.flag = active)');
    }

    public function testAddLogicAndWithEmptyWhere(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'AND');
        Assert::assertSame([], $this->queryBuilder->getLogicStack());
        $this->assertSQL('SELECT t.name FROM table1 t WHERE a.name = John');
        $this->queryBuilder->applyStackLogic();
        $this->assertSQL('SELECT t.name FROM table1 t WHERE a.name = John');
    }

    public function testAddLogicAndWithExistingWhereWithEmptyStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'AND');
        Assert::assertSame([], $this->queryBuilder->getLogicStack());
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) AND (a.name = John)');
        $this->queryBuilder->applyStackLogic();
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) AND (a.name = John)');
    }

    public function testAddLogicAndWithExistingWhereWithExistingStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'OR');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.flag', 'active'), 'AND');
        Assert::assertSame([
            'a.name = John',
            'a.flag = active',
        ], $this->queryBuilder->getLogicStack());
        $this->assertSQL('SELECT t.name FROM table1 t WHERE t.enabled = 1');
        $this->queryBuilder->applyStackLogic();
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) OR ((a.name = John) AND (a.flag = active))');
    }

    public function testApplyStackLogicWithEmptyStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $queryBuilder = $this->queryBuilder->applyStackLogic();
        Assert::assertSame($queryBuilder, $this->queryBuilder);
        $this->assertSQL('SELECT t.name FROM table1 t WHERE t.enabled = 1');
    }

    public function testApplyStackLogicWithExistingStack(): void
    {
        $this->queryBuilder->select('t.name')
            ->from('table1', 't')
            ->where('t.enabled = 1');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.name', 'John'), 'AND');
        $this->queryBuilder->addLogic($this->queryBuilder->expr()->eq('a.flag', 'active'), 'AND');
        $queryBuilder = $this->queryBuilder->applyStackLogic();
        Assert::assertSame($queryBuilder, $this->queryBuilder);
        $this->assertSQL('SELECT t.name FROM table1 t WHERE (t.enabled = 1) AND (a.name = John) AND (a.flag = active)');
    }

    private function assertSQL(string $sql, int $repeat = 1): void
    {
        for ($i = 0; $i < $repeat; ++$i) {
            Assert::assertSame($sql, $this->queryBuilder->getSQL());
        }
    }

    private function createConnectionFake(): Connection
    {
        return new class([], $this->createMock(Driver::class)) extends Connection {
            public function getDatabasePlatform()
            {
                return new MySQLPlatform();
            }
        };
    }
}
