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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineEventSubscriberTest extends TestCase
{
    /**
     * @var MockObject&EntityManagerInterface
     */
    private MockObject $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testSubscriberWillAddCorrectIndexes(): void
    {
        $idColumn   = new Column('id', new BigIntType());
        $textColumn = new Column('firstname', new TextType());
        $dateColumn = new Column('date_added', new DateTimeType());
        $table      = new Table(MAUTIC_TABLE_PREFIX.'leads', [$idColumn, $textColumn, $dateColumn]);
        $schema     = new Schema([$table]);
        $args       = new GenerateSchemaEventArgs($this->entityManager, $schema);
        $subscriber = new DoctrineEventSubscriber();
        $subscriber->postGenerateSchema($args);

        Assert::assertTrue($schema->hasTable(MAUTIC_TABLE_PREFIX.'leads'));
        $contactsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'leads');
        Assert::assertTrue($contactsTable->hasIndex('contact_attribution'));
        Assert::assertTrue($contactsTable->hasIndex('date_added_country_index'));
    }

    public function testSubscriberWillNotFailWithTablesFromAPlugin(): void
    {
        $table      = new Table(MAUTIC_TABLE_PREFIX.'some_plugin_table', [new Column('id', new BigIntType())]);
        $schema     = new Schema([$table]);
        $args       = new GenerateSchemaEventArgs($this->entityManager, $schema);
        $subscriber = new DoctrineEventSubscriber();
        $subscriber->postGenerateSchema($args);

        Assert::assertTrue($schema->hasTable(MAUTIC_TABLE_PREFIX.'some_plugin_table'));
        Assert::assertFalse($schema->hasTable(MAUTIC_TABLE_PREFIX.'leads'));
    }
}
