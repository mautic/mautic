<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

class CommonRepositoryTest extends \PHPUnit_Framework_TestCase
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
     * Sets up objects used in the tests.
     */
    protected function setUp()
    {
        $emMock = $this->getMockBuilder(EntityManager::class)
            ->setMethods(['none'])
            ->disableOriginalConstructor()
            ->getMock();

        $metaMock = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = new CommonRepository($emMock, $metaMock);
        $this->qb   = new QueryBuilder($emMock);
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
     * Calls a protected method from CommonRepository with provided argumetns.
     *
     * @param string $method name
     * @param array  $args   added to the method
     *
     * @return mixed result of the method
     */
    private function callProtectedMethod($method, $args)
    {
        $reflection = new \ReflectionClass(CommonRepository::class);
        $method     = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->repo, $args);
    }
}
