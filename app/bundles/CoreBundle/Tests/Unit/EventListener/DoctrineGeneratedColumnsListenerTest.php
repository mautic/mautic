<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\EventListener\DoctrineGeneratedColumnsListener;
use Psr\Log\LoggerInterface;

class DoctrineGeneratedColumnsListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GenerateSchemaEventArgs
     */
    private \PHPUnit\Framework\MockObject\MockObject $event;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Schema
     */
    private \PHPUnit\Framework\MockObject\MockObject $schema;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Table
     */
    private \PHPUnit\Framework\MockObject\MockObject $table;

    private DoctrineGeneratedColumnsListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $generatedColumnsProvider       = $this->createMock(GeneratedColumnsProviderInterface::class);
        $logger                         = $this->createMock(LoggerInterface::class);
        $this->event                    = $this->createMock(GenerateSchemaEventArgs::class);
        $this->schema                   = $this->createMock(Schema::class);
        $this->table                    = $this->createMock(Table::class);
        $this->listener                 = new DoctrineGeneratedColumnsListener($generatedColumnsProvider, $logger);

        $generatedColumn  = new GeneratedColumn('page_hits', 'generated_hit_date', 'DATE', 'not important');
        $generatedColumns = new GeneratedColumns();

        $generatedColumns->add($generatedColumn);

        $generatedColumnsProvider->method('getGeneratedColumns')->willReturn($generatedColumns);
        $this->event->method('getSchema')->willReturn($this->schema);
    }

    public function testPostGenerateSchemaWhenTableDoesNotExist(): void
    {
        $this->schema->expects($this->once())
            ->method('hasTable')
            ->with(MAUTIC_TABLE_PREFIX.'page_hits')
            ->willReturn(false);

        $this->schema->expects($this->never())
            ->method('getTable');

        $this->listener->postGenerateSchema($this->event);
    }

    public function testPostGenerateSchemaWhenColumnExists(): void
    {
        $this->schema->expects($this->once())
            ->method('hasTable')
            ->with(MAUTIC_TABLE_PREFIX.'page_hits')
            ->willReturn(true);

        $this->schema->expects($this->once())
            ->method('getTable')
            ->with(MAUTIC_TABLE_PREFIX.'page_hits')
            ->willReturn($this->table);

        $this->table->expects($this->once())
            ->method('hasColumn')
            ->with('generated_hit_date')
            ->willReturn(true);

        $this->table->expects($this->never())
            ->method('addColumn');

        $this->listener->postGenerateSchema($this->event);
    }

    public function testPostGenerateSchemaWhenColumnDoesNotExist(): void
    {
        $this->schema->expects($this->once())
            ->method('hasTable')
            ->with(MAUTIC_TABLE_PREFIX.'page_hits')
            ->willReturn(true);

        $this->schema->expects($this->once())
            ->method('getTable')
            ->with(MAUTIC_TABLE_PREFIX.'page_hits')
            ->willReturn($this->table);

        $this->table->expects($this->once())
            ->method('hasColumn')
            ->with('generated_hit_date')
            ->willReturn(false);

        $this->table->expects($this->once())
            ->method('addColumn')
            ->with('generated_hit_date');

        $this->table->expects($this->once())
            ->method('addIndex')
            ->with(['generated_hit_date'], MAUTIC_TABLE_PREFIX.'generated_hit_date');

        $this->listener->postGenerateSchema($this->event);
    }
}
