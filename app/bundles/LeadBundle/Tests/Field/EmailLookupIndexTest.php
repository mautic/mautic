<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Mautic\LeadBundle\Field\EmailLookupIndex;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailLookupIndexTest extends TestCase
{
    private Connection&MockObject $connection;
    private AbstractSchemaManager&MockObject $schemaManager;

    protected function setUp(): void
    {
        $this->connection    = $this->createMock(Connection::class);
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);
        $this->connection->method('createSchemaManager')->willReturn($this->schemaManager);
        $this->schemaManager->method('tablesExist')->with(['leads'])->willReturn(true);
    }

    public function testItCreatesTheEmailIndexWhenNoEmailFirstIndexExists(): void
    {
        $this->schemaManager->method('introspectTable')->with('leads')->willReturn(new Table('leads'));
        $this->connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with('CREATE INDEX email_search ON leads (email)');

        (new EmailLookupIndex($this->connection, ''))->ensure();
    }

    public function testItKeepsAnExistingEmailFirstIndexWithAnotherName(): void
    {
        $table = new Table('leads');
        $table->addColumn('email', 'string');
        $table->addIndex(['email'], 'custom_email_lookup');
        $this->schemaManager->method('introspectTable')->with('leads')->willReturn($table);
        $this->connection->expects($this->never())->method('executeStatement');

        (new EmailLookupIndex($this->connection, ''))->ensure();
    }
}
