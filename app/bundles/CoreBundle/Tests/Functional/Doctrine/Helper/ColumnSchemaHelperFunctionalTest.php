<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine\Helper;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class ColumnSchemaHelperFunctionalTest extends MauticMysqlTestCase
{
    private LeadField $field;

    private ColumnSchemaHelper $schemaHelper;

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field        = $this->createCustomField();
        $this->schemaHelper = $this->getContainer()->get(ColumnSchemaHelper::class);
    }

    #[DataProvider('provideColumnLength')]
    public function testUpdateColumnSchemaLengthSuccessfully(?int $length): void
    {
        $this->schemaHelper->updateColumnLength($this->field->getAlias(), $length);

        $column = $this->schemaHelper->getColumns()[$this->field->getAlias()];
        $this->assertInstanceOf(Column::class, $column);

        $this->assertEquals($length, $column->getLength(), 'Column length updated.');
    }

    #[DataProvider('dataUpdateColumnExceptionCheck')]
    public function testUpdateColumnLengthThrowsException(string $column, ?int $len, string $message): void
    {
        $this->expectExceptionMessageMatches($message);
        $this->schemaHelper->updateColumnLength($column, $len);
    }

    public static function provideColumnLength(): \Generator
    {
        yield 'null' => [null];
        yield '100' => [100];
    }

    /**
     * @return mixed[]
     */
    public static function dataUpdateColumnExceptionCheck(): iterable
    {
        // name, length, exception msg.
        yield 'Column name missing.' => ['', 10, '/The column name is should not be empty\/missing./'];
        yield 'Column name missing, when length is unknown' => ['', null, '/The column name is should not be empty\/missing./'];

        yield 'Column name does not exist.' => ['does_not_exists', 10, '/There is no column with name "does_not_exists" on table/'];
        yield 'Column name does not exist, when length is unknown' => ['does_not_exists', null, '/There is no column with name "does_not_exists" on table/'];

        yield 'Out of range, when length is 0.' => ['custom_field_test', 0, '/Column length should be between 1 and 191./'];
        yield 'Out of range, when length greater than 191.' => ['custom_field_test', 195, '/Column length should be between 1 and 191./'];
    }

    #[DataProvider('generatedDependencyLengths')]
    public function testGeneratedDependenciesSurviveSourceLengthChanges(int $length, bool $succeeds): void
    {
        if (!$this->isPostgresqlPlatform()) {
            $this->markTestSkipped('PostgreSQL generated columns require rebuilding their dependencies before source type changes.');
        }
        $table = MAUTIC_TABLE_PREFIX.'generated_dependency_fixture';
        $this->connection->executeStatement("CREATE TABLE {$table} (
            id integer PRIMARY KEY, email varchar(64),
            domain text GENERATED ALWAYS AS (split_part(email, '@', 2)) STORED NOT NULL,
            CONSTRAINT generated_domain_unique UNIQUE (domain),
            CONSTRAINT generated_domain_check CHECK (length(domain) > 0)
        )");
        try {
            $this->connection->executeStatement("COMMENT ON COLUMN {$table}.domain IS '(DC2Type:generated)'");
            $this->connection->executeStatement("CREATE INDEX generated_domain_expression ON {$table} (lower(domain), id)");
            $this->connection->insert($table, ['id' => 1, 'email' => 'alice@example.com']);
            $beforeIndexes = $this->connection->fetchFirstColumn('SELECT indexdef FROM pg_indexes WHERE tablename = ? ORDER BY indexname', [$table]);
            $helper = new ColumnSchemaHelper($this->connection, MAUTIC_TABLE_PREFIX);
            $helper->setName('generated_dependency_fixture')->updateColumnLength('email', $length);
            $exception = null;
            try {
                $helper->executeChanges();
            } catch (Exception $exception) {
            }
            $this->assertSame($succeeds, null === $exception, $exception?->getMessage() ?? '');
            $column = $this->connection->createSchemaManager()->introspectTable($table)->getColumn('email');
            $this->assertSame($succeeds ? $length : 64, $column->getLength());
            $this->assertSame(['email' => 'alice@example.com', 'domain' => 'example.com'], $this->connection->fetchAssociative("SELECT email, domain FROM {$table} WHERE id = 1"));
            $this->assertSame($beforeIndexes, $this->connection->fetchFirstColumn('SELECT indexdef FROM pg_indexes WHERE tablename = ? ORDER BY indexname', [$table]));
            $this->assertSame('(DC2Type:generated)', $this->connection->fetchOne("SELECT col_description(?::regclass, attnum) FROM pg_attribute WHERE attrelid = ?::regclass AND attname = 'domain'", [$table, $table]));
            $this->assertSame(2, (int) $this->connection->fetchOne("SELECT COUNT(*) FROM pg_constraint WHERE conrelid = ?::regclass AND conname IN ('generated_domain_unique', 'generated_domain_check')", [$table]));
            $this->connection->update($table, ['email' => 'alice@updated.example'], ['id' => 1]);
            $this->assertSame('updated.example', $this->connection->fetchOne("SELECT domain FROM {$table} WHERE id = 1"));
        } finally {
            $this->connection->executeStatement('DROP TABLE '.$table);
        }
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function generatedDependencyLengths(): iterable
    {
        yield 'widening preserves and recomputes generated values' => [120, true];
        yield 'invalid narrowing rolls back source and generated schema' => [3, false];
    }

    private function createCustomField(): LeadField
    {
        $field = new LeadField();
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setLabel('Test field');
        $field->setAlias('custom_field_test');
        $field->setCharLengthLimit(64);

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);
        $this->assertInstanceOf(FieldModel::class, $fieldModel);
        $fieldModel->saveEntity($field);
        $fieldModel->getRepository()->detachEntity($field);

        return $field;
    }
}
