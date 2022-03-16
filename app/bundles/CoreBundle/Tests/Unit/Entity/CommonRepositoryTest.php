<?php

namespace Mautic\CoreBundle\Tests\Unit\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

class CommonRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CommonRepository
     */
    private $repo;

    /**
     * @var QueryBuilder
     */
    private $qb;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private $connectionMock;

    /**
     * Sets up objects used in the tests.
     */
    protected function setUp(): void
    {
        $emMock = $this->getMockBuilder(EntityManager::class)
            ->addMethods(['none'])
            ->disableOriginalConstructor()
            ->getMock();

        $metaMock = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo           = new CommonRepository($emMock, $metaMock);
        $this->qb             = new QueryBuilder($emMock);
        $this->connectionMock = $this->createMock(Connection::class);
        $this->connectionMock->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($this->connectionMock));
    }

    /**
     * @testdox Check that the query is being build without providing any order statements
     *
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildClauses
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildOrderByClause
     */
    public function testBuildingQueryWithUndefinedOrder()
    {
        $this->callProtectedMethod('buildClauses', [$this->qb, []]);
        $this->assertSame('SELECT e', (string) $this->qb);
    }

    /**
     * @testdox Check that providing orderBy and orderByDir builds the query correctly
     *
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildClauses
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildOrderByClause
     */
    public function testBuildingQueryWithBasicOrder()
    {
        $args = [
            'orderBy'    => 'e.someCol',
            'orderByDir' => 'DESC',
        ];
        $this->callProtectedMethod('buildClauses', [$this->qb, $args]);
        $this->assertSame('SELECT e ORDER BY e.someCol DESC', (string) $this->qb);
    }

    /**
     * @testdox Check that array of ORDER statements is correct
     *
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildClauses
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildOrderByClause
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::buildOrderByClauseFromArray
     */
    public function testBuildingQueryWithOrderArray()
    {
        $args = [
            'filter' => [
                'order' => [
                    [
                        'col' => 'e.someCol',
                        'dir' => 'DESC',
                    ],
                ],
            ],
        ];
        $this->callProtectedMethod('buildClauses', [$this->qb, $args]);
        $this->assertSame('SELECT e ORDER BY e.someCol DESC', (string) $this->qb);
    }

    /**
     * @testdox Check that order by validation will allow dots in the column name
     *
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::validateOrderByClause
     */
    public function testValidateOrderByClauseWithColContainingAliasWillNotRemoveTheDot()
    {
        $provided = [
            'col' => 'e.someCol',
            'dir' => 'DESC',
        ];

        $expected = [
            'col' => 'e.someCol',
            'dir' => 'DESC',
        ];

        $result = $this->callProtectedMethod('validateOrderByClause', [$provided]);
        $this->assertSame($expected, $result);
    }

    /**
     * @testdox Check that order validation will remove funky characters that can be used in an attack
     *
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::validateOrderByClause
     */
    public function testValidateOrderByClauseWillRemoveFunkyChars()
    {
        $provided = [
            'col' => '" DELETE * FROM users',
        ];

        $expected = [
            'col' => 'DELETEFROMusers',
            'dir' => 'ASC',
        ];

        $result = $this->callProtectedMethod('validateOrderByClause', [$provided]);
        $this->assertSame($expected, $result);
    }

    /**
     * @testdox Check that order validation will throw an exception if column name is missing
     *
     * @covers  \Mautic\CoreBundle\Entity\CommonRepository::validateOrderByClause
     */
    public function testValidateOrderByClauseWithMissingCol()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->callProtectedMethod('validateOrderByClause', [[]]);
    }

    /**
     * Copy of.
     *
     * @see \Mautic\LeadBundle\Tests\Segment\RandomParameterNameTest::testGenerateRandomParameterName
     */
    public function testGenerateRandomParameterName()
    {
        $expectedValues = [
            'par0',
            'par1',
            'par2',
            'par3',
            'par4',
            'par5',
            'par6',
            'par7',
            'par8',
            'par9',
            'para',
            'parb',
            'parc',
            'pard',
            'pare',
            'parf',
            'parg',
            'parh',
            'pari',
            'parj',
            'park',
            'parl',
            'parm',
            'parn',
            'paro',
            'parp',
            'parq',
            'parr',
            'pars',
            'part',
            'paru',
            'parv',
            'parw',
            'parx',
            'pary',
            'parz',
            'par10',
            'par11',
        ];

        foreach ($expectedValues as $expectedValue) {
            self::assertSame($expectedValue, $this->repo->generateRandomParameterName());
        }
    }

    /**
     * Calls a protected method from CommonRepository with provided argumetns.
     *
     * @param string $method name
     * @param array  $args   added to the method
     *
     * @return mixed result of the method
     *
     * @throws \ReflectionException
     */
    private function callProtectedMethod($method, $args)
    {
        $reflection = new \ReflectionClass(CommonRepository::class);
        $method     = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->repo, $args);
    }

    public function testArgumentCSVArray()
    {
        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.user_id',
                'expr'  => 'in',
                'val'   => '"1","2","3","4"',
            ],
        ];
        $matchArgs = explode(',', $args[0]['val']);
        array_walk($matchArgs, function (&$element) { $element = trim($element, '"'); });

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith('l.user_id IN (', (string) $qb->getQueryPart('where'));
        $parameters = $qb->getParameters();
        $this->assertEquals($matchArgs, array_shift($parameters));

        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.user_id',
                'expr'  => 'notIn',
                'val'   => '"1","2","3","4"',
            ],
        ];
        $matchArgs = explode(',', $args[0]['val']);
        array_walk($matchArgs, function (&$element) { $element = trim($element, '"'); });

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith('l.user_id NOT IN (', (string) $qb->getQueryPart('where'));
        $parameters = $qb->getParameters();
        $this->assertEquals($matchArgs, array_shift($parameters));
    }

    public function testNoEnquotedArgumentCSVArray()
    {
        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.user_id',
                'expr'  => 'in',
                'val'   => '1,2,3,4',
            ],
        ];
        $matchArgs = explode(',', $args[0]['val']);
        array_walk($matchArgs, function (&$element) { $element = trim($element, '"'); });

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith('l.user_id IN (', (string) $qb->getQueryPart('where'));

        $parameters = $qb->getParameters();
        $this->assertEquals($matchArgs, array_shift($parameters));

        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.user_id',
                'expr'  => 'notIn',
                'val'   => '1,2,3,4',
            ],
        ];
        $matchArgs = explode(',', $args[0]['val']);
        array_walk($matchArgs, function (&$element) { $element = trim($element, '"'); });

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith('l.user_id NOT IN (', (string) $qb->getQueryPart('where'));

        $parameters = $qb->getParameters();
        $this->assertEquals($matchArgs, array_shift($parameters));
    }

    public function testNoEnquotedStringArgumentCSVArray()
    {
        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.firstname',
                'expr'  => 'in',
                'val'   => 'jan,alan,don,john',
            ],
        ];
        $matchArgs = explode(',', $args[0]['val']);
        array_walk($matchArgs, function (&$element) { $element = trim($element, '"'); });

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith($args[0]['col'].' IN (', (string) $qb->getQueryPart('where'));

        $parameters = $qb->getParameters();
        $this->assertEquals($matchArgs, array_shift($parameters));

        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.firstname',
                'expr'  => 'notIn',
                'val'   => 'jan,alan,don,john',
            ],
        ];
        $matchArgs = explode(',', $args[0]['val']);
        array_walk($matchArgs, function (&$element) { $element = trim($element, '"'); });

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith($args[0]['col'].' NOT IN (', (string) $qb->getQueryPart('where'));

        $parameters = $qb->getParameters();
        $this->assertEquals($matchArgs, array_shift($parameters));
    }

    public function testStringArgumentInterpretedAsSingleValueEnquoted()
    {
        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.firstname',
                'expr'  => 'in',
                'val'   => '"jan,alan,don,john"',
            ],
        ];

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith($args[0]['col'].' = ', (string) $qb->getQueryPart('where'));
        $parameters = $qb->getParameters();
        $this->assertEquals(trim($args[0]['val'], '"'), array_shift($parameters));

        $qb   = new \Doctrine\DBAL\Query\QueryBuilder($this->connectionMock);
        $args = [
            [
                'col'   => 'l.firstname',
                'expr'  => 'notIn',
                'val'   => '"jan,alan,don,john"',
            ],
        ];

        $this->callBuildWhereClauseFromArray($qb, $args);

        $this->assertStringStartsWith($args[0]['col'].' <> ', (string) $qb->getQueryPart('where'));
        $parameters = $qb->getParameters();
        $this->assertEquals(trim($args[0]['val'], '"'), array_shift($parameters));
    }

    private function callBuildWhereClauseFromArray($qb, $args)
    {
        $reflection = new \ReflectionClass(CommonRepository::class);
        $method     = $reflection->getMethod('buildWhereClauseFromArray');
        $method->setAccessible(true);

        return $method->invokeArgs($this->repo, [$qb, $args]);
    }
}
