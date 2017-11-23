<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadListRepository;

class LeadListRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testIncludeSegmentFilterWithFiltersAppendInOrGroups()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod();

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'in',
                    'field'    => 'leadlist',
                    'object'   => 'lead',
                    'type'     => 'leadlist',
                    'display'  => null,
                    'filter'   => [1, 2],
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr   = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);
        $string = (string) $expr;

        $found = preg_match_all('/EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'leads .*? LEFT JOIN '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(2, $found, $string);

        // Segment filters combined by OR to keep consistent behavior with the use of leadlist_id IN (1,2,3)
        $found = preg_match_all('/OR \(EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'leads .*? LEFT JOIN '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(1, $found, $string);

        $found = preg_match_all('/\(l.email = :(.*?)\)/', $string, $matches);
        $this->assertEquals(2, $found, $string);

        $this->assertTrue(isset($parameters[$matches[1][0]]) && $parameters[$matches[1][0]] = 'blah@blah.com', $string);
        $this->assertTrue(isset($parameters[$matches[1][1]]) && $parameters[$matches[1][1]] = 'blah2@blah.com', $string);
    }

    public function testIncludeSegmentFilterWithOutFiltersAppendMembershipSubquery()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'in',
                    'field'    => 'leadlist',
                    'object'   => 'lead',
                    'type'     => 'leadlist',
                    'display'  => null,
                    'filter'   => [1, 2],
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;

        // Two segments included
        $found = preg_match_all('/EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(2, $found, $string);

        // Segment filters combined by OR to keep consistent behavior with the use of leadlist_id IN (1,2,3)
        $found = preg_match_all('/OR \(EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(1, $found, $string);
    }

    public function testExcludeSegmentFilterWithFiltersAppendNotExistsSubQuery()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod();

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => '!in',
                    'field'    => 'leadlist',
                    'object'   => 'lead',
                    'type'     => 'leadlist',
                    'display'  => null,
                    'filter'   => [1, 2],
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr   = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);
        $string = (string) $expr;

        $found = preg_match_all('/NOT EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'leads .*? LEFT JOIN '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(2, $found, $string);

        // Segment filters combined by AND to keep consistent behavior with the use of leadlist_id IN (1,2,3)
        $found = preg_match_all('/AND \(NOT EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'leads .*? LEFT JOIN '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(1, $found, $string);

        $found = preg_match_all('/\(l.email = :(.*?)\)/', $string, $matches);
        $this->assertEquals(2, $found, $string);

        $this->assertTrue(isset($parameters[$matches[1][0]]) && $parameters[$matches[1][0]] = 'blah@blah.com', $string);
        $this->assertTrue(isset($parameters[$matches[1][1]]) && $parameters[$matches[1][1]] = 'blah2@blah.com', $string);
    }

    public function testExcludeSegmentFilterWithOutFiltersAppendMembershipSubquery()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => '!in',
                    'field'    => 'leadlist',
                    'object'   => 'lead',
                    'type'     => 'leadlist',
                    'display'  => null,
                    'filter'   => [1, 2],
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;

        // Two segments included
        $found = preg_match_all('/NOT EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(2, $found, $string);

        // Segment filters combined by AND to keep consistent behavior with the use of leadlist_id NOT IN (1,2,3)
        $found = preg_match_all('/AND \(NOT EXISTS \(SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads/', $string, $matches);
        $this->assertEquals(1, $found, $string);
    }

    public function testLikeFilterAppendsAmperstandIfNotIncluded()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'like',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'display'  => null,
                    'filter'   => 'blah.com',
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;
        $found  = preg_match('/^l.email LIKE :(.*?)$/', $string, $match);
        $this->assertEquals(1, $found, $string);

        $this->assertTrue(isset($parameters[$match[1]]) && $parameters[$match[1]] == '%blah.com%', $string);
    }

    public function testLikeFilterDoesNotAppendsAmperstandIfAlreadyIncluded()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'like',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'display'  => null,
                    'filter'   => 'blah.com%',
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;
        $found  = preg_match('/^l.email LIKE :(.*?)$/', $string, $match);
        $this->assertEquals(1, $found, $string);

        $this->assertTrue(isset($parameters[$match[1]]) && $parameters[$match[1]] == 'blah.com%', $string);
    }

    public function testContainsFilterAppendsAmperstandOnBothEnds()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'contains',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'display'  => null,
                    'filter'   => 'blah.com',
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;
        $found  = preg_match('/^l.email LIKE :(.*?)$/', $string, $match);
        $this->assertEquals(1, $found, $string);

        $this->assertTrue(isset($parameters[$match[1]]) && $parameters[$match[1]] == '%blah.com%', $string);
    }

    public function testStartsWithFilterAppendsAmperstandAtEnd()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'startsWith',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'display'  => null,
                    'filter'   => 'blah.com',
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;
        $found  = preg_match('/^l.email LIKE :(.*?)$/', $string, $match);
        $this->assertEquals(1, $found, $string);

        $this->assertTrue(isset($parameters[$match[1]]) && $parameters[$match[1]] == 'blah.com%', $string);
    }

    public function testEndsWithFilterAppendsAmperstandAtBeginning()
    {
        list($mockRepository, $reflectedMethod, $connection) = $this->getReflectedGenerateSegmentExpressionMethod(true);

        $parameters = [];
        $qb         = $connection->createQueryBuilder();
        $filters    =
            [
                [
                    'glue'     => 'and',
                    'operator' => 'endsWith',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'text',
                    'display'  => null,
                    'filter'   => 'blah.com',
                ],
            ];

        // array $filters, array &$parameters, QueryBuilder $q, QueryBuilder $parameterQ = null, $listId = null, $not = false
        $expr = $reflectedMethod->invokeArgs($mockRepository, [$filters, &$parameters, $qb]);

        $string = (string) $expr;
        $found  = preg_match('/^l.email LIKE :(.*?)$/', $string, $match);
        $this->assertEquals(1, $found, $string);

        $this->assertTrue(isset($parameters[$match[1]]) && $parameters[$match[1]] == '%blah.com', $string);
    }

    private function getReflectedGenerateSegmentExpressionMethod($noFilters = false)
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
        $mockRepository = $this->getMockBuilder(LeadListRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMock();

        $mockConnection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConnection->method('getExpressionBuilder')
            ->willReturnCallback(
                function () use ($mockConnection) {
                    return new ExpressionBuilder($mockConnection);
                }
            );
        $mockConnection->method('quote')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $mockSchemaManager = $this->getMockBuilder(MySqlSchemaManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSchemaManager->method('listTableColumns')
            ->willReturnCallback(
                function ($table) {
                    $mockType = $this->getMockBuilder(TextType::class)
                        ->disableOriginalConstructor()
                        ->getMock();
                    switch (true) {
                        case strpos($table, 'companies') !== false:
                            $name = 'company_email';
                            break;
                        default:
                            $name = 'email';
                    }

                    $column = new Column($name, $mockType);

                    return [
                        $name => $column,
                    ];
                }
            );

        $mockConnection->method('getSchemaManager')
            ->willReturn($mockSchemaManager);

        $mockPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockPlatform->method('getName')
            ->willReturn('mysql');
        $mockConnection->method('getDatabasePlatform')
            ->willReturn($mockPlatform);

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityManager->method('getConnection')
            ->willReturn($mockConnection);

        $mockRepository->method('getEntityManager')
            ->willReturn($mockEntityManager);

        $mockStatement = $this->getMockBuilder(Statement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subFilters1 = [
            [
                'glue'     => 'and',
                'operator' => '=',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'text',
                'display'  => null,
                'filter'   => 'blah@blah.com',
            ],
        ];

        $subFilters2 = [
            [
                'glue'     => 'and',
                'operator' => '=',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'text',
                'display'  => null,
                'filter'   => 'blah2@blah.com',
            ],
        ];

        $filters = [
            [
                'id'      => 1,
                'filters' => serialize($noFilters ? [] : $subFilters1),
            ],
            [
                'id'      => 2,
                'filters' => serialize($noFilters ? [] : $subFilters2),
            ],
        ];
        $mockStatement->method('fetchAll')
            ->willReturn($filters);

        $mockConnection->method('createQueryBuilder')
            ->willReturnCallback(
                function () use ($mockConnection, $mockStatement) {
                    $qb = $this->getMockBuilder(QueryBuilder::class)
                        ->setConstructorArgs([$mockConnection])
                        ->setMethods(['execute'])
                        ->getMock();

                    $qb->method('execute')
                        ->willReturn($mockStatement);

                    return $qb;
                }
            );

        $reflectedMockRepository = new \ReflectionObject($mockRepository);
        $method                  = $reflectedMockRepository->getMethod('generateSegmentExpression');
        $method->setAccessible(true);

        return [$mockRepository, $method, $mockConnection];
    }
}
