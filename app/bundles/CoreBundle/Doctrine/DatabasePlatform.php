<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Central abstraction point for database platform differences (MySQL vs PostgreSQL).
 *
 * Also a workaround for deprecated \Doctrine\DBAL\Platforms\AbstractPlatform::getName.
 */
class DatabasePlatform
{
    /**
     * Match date/time unit to a SQL datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}.
     *
     * @var array<string, string>
     */
    public static $sqlFormats = [
        's' => 'Y-m-d H:i:s',
        'i' => 'Y-m-d H:i:00',
        'H' => 'Y-m-d H:00:00',
        'd' => 'Y-m-d 00:00:00',
        'D' => 'Y-m-d 00:00:00', // ('D' is BC. Can be removed when all charts use this class)
        'W' => 'Y-m-d 00:00:00',
        'm' => 'Y-m-01 00:00:00',
        'M' => 'Y-m-00 00:00:00', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'Y-01-01 00:00:00',
    ];

    /**
     * Match date/time unit to a MySql datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * {@link dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format}.
     *
     * @var array<string, string>
     */
    public static $mysqlTimeUnits = [
        's' => '%Y-%m-%d %H:%i:%s',
        'i' => '%Y-%m-%d %H:%i',
        'H' => '%Y-%m-%d %H:00',
        'd' => '%Y-%m-%d',
        'D' => '%Y-%m-%d', // ('D' is BC. Can be removed when all charts use this class)
        'W' => '%Y %U',
        'm' => '%Y-%m',
        'M' => '%Y-%m', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => '%Y',
    ];

    /**
     * Match date/time unit to a PostgreSql datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * {@link www.postgresql.org/docs/current/functions-formatting.html}.
     *
     * @var array<string, string>
     */
    public static $postgresqlTimeUnits = [
        's' => 'YYYY-MM-DD HH24:MI:SS',
        'i' => 'YYYY-MM-DD HH24:MI',
        'H' => 'YYYY-MM-DD HH24":00"',
        'd' => 'YYYY-MM-DD',
        'D' => 'YYYY-MM-DD', // ('D' is BC. Can be removed when all charts use this class)
        'W' => 'IYYY IW',
        'm' => 'YYYY-MM',
        'M' => 'YYYY-MM', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'YYYY',
    ];

    public static function getDatabasePlatform(AbstractPlatform $platform): string
    {
        if ($platform instanceof AbstractMySQLPlatform) {
            return 'mysql';
        }

        if ($platform instanceof DB2Platform) {
            return 'db2';
        }

        if ($platform instanceof OraclePlatform) {
            return 'oracle';
        }

        if ($platform instanceof PostgreSQLPlatform) {
            return 'postgresql';
        }

        if ($platform instanceof SQLServerPlatform) {
            return 'mssql';
        }

        if ($platform instanceof SqlitePlatform) {
            return 'sqlite';
        }

        throw new \RuntimeException('Unknown platform '.$platform::class);
    }

    public static function isPostgreSQL(AbstractPlatform $platform): bool
    {
        return $platform instanceof PostgreSQLPlatform;
    }

    public static function isMySQL(AbstractPlatform $platform): bool
    {
        return $platform instanceof AbstractMySQLPlatform;
    }

    /**
     * Returns case-insensitive LIKE / ILIKE expression.
     *
     * PostgreSQL → ILIKE (faster, no LOWER needed in most cases)
     * MySQL      → LIKE (with optional LOWER for consistency when search term is lowercased)
     *
     * @param bool $lowerValue Set true only when the right-hand side is a literal that still needs LOWER()
     *                         (rare – most of the code already lowercases the parameter)
     */
    public static function getCaseInsensitiveLike(
        AbstractPlatform $platform,
        string $column,
        string $valueOrParameter,
        bool $ensureCast = false,
        bool $lowerColumn = false,
        bool $lowerValue = false,
        bool $forceLower = false,
    ): string {
        $col = $ensureCast ? self::castIfStrict($platform, $column) : $column;

        if (self::isPostgreSQL($platform)) {
            if ($forceLower) {
                return 'LOWER('.$col.') LIKE LOWER('.$valueOrParameter.')';
            }

            return $col.' ILIKE '.$valueOrParameter;
        }

        // MySQL
        if ($lowerColumn || $lowerValue) {
            return ($lowerColumn ? 'LOWER('.$col.')' : $col).' LIKE '.($lowerValue ? 'LOWER('.$valueOrParameter.')' : $col);
        }

        return $col.' LIKE '.$valueOrParameter;
    }

    /**
     * REGEXP handling (positive and negative).
     *
     * MySQL:   column REGEXP pattern
     *          NOT column REGEXP pattern
     *
     * PostgreSQL: column ~* pattern     (case-insensitive)
     *             column !~* pattern
     *
     * @param bool $negative true for NOT REGEXP / notRegexp
     */
    public static function getRegexpExpression(
        AbstractPlatform $platform,
        string $column,
        string $pattern,
        bool $negative = false,
    ): string {
        if (self::isPostgreSQL($platform)) {
            $operator = $negative ? '!~*' : '~*';

            return $column.' '.$operator.' '.$pattern;
        }

        // MySQL
        $operator = $negative ? 'NOT REGEXP' : 'REGEXP';

        return $column.' '.$operator.' '.$pattern;
    }

    /**
     * Returns the full date construct expression used in charts (group by + display).
     *
     * Handles timezone offset, weekly grouping differences, and platform-specific formatting.
     *
     * @param string $unit Time unit key ('d', 'H', 'W', 'm', 'Y', ...)
     */
    public static function getDateConstructExpression(
        AbstractPlatform $platform,
        string $columnName,
        string $unit,
        string $defaultTimezoneOffset = '+00:00',
    ): string {
        $dbUnit                = self::getTimeUnitFormat($platform, $unit);

        if (self::isPostgreSQL($platform)) {
            // Shift the UTC-stored timestamp to the user's local offset
            // Works whether the column is timestamp or timestamptz
            // ::timestamp strips any timezone info to avoid session TimeZone influence
            $tzAdjusted = "({$columnName} + '{$defaultTimezoneOffset}'::interval)::timestamp";
            // Special handling for weekly grouping ('W' unit → '%Y %U')
            // MySQL %U = Sunday-based week 00–53
            // We approximate with ISO week (Monday-based, 01–53) – common compromise in ports
            // Padded with space like "2026 03" for identical grouping/label behavior
            $sql = ('IYYY IW' === $dbUnit) ?
                "TO_CHAR({$tzAdjusted}, 'YYYY') || ' ' || LPAD(TO_CHAR({$tzAdjusted}, 'IW')::text, 2, '0')" :
                "TO_CHAR({$tzAdjusted}, '{$dbUnit}')";
        } else {
            // MySQL / MariaDB
            $columnName = "CONVERT_TZ($columnName, '+00:00', '{$defaultTimezoneOffset}')";

            $sql = 'DATE_FORMAT('.$columnName.', \''.$dbUnit.'\')';
        }

        return $sql;
    }

    /**
     * Returns the correct format string for the given unit.
     */
    public static function getTimeUnitFormat(AbstractPlatform $platform, string $unit): string
    {
        $formats = self::isPostgreSQL($platform) ? self::$postgresqlTimeUnits : self::$mysqlTimeUnits;

        if (!isset($formats[$unit])) {
            throw new \UnexpectedValueException('Date/Time unit "'.$unit.'" is not available for '.(self::isPostgreSQL($platform) ? 'PostgreSQL' : 'MySQL').'.');
        }

        return $formats[$unit];
    }

    /**
     * Returns best-hour formatting expression for time-of-day grouping.
     *
     * Returns the full SELECT expression for "hour - next hour" string + COUNT.
     */
    public static function getBestHoursSelectExpression(
        AbstractPlatform $platform,
        string $column,           // e.g. 't.date_read'
        int $timeFormat = 24,     // 12 or 24
        string $offset  = '+00:00',
    ): string {
        $format = (12 === $timeFormat) ? 'HH12 AM' : 'HH24:00';
        if (self::isPostgreSQL($platform)) {
            // PostgreSQL: convert UTC to local timezone
            $localTime = "({$column} AT TIME ZONE 'UTC' AT TIME ZONE '{$offset}')";

            return "
                TO_CHAR({$localTime}, '{$format}') || '-' || 
                TO_CHAR({$localTime} + INTERVAL '1 hour', '{$format}') as hour, 
                COUNT(t.id) AS count
            ";
        }

        // MySQL
        $localTime = "CONVERT_TZ({$column}, '+00:00', '{$offset}')";
        $format    = (12 === $timeFormat) ? '%h %p' : '%H:00';

        return "
            CONCAT(
                TIME_FORMAT({$localTime}, '{$format}'), '-', 
                TIME_FORMAT({$localTime} + INTERVAL 1 HOUR, '{$format}')
            ) as hour, 
            COUNT(t.id) AS count
        ";
    }

    /**
     * Returns read delay formula (time difference between date_sent and date_read).
     *
     * PostgreSQL: TO_CHAR(date_read - date_sent, 'HH24:MI:SS')
     * MySQL:      TIMEDIFF(date_read, date_sent)
     *
     * Returns '-' when date_read is NULL.
     */
    public static function getReadDelayFormula(AbstractPlatform $platform, string $sentColumn = 'es.date_sent', string $readColumn = 'es.date_read'): string
    {
        if (self::isPostgreSQL($platform)) {
            return "CASE WHEN {$readColumn} IS NOT NULL THEN TO_CHAR({$readColumn} - {$sentColumn}, 'HH24:MI:SS') ELSE '-' END";
        }

        return "CASE WHEN {$readColumn} IS NOT NULL THEN TIMEDIFF({$readColumn}, {$sentColumn}) ELSE '-' END";
    }

    /**
     * Returns date difference in seconds expression (used for read delay, time-between events, etc.).
     *
     * PostgreSQL: EXTRACT(EPOCH FROM (date1 - date2))
     * MySQL:      TIMESTAMPDIFF(SECOND, date1, date2)
     */
    public static function getDateDiffInSeconds(
        AbstractPlatform $platform,
        string $dateColumn1,
        string $dateColumn2,
    ): string {
        if (self::isPostgreSQL($platform)) {
            return "EXTRACT(EPOCH FROM ({$dateColumn1} - {$dateColumn2}))";
        }

        return "TIMESTAMPDIFF(SECOND, {$dateColumn1}, {$dateColumn2})";
    }

    /**
     * Return characters length expression.
     *
     * PostgreSQL: LENGTH(col)
     * MySQL:      CHAR_LENGTH(col)
     */
    public static function getLength(
        AbstractPlatform $platform,
        string $column,
    ): string {
        if (self::isPostgreSQL($platform)) {
            return "LENGTH({$column})";
        }

        return "CHAR_LENGTH({$column})";
    }

    /**
     * Returns GROUP_CONCAT / STRING_AGG expression.
     *
     * PostgreSQL: STRING_AGG(..., ',' ORDER BY ...)
     * MySQL:      GROUP_CONCAT(..., ORDER BY ... SEPARATOR ',')
     */
    public static function getGroupConcat(
        AbstractPlatform $platform,
        string $expression,
        string $separator = ',',
        ?string $orderBy = null,
    ): string {
        if (self::isPostgreSQL($platform)) {
            $sql = "STRING_AGG({$expression}, '{$separator}'";

            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }

            $sql .= ')';

            return $sql;
        }

        // MySQL
        $sql = "GROUP_CONCAT({$expression}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $sql .= " SEPARATOR '{$separator}')";

        return $sql;
    }

    /**
     * Returns expression that safely casts to integer.
     *
     * MySQL:      just the column (already numeric or implicitly cast)
     * PostgreSQL: explicit cast to int because status_code is often stored as varchar/text
     */
    public static function applyTypeIfStrict(AbstractPlatform $platform, string $column, string $type='integer'): string
    {
        if (self::isPostgreSQL($platform)) {
            return '('.$column.')::'.$type;
        }

        return $column;
    }

    /**
     * Returns column wrapped with CAST AS text when needed for PostgreSQL.
     *
     * Used for JSON, properties, array-type fields, etc.
     */
    public static function castIfStrict(AbstractPlatform $platform, string $column, $type='text'): string
    {
        if (self::isPostgreSQL($platform)) {
            return "CAST({$column} AS {$type})";
        }

        return $column;
    }

    /**
     * Returns regex pattern for "in" / "notIn" delimited matching (e.g. |value|).
     * PostgreSQL uses ~* (case-insensitive), MySQL uses REGEXP.
     */
    public static function getDelimitedRegexPattern(
        AbstractPlatform $platform,
        string $value,
    ): string {
        $escaped = preg_quote($value, '~');
        if (self::isPostgreSQL($platform)) {
            return '\\|?'.$escaped.'\\|?';
        }

        return "\\|?$value\\|?";
    }

    /**
     * Returns normalized value for comparison on PostgreSQL (numeric/boolean coercion).
     *
     * On MySQL we keep original value (implicit casting works).
     * On PostgreSQL we explicitly cast when field type is numeric or boolean.
     */
    public static function normalizeComparisonValue(
        AbstractPlatform $platform,
        mixed $value,
        ?string $fieldType = null,
        bool $isStringPatternOperator = false,
    ): mixed {
        if (!self::isPostgreSQL($platform) || !is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($fieldType && !$isStringPatternOperator) {
            $numericTypes = ['number', 'int', 'integer', 'float'];

            if (in_array($fieldType, $numericTypes, true)) {
                if (is_numeric($trimmed)) {
                    return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
                }

                return 0; // MySQL-style fallback
            }

            if ('boolean' === $fieldType) {
                return filter_var($trimmed, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        return $value; // keep as string for text fields, pattern operators, etc.
    }

    /**
     * Returns ID handling for raw INSERT when Doctrine cannot auto-generate the ID.
     *
     * MySQL:      no ID column in INSERT (auto_increment)
     * PostgreSQL: id = NEXTVAL('table_id_seq')
     */
    public static function getInsertIdValues(
        AbstractPlatform $platform,
        string $tableName,
        string $idColumn = 'id',
    ): array {
        if (self::isPostgreSQL($platform)) {
            $sequence = $tableName.'_'.$idColumn.'_seq';

            return ['id' => "NEXTVAL('{$sequence}')"];
        }

        return [];
    }

    /**
     * Returns platform-specific ID column and SELECT part for INSERT when using direct SQL.
     *
     * PostgreSQL needs explicit nextval() because Doctrine does not auto-handle sequences in raw SQL.
     * MySQL uses auto_increment (no id column in INSERT).
     */
    public static function getInsertIdHandling(
        AbstractPlatform $platform,
        string $tableName,
        ?ClassMetadata $metadata = null,
        string $idColumn = 'id',
    ): array {
        if (self::isPostgreSQL($platform)) {
            // Get sequence name from metadata (or fallback to Doctrine default)
            $sequenceName = $metadata ? $metadata->getSequenceName($platform) : $tableName.'_'.$idColumn.'_seq';

            return [
                'idColumn' => $idColumn.', ',
                'idSelect' => "nextval('{$sequenceName}') AS {$idColumn}, ",
            ];
        }

        return [
            'idColumn' => '',
            'idSelect' => '',
        ];
    }

    /**
     * Returns the locking clause for SELECT ... FOR SHARE / LOCK IN SHARE MODE.
     *
     * PostgreSQL:  FOR SHARE
     * MySQL:       LOCK IN SHARE MODE
     */
    public static function getShareLockClause(AbstractPlatform $platform): string
    {
        if (self::isPostgreSQL($platform)) {
            return ' FOR SHARE';
        }

        return ' LOCK IN SHARE MODE';
    }

    /**
     * Resets the auto-increment / sequence of a table to a specific value.
     *
     * MySQL:      ALTER TABLE ... AUTO_INCREMENT = X
     * PostgreSQL: ALTER SEQUENCE ... RESTART WITH X
     */
    public static function resetAutoIncrement(
        Connection $connection,
        string $tableName,
        int $startWith = 1,
        string $column = 'id',
    ): void {
        $platform = $connection->getDatabasePlatform();

        if (self::isPostgreSQL($platform)) {
            // Try to get sequence name via pg_get_serial_sequence first
            $sequence = $connection->fetchOne(
                "SELECT pg_get_serial_sequence(?, '{$column}')",
                [$tableName]
            );

            // Fallback to Doctrine-style sequence name
            if (!$sequence) {
                $fallback = $tableName.'_'.$column.'_seq';
                if ($connection->fetchOne(
                    "SELECT 1 FROM pg_class WHERE relname = ? AND relkind = 'S'",
                    [$fallback]
                )) {
                    $sequence = $fallback;
                }
            }

            if ($sequence) {
                $connection->executeStatement(
                    sprintf(
                        'ALTER SEQUENCE %s RESTART WITH %d',
                        $connection->quoteIdentifier($sequence),
                        $startWith
                    )
                );
            }

            return;
        }

        // MySQL / MariaDB
        $connection->executeStatement(
            'ALTER TABLE '.$tableName.' AUTO_INCREMENT='.$startWith
        );
    }

    /**
     * Returns platform-specific upsert (INSERT ... ON CONFLICT / ON DUPLICATE KEY) SQL.
     *
     * @param string $tableName  Target table
     * @param string $columns    Target columns
     * @param string $innerSql   Subquery that provides the data
     * @param string $conflictOn Columns that form the unique constraint (for Postgres ON CONFLICT)
     * @param array  $updateSet  Columns to update on conflict (without table prefix)
     */
    public static function getUpsertStatement(
        AbstractPlatform $platform,
        string $tableName,
        string $columns,
        string $innerSql,
        string $conflictOn,
        array $updateSet,
    ): string {
        if (self::isPostgreSQL($platform)) {
            $setClauses = [];
            foreach ($updateSet as $col) {
                $setClauses[] = "{$col} = EXCLUDED.{$col}";
            }

            return "
                INSERT INTO {$tableName} ({$columns})
                SELECT * FROM ({$innerSql}) AS tmp
                ON CONFLICT ({$conflictOn})
                DO UPDATE SET
                    ".implode(',', $setClauses).'
            ';
        }

        // MySQL
        $setClauses = [];
        foreach ($updateSet as $col) {
            $setClauses[] = "{$col} = VALUES({$col})";
        }

        return "
            INSERT INTO {$tableName} ({$columns})
            SELECT {$columns} FROM ({$innerSql}) AS tmp
            ON DUPLICATE KEY UPDATE
                ".implode(',', $setClauses).'
        ';
    }

    /**
     * Returns "ADD" or "ADD COLUMN" depending on platform.
     *
     * MySQL:      ADD column_definition
     * PostgreSQL: ADD COLUMN column_definition
     */
    public static function getAddColumnKeyword(AbstractPlatform $platform): string
    {
        if (self::isPostgreSQL($platform)) {
            return 'ADD COLUMN';
        }

        return 'ADD';
    }

    /**
     * Returns full column definition for a GENERATED column.
     *
     * Handles differences in GENERATED ALWAYS AS syntax, STORED/VIRTUAL, and comments.
     */
    public static function getGeneratedColumnDefinition(
        AbstractPlatform $platform,
        string $columnType,
        string $expression,
        bool $stored = true,
    ): string {
        if (self::isPostgreSQL($platform)) {
            $as      = 'GENERATED ALWAYS AS';
            $stored  = $stored ? ' STORED' : ' VIRTUAL';   // PG 12-17 requires STORED; 18+ supports VIRTUAL
            $comment = '';                                // PostgreSQL does not support COMMENT in ADD COLUMN for generated columns
        } else {
            $as      = 'AS';
            $stored  = $stored ? ' STORED' : '';
            $comment = " COMMENT '(DC2Type:generated)'";
        }

        return "{$columnType} {$as} ({$expression}){$stored}{$comment}";
    }

    /**
     * Returns whether the platform requires explicit handling for text columns in indexes.
     *
     * MySQL:      text columns cannot be part of indexes without length prefix (Doctrine already enforces this).
     * PostgreSQL: text columns are fully supported in indexes.
     */
    public static function allowsTextInIndex(AbstractPlatform $platform): bool
    {
        return self::isPostgreSQL($platform);
    }

    /**
     * Returns list of indexes for a table in a platform-agnostic way.
     *
     * Custom reliable index listing for PostgreSQL (fallback to Doctrine for other platforms)
     * This bypasses the buggy Doctrine introspection in older DBAL versions (below 4.0)
     * (the deprecated getListTableIndexesSQL misses indexes due to flawed joins/filters).
     *
     * @return Index[]
     */
    public static function listTableIndexes(
        Connection $connection,
        string $fullTableName,
    ): array {
        $platform = $connection->getDatabasePlatform();

        if (!self::isPostgreSQL($platform)) {
            // Let Doctrine handle MySQL/MariaDB and other platforms
            $schemaManager = $connection->createSchemaManager();

            return $schemaManager->listTableIndexes($fullTableName);
        }

        // Reliable custom query for PostgreSQL
        $sql = "
            SELECT
                i.relname AS index_name,
                array_agg(a.attname ORDER BY c.ordinality) AS columns,
                ix.indisunique AS is_unique,
                ix.indisprimary AS is_primary,
                t.relkind AS relation_kind
            FROM
                pg_class t
                JOIN pg_namespace ns ON ns.oid = t.relnamespace
                LEFT JOIN pg_index ix ON t.oid = ix.indrelid
                LEFT JOIN pg_class i ON ix.indexrelid = i.oid
                LEFT JOIN unnest(ix.indkey) WITH ORDINALITY AS c(attnum, ordinality) ON true
                LEFT JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = c.attnum
            WHERE
                t.relkind IN ('r', 'i', 'm', 'p')
                AND t.relname = :table
                AND ns.nspname = CURRENT_SCHEMA()
            GROUP BY
                i.relname, t.relkind, ix.indisunique, ix.indisprimary, i.oid
            ORDER BY
                i.relname;
        ";

        $stmt = $connection->prepare($sql);
        $stmt->bindValue('table', $fullTableName);
        $results = $stmt->executeQuery()->fetchAllAssociative();

        $indexes = [];
        foreach ($results as $row) {
            $columns = $row['columns'];

            // Handle both native PHP array and string representation {col1,col2}
            if (is_string($columns)) {
                $columnsStr = trim($columns, '{}');
                $columns    = explode(',', $columnsStr);
                $columns    = array_map(fn ($part) => trim($part, '"'), $columns);
            }

            $indexes[] = new Index(
                $row['index_name'],
                $columns,
                (bool) $row['is_unique'],
                (bool) $row['is_primary']
            );
        }

        return $indexes;
    }

    /**
     * Returns platform-specific DISTINCT expression for COUNT(DISTINCT ...).
     *
     * MySQL:      DISTINCT col1, col2, col3
     * PostgreSQL: DISTINCT (col1, col2, col3)   — required for multi-column DISTINCT
     */
    public static function getDistinctMultiColumnExpression(
        AbstractPlatform $platform,
        string ...$columns,
    ): string {
        $columnList = implode(', ', $columns);

        if (self::isPostgreSQL($platform)) {
            return "DISTINCT ({$columnList})";
        }

        return "DISTINCT {$columnList}";
    }

    /**
     * Returns platform-specific quoting for a column or alias.
     *
     * MySQL:      `table`.`column`  or  `label`
     * PostgreSQL: "table"."column"  or  "label"
     */
    public static function quoteIdentifier(AbstractPlatform $platform, string $identifier): string
    {
        if (self::isPostgreSQL($platform)) {
            return '"'.$identifier.'"';
        }

        return '`'.$identifier.'`';
    }

    /**
     * Returns fully quoted "tableAlias"."columnName" or `tableAlias`.`columnName`.
     *
     * Expects input in format "table_alias.column_name".
     */
    public static function quoteColumn(AbstractPlatform $platform, string $fullColumnName, bool $isIdentifier = false): string
    {
        if (false === strpos($fullColumnName, '.') || $isIdentifier) {
            return self::quoteIdentifier($platform, $fullColumnName);
        }

        [$tableAlias, $columnName] = explode('.', $fullColumnName, 2);

        if (self::isPostgreSQL($platform)) {
            return '"'.$tableAlias.'"."'.$columnName.'"';
        }

        return '`'.$tableAlias.'`.`'.$columnName.'`';
    }

    /**
     * Removes platform-specific quoting from a column identifier (for normalization/comparison).
     */
    public static function unquoteIdentifier(AbstractPlatform $platform, string $fullColumnName): string
    {
        if (self::isPostgreSQL($platform)) {
            return preg_match('/^["a-zA-Z0-9_\.\$]+$/', $fullColumnName)
                ? str_replace('"', '', $fullColumnName)
                : $fullColumnName;
        }

        // MySQL
        return preg_match('/^[`a-zA-Z0-9_\.\$]+$/', $fullColumnName)
            ? str_replace('`', '', $fullColumnName)
            : $fullColumnName;
    }

    /**
     * Returns aggregator expression with platform-specific precision handling.
     *
     * MySQL:      AVG(column)
     * PostgreSQL: AVG(column)::numeric(10, 4)   — ensures consistent decimal precision
     */
    public static function getAggregatorExpression(
        AbstractPlatform $platform,
        string $aggregator,
        string $expression,
    ): string {
        $sql = sprintf('%s(%s)', $aggregator, $expression);
        if (self::isPostgreSQL($platform) && 'AVG' == $aggregator) {
            return $sql.'::numeric(10, 4)';
        }

        return $sql;
    }

    /**
     * Returns whether the platform requires explicit handling for indexes hints.
     *
     * MySQL:      text columns cannot be part of indexes without length prefix (Doctrine already enforces this).
     * PostgreSQL: text columns are fully supported in indexes.
     */
    public static function allowsIndexHint(AbstractPlatform $platform): bool
    {
        return self::isMySQL($platform);
    }

    /**
     * Returns interval expression for date arithmetic.
     *
     * MySQL:      INTERVAL 30 MINUTE
     * PostgreSQL: INTERVAL '30 MINUTES'
     */
    public static function getIntervalExpression(
        AbstractPlatform $platform,
        int $value,
        string $unit = 'MINUTE',
    ): string {
        if (self::isPostgreSQL($platform)) {
            // PostgreSQL prefers plural form and quoted string
            $unit = rtrim($unit, 'S').'S'; // ensure plural

            return "INTERVAL '{$value} {$unit}'";
        }

        // MySQL
        return "INTERVAL {$value} {$unit}";
    }

    /**
     * Returns expression to adjust a timestamp by timezone offset (in seconds).
     *
     * PostgreSQL: column + (offset || ' second')::interval
     * MySQL:      TIMESTAMPADD(SECOND, offset, column)
     */
    public static function getOffsetAdjustedDate(
        AbstractPlatform $platform,
        string $column,
        string $offsetParam = ':timezoneOffset',
    ): string {
        if (self::isPostgreSQL($platform)) {
            return "{$column} + ({$offsetParam} || ' second')::interval";
        }

        return "TIMESTAMPADD(SECOND, {$offsetParam}, {$column})";
    }

    /**
     * Hour extraction.
     */
    public static function getHourExpression(AbstractPlatform $platform, string $column): string
    {
        if (self::isPostgreSQL($platform)) {
            return "TO_CHAR({$column}, 'HH24')";
        }

        return "TIME_FORMAT({$column}, '%H')";
    }

    /**
     * Returns date subtraction expression for "last X interval".
     *
     * PostgreSQL: NOW() - INTERVAL '1 DAY'
     * MySQL:      DATE_SUB(NOW(), INTERVAL 1 DAY)
     */
    public static function getDateSubExpression(
        AbstractPlatform $platform,
        string $intervalUnit = 'DAY',   // 'DAY', 'WEEK', 'MONTH'
        int $value = 1,
    ): string {
        $intervalUnit = strtoupper($intervalUnit);

        if (self::isPostgreSQL($platform)) {
            return "NOW() - INTERVAL '{$value} {$intervalUnit}'";
        }

        return "DATE_SUB(NOW(), INTERVAL {$value} {$intervalUnit})";
    }

    /**
     * Weekday calculation (0 = Monday ... 6 = Sunday).
     */
    public static function getWeekdayExpression(AbstractPlatform $platform, string $column): string
    {
        if (self::isPostgreSQL($platform)) {
            return "FLOOR((EXTRACT(DOW FROM {$column}) + 6)::int % 7)";
        }

        return "WEEKDAY({$column})";
    }

    /* ===================================================================
     * LIKE / ILIKE helpers
     * =================================================================== */

    /**
     * Returns the correct LIKE operator for case-insensitive search.
     *
     * In ORM context we always use LOWER(column) LIKE on both platforms.
     * In raw DBAL we can use native ILIKE on PostgreSQL.
     */
    public static function getLikeOperator(
        AbstractPlatform $platform,
        bool $isOrm = true,
    ): string {
        if (self::isPostgreSQL($platform) && !$isOrm) {
            return 'ILIKE';
        }

        return 'LIKE';
    }

    /**
     * Returns whether the search value should be lowercased.
     *
     * On PostgreSQL + ORM we lowercase the value because we use LOWER(column).
     */
    public static function shouldLowercaseSearchValue(
        AbstractPlatform $platform,
        bool $isOrm = true,
    ): bool {
        return self::isPostgreSQL($platform) && $isOrm;
    }
}
