<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class Version20261015180000 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Apply the PostgreSQL case and accent insensitive collation to existing application text columns';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(!DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform()), 'Only PostgreSQL needs collation migration.');
        $this->abortIf(null === $this->connection->fetchOne("SELECT to_regcollation('mautic_unicode_ci')"), 'The mautic_unicode_ci collation must be installed before this migration.');

        $schemaName = (string) $this->connection->fetchOne('SELECT current_schema()');
        $tableNames = $this->getOwnedTableNames();
        $formsTable = $this->prefix.'forms';
        if ($this->tableExists($schemaName, $formsTable)) {
            foreach ($this->connection->fetchAllAssociative('SELECT id, alias FROM '.$this->connection->quoteIdentifier($schemaName).'.'.$this->connection->quoteIdentifier($formsTable)) as $form) {
                $tableNames[] = $this->prefix.'form_results_'.$form['id'].'_'.$form['alias'];
            }
        }
        $tableNames = array_values(array_unique($tableNames));
        $collation = $this->connection->fetchOne("SELECT oid FROM pg_collation WHERE oid = to_regcollation('mautic_unicode_ci') AND collprovider = 'i' AND NOT collisdeterministic");
        $this->abortIf(null === $collation || false === $collation, 'mautic_unicode_ci must be a PostgreSQL nondeterministic ICU collation.');

        $columnsByTable = [];
        $candidateColumnsByRelation = [];
        foreach ($tableNames as $tableName) {
            if (!$this->tableExists($schemaName, $tableName)) {
                continue;
            }

            $columnsByTable[$tableName] = $this->connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT a.attrelid AS relation, a.attname, a.attnum, a.attcollation,
                        format_type(a.atttypid, a.atttypmod) AS type, t.typname,
                        a.attgenerated, a.attnotnull,
                        pg_get_expr(definition.adbin, definition.adrelid) AS expression,
                        col_description(a.attrelid, a.attnum) AS comment
                    FROM pg_attribute a
                    JOIN pg_type t ON t.oid = a.atttypid
                    LEFT JOIN pg_attrdef definition ON definition.adrelid = a.attrelid AND definition.adnum = a.attnum
                    WHERE a.attrelid = to_regclass(:table)
                        AND a.attnum > 0 AND NOT a.attisdropped
                        AND t.typname IN ('text', 'varchar', 'bpchar')
                        AND a.attcollation <> :collation
                    ORDER BY a.attnum
                    SQL,
                ['table' => $schemaName.'.'.$tableName, 'collation' => $collation]
            );

            foreach ($columnsByTable[$tableName] as $column) {
                $candidateColumnsByRelation[(int) $column['relation']][(int) $column['attnum']] = true;
            }
        }

        $foreignKeys = $this->getAffectedForeignKeys($schemaName, $candidateColumnsByRelation, array_fill_keys($tableNames, true), (string) $collation);
        foreach ($foreignKeys as $foreignKey) {
            $childTable = $this->connection->quoteIdentifier($foreignKey['schema']).'.'.$this->connection->quoteIdentifier($foreignKey['table']);
            $this->addSql('ALTER TABLE '.$childTable.' DROP CONSTRAINT '.$this->connection->quoteIdentifier($foreignKey['name']));
        }

        foreach ($tableNames as $tableName) {
            $columns = $columnsByTable[$tableName] ?? [];
            if (!$columns) {
                continue;
            }
            $tableSql = $this->connection->quoteIdentifier($schemaName).'.'.$this->connection->quoteIdentifier($tableName);

            $collatableNames = array_column($columns, 'attname');
            $generatedColumns = [];
            foreach ($columns as $column) {
                if ('' !== $column['attgenerated']) {
                    $generatedColumns[$column['attname']] = $column;
                }
            }
            $ordinaryColumns = array_filter($columns, static fn (array $column): bool => '' === $column['attgenerated']);

            $dependentColumns = $this->connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT DISTINCT generated.attname, generated.attnum,
                        format_type(generated.atttypid, generated.atttypmod) AS type,
                        generated.attnotnull, generated.attgenerated,
                        pg_get_expr(definition.adbin, definition.adrelid) AS expression,
                        col_description(generated.attrelid, generated.attnum) AS comment
                    FROM pg_attribute source
                    JOIN pg_depend dependency ON dependency.refobjid = source.attrelid AND dependency.refobjsubid = source.attnum
                        AND dependency.classid = 'pg_attrdef'::regclass
                    JOIN pg_attrdef definition ON definition.oid = dependency.objid
                    JOIN pg_attribute generated ON generated.attrelid = definition.adrelid AND generated.attnum = definition.adnum
                    WHERE source.attrelid = to_regclass(:table) AND source.attname IN (:columns) AND generated.attgenerated <> ''
                    ORDER BY generated.attnum
                    SQL,
                ['table' => $schemaName.'.'.$tableName, 'columns' => $collatableNames],
                ['columns' => ArrayParameterType::STRING]
            );
            foreach ($dependentColumns as $column) {
                $generatedColumns[$column['attname']] = $column;
            }
            $constraints = [];
            $indexes = [];
            if ($generatedColumns) {
                $generatedNumbers = array_column($generatedColumns, 'attnum');
                $constraints = $this->connection->fetchAllAssociative(
                    <<<'SQL'
                        SELECT conname, pg_get_constraintdef(oid) AS definition
                        FROM pg_constraint
                        WHERE conrelid = to_regclass(:table)
                            AND contype <> 'f'
                            AND EXISTS (SELECT 1 FROM unnest(conkey) AS column_number WHERE column_number IN (:columns))
                        ORDER BY conname
                        SQL,
                    ['table' => $schemaName.'.'.$tableName, 'columns' => $generatedNumbers],
                    ['columns' => ArrayParameterType::INTEGER]
                );
                $indexes = $this->connection->fetchAllAssociative(
                    <<<'SQL'
                        SELECT DISTINCT index_class.relname AS name, pg_get_indexdef(index.indexrelid) AS definition
                        FROM pg_index index
                        JOIN pg_class index_class ON index_class.oid = index.indexrelid
                        JOIN pg_depend dependency ON dependency.objid = index.indexrelid AND dependency.classid = 'pg_class'::regclass
                            AND dependency.refobjid = index.indrelid
                        WHERE index.indrelid = to_regclass(:table) AND dependency.refobjsubid IN (:columns)
                            AND NOT EXISTS (SELECT 1 FROM pg_constraint c WHERE c.conindid = index.indexrelid AND c.contype IN ('p', 'u', 'x'))
                        ORDER BY name
                        SQL,
                    ['table' => $schemaName.'.'.$tableName, 'columns' => $generatedNumbers],
                    ['columns' => ArrayParameterType::INTEGER]
                );
                foreach ($constraints as $constraint) {
                    $this->addSql('ALTER TABLE '.$tableSql.' DROP CONSTRAINT '.$this->connection->quoteIdentifier($constraint['conname']));
                }
                foreach ($indexes as $index) {
                    $this->addSql('DROP INDEX '.$this->connection->quoteIdentifier($schemaName).'.'.$this->connection->quoteIdentifier($index['name']));
                }
                foreach ($generatedColumns as $column) {
                    $this->addSql('ALTER TABLE '.$tableSql.' DROP COLUMN '.$this->connection->quoteIdentifier($column['attname']));
                }
            }

            foreach ($ordinaryColumns as $column) {
                $this->addSql('ALTER TABLE '.$tableSql.' ALTER COLUMN '.$this->connection->quoteIdentifier($column['attname']).' TYPE '.$column['type'].' COLLATE '.$this->connection->quoteIdentifier('mautic_unicode_ci'));
            }
            foreach ($generatedColumns as $column) {
                $name = $this->connection->quoteIdentifier($column['attname']);
                $storage = 'v' === $column['attgenerated'] ? 'VIRTUAL' : 'STORED';
                $type = $column['type'];
                if (preg_match('/^(?:CHAR(?:ACTER)?(?: VARYING)?|VARCHAR|TEXT)\b/i', $type)) {
                    $type .= ' COLLATE '.$this->connection->quoteIdentifier('mautic_unicode_ci');
                }
                $expression = $this->prefix.'leads' === $tableName && 'generated_email_domain' === $column['attname']
                    ? "SUBSTRING(email, POSITION('@' IN (email COLLATE \"C\")) + 1)"
                    : $column['expression'];
                $this->addSql('ALTER TABLE '.$tableSql.' ADD COLUMN '.$name.' '.$type.' GENERATED ALWAYS AS ('.$expression.') '.$storage.($column['attnotnull'] ? ' NOT NULL' : ''));
                if (null !== $column['comment']) {
                    $this->addSql('COMMENT ON COLUMN '.$tableSql.'.'.$name.' IS '.$this->connection->quote($column['comment']));
                }
            }
            foreach ($constraints as $constraint) {
                $this->addSql('ALTER TABLE '.$tableSql.' ADD CONSTRAINT '.$this->connection->quoteIdentifier($constraint['conname']).' '.$constraint['definition']);
            }
            foreach ($indexes as $index) {
                $this->addSql($index['definition']);
            }
        }

        foreach ($foreignKeys as $foreignKey) {
            $childTable = $this->connection->quoteIdentifier($foreignKey['schema']).'.'.$this->connection->quoteIdentifier($foreignKey['table']);
            $this->addSql('ALTER TABLE '.$childTable.' ADD CONSTRAINT '.$this->connection->quoteIdentifier($foreignKey['name']).' '.$foreignKey['definition']);
            if (null !== $foreignKey['comment']) {
                $this->addSql('COMMENT ON CONSTRAINT '.$this->connection->quoteIdentifier($foreignKey['name']).' ON '.$childTable.' IS '.$this->connection->quote($foreignKey['comment']));
            }
        }

        $this->skipIf([] === $this->getSql(), 'All Mautic text columns already use mautic_unicode_ci.');
    }

    /**
     * @return string[]
     */
    private function getOwnedTableNames(): array
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $metadata      = $entityManager->getMetadataFactory()->getAllMetadata();
        $runtimePrefix = (string) $this->container->getParameter('mautic.db_table_prefix');
        $names         = [];

        foreach ($metadata as $classMetadata) {
            $names[] = $this->withMigrationPrefix($classMetadata->getTableName(), $runtimePrefix);
            foreach ($classMetadata->getAssociationMappings() as $association) {
                if (isset($association['joinTable']['name'])) {
                    $names[] = $this->withMigrationPrefix($association['joinTable']['name'], $runtimePrefix);
                }
            }
        }

        return array_values(array_unique($names));
    }

    private function withMigrationPrefix(string $tableName, string $runtimePrefix): string
    {
        if ('' !== $runtimePrefix && str_starts_with($tableName, $runtimePrefix)) {
            $tableName = substr($tableName, strlen($runtimePrefix));
        }

        return $this->prefix.$tableName;
    }

    /**
     * @param array<int, array<int, bool>> $candidateColumnsByRelation
     * @param array<string, bool>          $ownedTableNames
     *
     * @return array<string, array{schema: string, table: string, name: string, definition: string, comment: ?string}>
     */
    private function getAffectedForeignKeys(string $schemaName, array $candidateColumnsByRelation, array $ownedTableNames, string $collation): array
    {
        if (!$candidateColumnsByRelation) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT constraint_row.oid AS constraint_oid, constraint_row.conname,
                    pg_get_constraintdef(constraint_row.oid, true) AS definition,
                    obj_description(constraint_row.oid, 'pg_constraint') AS comment,
                    child_namespace.nspname AS child_schema, child.relname AS child_table,
                    parent_namespace.nspname AS parent_schema, parent.relname AS parent_table,
                    key_pair.ordinality,
                    child_attribute.attrelid AS child_relation, child_attribute.attnum AS child_attnum,
                    child_attribute.attname AS child_column, child_attribute.attcollation AS child_collation,
                    child_type.typname AS child_type,
                    parent_attribute.attrelid AS parent_relation, parent_attribute.attnum AS parent_attnum,
                    parent_attribute.attname AS parent_column, parent_attribute.attcollation AS parent_collation,
                    parent_type.typname AS parent_type
                FROM pg_constraint constraint_row
                JOIN pg_class child ON child.oid = constraint_row.conrelid
                JOIN pg_namespace child_namespace ON child_namespace.oid = child.relnamespace
                JOIN pg_class parent ON parent.oid = constraint_row.confrelid
                JOIN pg_namespace parent_namespace ON parent_namespace.oid = parent.relnamespace
                JOIN LATERAL unnest(constraint_row.conkey, constraint_row.confkey) WITH ORDINALITY
                    AS key_pair(child_attnum, parent_attnum, ordinality) ON TRUE
                JOIN pg_attribute child_attribute ON child_attribute.attrelid = child.oid AND child_attribute.attnum = key_pair.child_attnum
                JOIN pg_type child_type ON child_type.oid = child_attribute.atttypid
                JOIN pg_attribute parent_attribute ON parent_attribute.attrelid = parent.oid AND parent_attribute.attnum = key_pair.parent_attnum
                JOIN pg_type parent_type ON parent_type.oid = parent_attribute.atttypid
                WHERE constraint_row.contype = 'f'
                    AND (constraint_row.conrelid IN (:relations) OR constraint_row.confrelid IN (:relations))
                ORDER BY constraint_row.oid, key_pair.ordinality
                SQL,
            ['relations' => array_map('intval', array_keys($candidateColumnsByRelation))],
            ['relations' => ArrayParameterType::INTEGER]
        );

        $foreignKeys = [];
        foreach ($rows as $row) {
            $childCandidate = isset($candidateColumnsByRelation[(int) $row['child_relation']][(int) $row['child_attnum']]);
            $parentCandidate = isset($candidateColumnsByRelation[(int) $row['parent_relation']][(int) $row['parent_attnum']]);
            if (!$childCandidate && !$parentCandidate) {
                continue;
            }

            if ($childCandidate && $parentCandidate) {
                $this->abortIf($schemaName !== $row['child_schema'] || !isset($ownedTableNames[$row['child_table']]), sprintf('Cannot safely rebuild foreign key %s: its referencing table %s.%s is not an owned application table.', $row['conname'], $row['child_schema'], $row['child_table']));
                $foreignKeys[(string) $row['constraint_oid']] = [
                    'schema'     => $row['child_schema'],
                    'table'      => $row['child_table'],
                    'name'       => $row['conname'],
                    'definition' => $row['definition'],
                    'comment'    => $row['comment'],
                ];

                continue;
            }

            $peerType = $childCandidate ? $row['parent_type'] : $row['child_type'];
            $peerCollation = (string) ($childCandidate ? $row['parent_collation'] : $row['child_collation']);
            if (in_array($peerType, ['text', 'varchar', 'bpchar'], true) && $peerCollation !== $collation) {
                $peerSchema = $childCandidate ? $row['parent_schema'] : $row['child_schema'];
                $peerTable = $childCandidate ? $row['parent_table'] : $row['child_table'];
                $peerColumn = $childCandidate ? $row['parent_column'] : $row['child_column'];
                $this->abortIf(true, sprintf('Cannot safely migrate text collation: foreign key %s pairs an application text column with unowned or nonmigrated column %s.%s.%s.', $row['conname'], $peerSchema, $peerTable, $peerColumn));
            }
        }

        return $foreignKeys;
    }

    private function tableExists(string $schemaName, string $tableName): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = :schema AND c.relname = :table AND c.relkind IN (\'r\', \'p\')',
            ['schema' => $schemaName, 'table' => $tableName]
        );
    }
}
