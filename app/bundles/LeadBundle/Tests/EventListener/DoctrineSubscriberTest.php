<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\InstallBundle\EventListener\DoctrineEventSubscriber;
use Mautic\LeadBundle\EventListener\DoctrineSubscriber;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class DoctrineSubscriberTest extends TestCase
{
    public function testExistingInstallerIndexesDoNotAbortRuntimeFieldMetadata(): void
    {
        $leadFields = $this->createStub(Result::class);
        $leadFields->method('fetchAllAssociative')->willReturn([
            ['alias' => 'email', 'type' => 'email', 'is_unique' => true, 'is_index' => false],
            ['alias' => 'custom_indexed_field', 'type' => 'text', 'is_unique' => true, 'is_index' => true],
        ]);
        $companyFields = $this->createStub(Result::class);
        $companyFields->method('fetchAllAssociative')->willReturn([
            ['alias' => 'companyname', 'type' => 'text', 'is_unique' => true, 'is_index' => false],
        ]);
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection->method('createQueryBuilder')->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($connection));
        $connection->method('executeQuery')->willReturnOnConsecutiveCalls($leadFields, $companyFields, $leadFields, $companyFields);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $schema = new Schema();
        $schema->createTable(MAUTIC_TABLE_PREFIX.'leads')->addColumn('date_added', 'datetime');
        $schema->createTable(MAUTIC_TABLE_PREFIX.'companies');
        $schema->createTable(MAUTIC_TABLE_PREFIX.'lead_fields');
        $args = new GenerateSchemaEventArgs($entityManager, $schema);
        (new DoctrineEventSubscriber())->postGenerateSchema($args);
        $schema->getTable(MAUTIC_TABLE_PREFIX.'companies')->dropIndex(MAUTIC_TABLE_PREFIX.'company_match');
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');
        $subscriber = new DoctrineSubscriber($logger);

        $subscriber->postGenerateSchema($args);
        $subscriber->postGenerateSchema($args);

        $leads = $schema->getTable(MAUTIC_TABLE_PREFIX.'leads');
        $this->assertTrue($leads->hasColumn('custom_indexed_field'));
        $this->assertTrue($leads->hasIndex(MAUTIC_TABLE_PREFIX.'email_search'));
        $this->assertTrue($leads->hasIndex(MAUTIC_TABLE_PREFIX.'custom_indexed_field_search'));
        $this->assertSame(['custom_indexed_field', 'email'], $leads->getIndex(MAUTIC_TABLE_PREFIX.'lead_unique_identifier_search')->getColumns());
        $this->assertTrue($schema->getTable(MAUTIC_TABLE_PREFIX.'companies')->hasIndex(MAUTIC_TABLE_PREFIX.'companyname_search'));
        $this->assertTrue($schema->getTable(MAUTIC_TABLE_PREFIX.'companies')->hasIndex(MAUTIC_TABLE_PREFIX.'company_match'));
    }
}
