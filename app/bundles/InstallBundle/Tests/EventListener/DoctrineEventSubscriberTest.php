<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\EventListener;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\InstallBundle\EventListener\DoctrineEventSubscriber;
use PHPUnit\Framework\TestCase;

final class DoctrineEventSubscriberTest extends TestCase
{
    public function testSubscriberWillAddCorrectIndexes(): void
    {
        $idColumn   = new Column('id', new BigIntType());
        $textColumn = new Column('firstname', new TextType());
        $dateColumn = new Column('date_added', new DateTimeType());
        $table      = new Table(MAUTIC_TABLE_PREFIX.'leads', [$idColumn, $textColumn, $dateColumn]);
        $schema     = new Schema([$table]);
        $args       = new GenerateSchemaEventArgs($this->createStub(EntityManagerInterface::class), $schema);
        $subscriber = new DoctrineEventSubscriber();
        $subscriber->postGenerateSchema($args);

        $this->assertTrue($schema->hasTable(MAUTIC_TABLE_PREFIX.'leads'));
        $contactsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'leads');
        $this->assertTrue($contactsTable->hasIndex(MAUTIC_TABLE_PREFIX.'contact_attribution'));
        $this->assertTrue($contactsTable->hasIndex(MAUTIC_TABLE_PREFIX.'date_added_country_index'));
    }

    public function testSubscriberWillNotFailWithTablesFromAPlugin(): void
    {
        $table      = new Table(MAUTIC_TABLE_PREFIX.'some_plugin_table', [new Column('id', new BigIntType())]);
        $schema     = new Schema([$table]);
        $args       = new GenerateSchemaEventArgs($this->createStub(EntityManagerInterface::class), $schema);
        $subscriber = new DoctrineEventSubscriber();
        $subscriber->postGenerateSchema($args);

        $this->assertTrue($schema->hasTable(MAUTIC_TABLE_PREFIX.'some_plugin_table'));
        $this->assertFalse($schema->hasTable(MAUTIC_TABLE_PREFIX.'leads'));
    }

    public function testFixedUniqueFieldsAndCompositeIndexesUseTheInstallationPrefix(): void
    {
        $schema = new Schema();
        $schema->createTable(MAUTIC_TABLE_PREFIX.'leads')->addColumn('email', 'string');
        $schema->getTable(MAUTIC_TABLE_PREFIX.'leads')->addColumn('date_added', 'datetime');
        $schema->createTable(MAUTIC_TABLE_PREFIX.'companies')->addColumn('companyname', 'string');
        $args       = new GenerateSchemaEventArgs($this->createStub(EntityManagerInterface::class), $schema);
        $subscriber = new DoctrineEventSubscriber();
        $subscriber->postGenerateSchema($args);
        $subscriber->postGenerateSchema($args);

        foreach (['leads' => ['email_search', 'contact_attribution', 'date_added_country_index'], 'companies' => ['companyname_search', 'company_filter', 'company_match']] as $tableName => $indexes) {
            $table = $schema->getTable(MAUTIC_TABLE_PREFIX.$tableName);
            foreach ($indexes as $indexName) {
                $this->assertTrue($table->hasIndex(MAUTIC_TABLE_PREFIX.$indexName));
                $this->assertFalse($table->hasIndex($indexName));
            }
            foreach ($table->getIndexes() as $index) {
                $this->assertStringStartsWith(MAUTIC_TABLE_PREFIX, $index->getName());
            }
        }
    }
}
