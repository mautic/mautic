<?php

namespace Mautic\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager as BasePostgreSQLSchemaManager;

class PostgreSQLSchemaManager extends BasePostgreSQLSchemaManager
{
    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        // 1. We select the base table and schema info
        $sql = 'SELECT 
                    tc.relname AS table_name, 
                    tn.nspname AS schema_name,
                    quote_ident(ic.relname) AS relname,
                    i.indisunique AS indisunique,
                    i.indisprimary AS indisprimary,
                    i.indkey,
                    i.indrelid,
                    pg_get_expr(i.indpred, i.indrelid) AS "where"
                FROM pg_index i
                JOIN pg_class AS tc ON tc.oid = i.indrelid
                JOIN pg_namespace tn ON tn.oid = tc.relnamespace
                JOIN pg_class AS ic ON ic.oid = i.indexrelid
                WHERE tn.nspname = ANY(current_schemas(false))';

        // 2. Filter by table if provided
        if (null !== $tableName) {
            $sql .= ' AND tc.relname = '.$this->_platform->quoteStringLiteral($tableName);
        }

        // 3. Ensure we aren't picking up 'dead' indexes or
        // indexes on non-standard table types (like partitioned parents incorrectly)
        $sql .= " AND tc.relkind IN ('r', 'm', 'p')";

        return $this->_conn->executeQuery($sql);
    }
}
