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
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Test\Doctrine\DBALMocker;
use Mautic\LeadBundle\Entity\CustomFieldRepositoryTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Assert;

class LeadRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EntityManagerInterface
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
     * @var LeadRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->repository    = new LeadRepository($this->entityManager, $this->classMetadata);
    }

    public function testBooleanWithPrepareDbalFieldsForSave()
    {
        $trait  = $this->getMockForTrait(CustomFieldRepositoryTrait::class);
        $fields = [
            'true'   => true,
            'false'  => false,
            'string' => 'blah',
        ];

        $reflection = new \ReflectionObject($trait);
        $method     = $reflection->getMethod('prepareDbalFieldsForSave');
        $method->setAccessible(true);
        $method->invokeArgs($trait, [&$fields]);

        $this->assertEquals(1, $fields['true']);
        $this->assertEquals(0, $fields['false']);
        $this->assertEquals('blah', $fields['string']);
    }

    /**
     * Ensure that the emails are bound separately as parameters according to
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/query-builder.html#line-number-0a267d5a2c69797a7656aae33fcc140d16b0a566-72.
     */
    public function testBuildQueryForGetLeadsByFieldValue()
    {
        $dbalMock = new DBALMocker($this);

        $mock = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMock();

        $mock->method('getEntityManager')
            ->will($this->returnValue($dbalMock->getMockEm()));

        $reflection = new \ReflectionClass(LeadRepository::class);
        $refMethod  = $reflection->getMethod('buildQueryForGetLeadsByFieldValue');
        $refMethod->setAccessible(true);

        $refMethod->invoke($mock, 'email', ['test@example.com', 'test2@example.com']);

        $parameters = $dbalMock->getQueryPart('parameters');

        $this->assertCount(2, $parameters, 'There should be two parameters bound because that\'s the number of emails we passed into the method.');
    }

    /**
     * Ensure that the array_combine return value matches the old style.
     */
    public function testGetLeadsByFieldValueArrayMapReturn()
    {
        $mock = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntities', 'buildQueryForGetLeadsByFieldValue'])
            ->getMock();

        // Mock the
        $mockEntity = $this->getMockBuilder(Lead::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadMetadata'])
            ->getMock();

        $mockEntity->setEmail('test@example.com');

        $mockEntity2 = clone $mockEntity;
        $mockEntity2->setEmail('test2@example.com');

        $entities = [
            $mockEntity,
            $mockEntity2,
        ];

        $mock->method('getEntities')
            ->will($this->returnValue($entities));

        $mock->method('buildQueryForGetLeadsByFieldValue')
            ->will($this->returnValue(null));

        $contacts = $mock->getLeadsByFieldValue('email', ['test@example.com', 'test2@example.com']);

        $this->assertSame($entities, $contacts, 'When getting leads without indexing by column, it should match the expected result.');

        $contacts = $mock->getLeadsByFieldValue('email', ['test@example.com', 'test2@example.com'], null, true);

        $expected = [
            'test@example.com',
            'test2@example.com',
        ];

        $this->assertSame($expected, array_keys($contacts), 'When getting leads with indexing by column, it should match the expected result.');
    }

    /**
     * Ensure that we will join each table with the same alias only once.
     */
    public function testApplySearchQueryRelationshipJoinOnlyOnce(): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('*')->from('table_a');
        $tableB = [
            'alias'      => 'alias_b',
            'from_alias' => 'table_a',
            'table'      => 'table_b',
            'condition'  => 'condition_b',
        ];
        $tableC = [
            'alias'      => 'alias_c',
            'from_alias' => 'table_a',
            'table'      => 'table_c',
            'condition'  => 'condition_c',
        ];

        $this->repository->applySearchQueryRelationship(
            $queryBuilder,
            [$tableB, $tableC, $tableB], // Sending 2 table B here.
            true
        );

        Assert::assertSame(
            'SELECT * FROM table_a INNER JOIN table_b alias_b ON condition_b INNER JOIN table_c alias_c ON condition_c GROUP BY l.id',
            $queryBuilder->getSQL()
        );
    }

    public function testGetContactIdsByEmails(): void
    {
        $emails = [
            'somebody1@anywhere.com',
            'somebody2@anywhere.com',
        ];

        $entityManager = $this->createMock(EntityManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $repo          = new LeadRepository($entityManager, $classMetadata);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('setParameter')
            ->with(':emails', $emails, Connection::PARAM_STR_ARRAY)
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([
                0 => [
                    'id' => '1',
                ],
                1 => [
                    'id' => '2',
                ],
            ]);

        $entityManager->expects(self::once())
            ->method('createQuery')
            ->willReturn($query);

        self::assertEquals(
            [1, 2],
            $repo->getContactIdsByEmails($emails)
        );
    }
}
