<?php

namespace Mautic\CoreBundle\Doctrine\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as BasePostgreSQLPlatform;
use Doctrine\Deprecations\Deprecation;
use Mautic\CoreBundle\Doctrine\Schema\PostgreSQLSchemaManager;

class PostgreSQLPlatform extends BasePostgreSQLPlatform
{
    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        return new PostgreSQLSchemaManager($connection, $this);
    }

    // Fix for DBAL 3.x wrong index list SQL
    public function getListTableIndexesSQL($table, $database = null): string
    {
        // Custom SQL to correctly fetch indexes, ensuring generated columns
        // or specific PostgreSQL 12+ features don't break the mapping.
        return 'SELECT quote_ident(relname) as relname, i.indisunique, i.indisprimary,
                pg_get_indexdef(i.indexrelid) AS indexdef
                FROM pg_index i
                JOIN pg_class c ON c.oid = i.indrelid
                JOIN pg_class rc ON rc.oid = i.indexrelid
                WHERE c.relname = '.$this->quoteStringLiteral($table)."
                AND c.relkind = 'r'";
    }

    // Fix for "GENERATED ALWAYS" keyword handling
    public function getColumnDeclarationSQL($name, array $column)
    {
        if (isset($column['columnDefinition'])) {
            $declaration = $this->getCustomTypeDeclarationSQL($column);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($column);

            $charset = !empty($column['charset']) ?
                ' '.$this->getColumnCharsetDeclarationSQL($column['charset']) : '';

            $collation = !empty($column['collation']) ?
                ' '.$this->getColumnCollationDeclarationSQL($column['collation']) : '';

            $notnull = !empty($column['notnull']) ? ' NOT NULL' : '';

            if (!empty($column['unique'])) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/pull/5656',
                    'The usage of the "unique" column property is deprecated. Use unique constraints instead.',
                );

                $unique = ' '.$this->getUniqueFieldDeclarationSQL();
            } else {
                $unique = '';
            }
            $check  = !empty($column['check']) ? ' '.$column['check'] : '';

            $typeDecl = $column['type']->getSQLDeclaration($column, $this);

            // --- CUSTOM LOGIC FOR GENERATED COLUMNS ---
            $generated = '';
            if (isset($column['generated']) && !empty($column['definition'])) {
                // Format: GENERATED ALWAYS AS (expression) STORED
                $generated = sprintf(' GENERATED ALWAYS AS (%s) STORED', $column['definition']);

                // Generated columns cannot have a DEFAULT clause in Postgres
                $default = '';
            }
            // ------------------------------------------

            $declaration = $typeDecl.$charset.$generated.$default.$notnull.$unique.$check.$collation;

            if ($this->supportsInlineColumnComments() && isset($column['comment']) && '' !== $column['comment']) {
                $declaration .= ' '.$this->getInlineColumnCommentSQL($column['comment']);
            }
        }

        return $name.' '.$declaration;
    }
}
