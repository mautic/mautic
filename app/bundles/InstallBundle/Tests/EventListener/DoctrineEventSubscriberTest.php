<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
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
        $this->assertTrue($contactsTable->hasIndex('contact_attribution'));
        $this->assertTrue($contactsTable->hasIndex('date_added_country_index'));
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
}
