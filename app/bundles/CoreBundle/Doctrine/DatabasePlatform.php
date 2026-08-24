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
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\LeadBundle\Entity\Tag;

/**
 * Central abstraction point for database platform differences (MySQL vs PostgreSQL).
 *
 * Also a workaround for deprecated \Doctrine\DBAL\Platforms\AbstractPlatform::getName.
 */
final class DatabasePlatform
{
    /* ===================================================================
     * Platform detection helpers
     * =================================================================== */

    /**
     * Returns a short string identifier for the given database platform.
     *
     * This is a workaround for the deprecated \Doctrine\DBAL\Platforms\AbstractPlatform::getName() method.
     */
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

    /**
     * Checks whether the given platform is PostgreSQL.
     */
    public static function isPostgreSQL(?AbstractPlatform $platform): bool
    {
        return $platform instanceof PostgreSQLPlatform;
    }

    /**
     * Checks whether the given platform is MySQL.
     */
    public static function isMySQL(?AbstractPlatform $platform): bool
    {
        return $platform instanceof AbstractMySQLPlatform;
    }

    /* ===================================================================
     * LIKE / ILIKE helpers
     * =================================================================== */

    // Define bitwise flags
    // 1 << 0
    public const FLAG_ENSURE_CAST        = 1;

    // 1 << 1
    public const FLAG_LOWER_COLUMN       = 2;

    // 1 << 2
    public const FLAG_LOWER_VALUE        = 4;

    // 1 << 3
    public const FLAG_FORCE_LOWER_COLUMN = 8;

    // 1 << 4
    public const FLAG_FORCE_LOWER_VALUE  = 16;

    // 1 << 5
    public const FLAG_NEGATIVE           = 32;

    /**
     * Returns case-insensitive LIKE / ILIKE expression.
     *
     * PostgreSQL → ILIKE (faster, no LOWER needed in most cases)
     * MySQL      → LIKE (with optional LOWER for consistency when search term is lowercased)
     */
    public static function getCaseInsensitiveLike(
        ?AbstractPlatform $platform,
        string $column,
        string $valueOrParameter,
        int $flags = 0,
    ): string {
        // Extract flags into local booleans for readability
        $ensureCast       = ($flags & self::FLAG_ENSURE_CAST);
        $lowerColumn      = ($flags & self::FLAG_LOWER_COLUMN);
        $lowerValue       = ($flags & self::FLAG_LOWER_VALUE);
        $forceLowerColumn = ($flags & self::FLAG_FORCE_LOWER_COLUMN);
        $forceLowerValue  = ($flags & self::FLAG_FORCE_LOWER_VALUE);
        $negative         = ($flags & self::FLAG_NEGATIVE);

        $col = $ensureCast ? self::castIfStrict($platform, $column) : $column;
        $not = $negative ? ' NOT ' : ' ';

        if (self::isPostgreSQL($platform)) {
            if ($forceLowerColumn || $forceLowerValue) {
                $c = $forceLowerColumn ? "LOWER($col)" : $col;
                $v = $forceLowerValue ? "LOWER($valueOrParameter)" : $valueOrParameter;

                return "{$c}{$not}LIKE {$v}";
            }

            return "{$col}{$not}ILIKE {$valueOrParameter}";
        }

        // MySQL / Default
        $c = ($lowerColumn || $forceLowerColumn) ? "LOWER($col)" : $col;
        $v = ($lowerValue || $forceLowerValue) ? "LOWER($valueOrParameter)" : $valueOrParameter;

        return "{$c}{$not}LIKE {$v}";
    }

    /* ===================================================================
     * REGEXP helpers
     * =================================================================== */

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
        ?AbstractPlatform $platform,
        string $column,
        string $pattern,
        bool $negative = false,
    ): string {
        if (self::isPostgreSQL($platform)) {
            // PostgreSQL
            $operator = $negative ? '!~*' : '~*';
        } else {
            // MySQL
            $operator = $negative ? 'NOT REGEXP' : 'REGEXP';
        }

        return $column.' '.$operator.' '.$pattern;
    }

    /**
     * Returns regex pattern for "in" / "notIn" delimited matching (e.g. |value|).
     * PostgreSQL uses ~* (case-insensitive), MySQL uses REGEXP.
     */
    public static function getDelimitedRegexPattern(
        ?AbstractPlatform $platform,
        string $value,
    ): string {
        return '\\|?'.(self::isPostgreSQL($platform) ? preg_quote($value, '~') : $value).'\\|?';
    }

    /* ===================================================================
     * Date / Time helpers
     * =================================================================== */

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

    /**
     * Returns the full date construct expression (ex: used in charts).
     *
     * Handles timezone offset, weekly grouping differences, and platform-specific formatting.
     *
     * @param string $unit Time unit key ('d', 'H', 'W', 'm', 'Y', ...)
     */
    public static function getDateConstructExpression(
        ?AbstractPlatform $platform,
        string $columnName,
        string $unit,
        bool $ignoreZeroTimezoneOffset = false,
        string $defaultTimezoneOffset = '+00:00',
    ): string {
        $dbUnit   = self::getTimeUnitFormat($platform, $unit);

        // Check if timezone adjustment is needed
        $isZeroTz = $ignoreZeroTimezoneOffset && in_array(trim($defaultTimezoneOffset), ['+00:00', '-00:00', '+0', '-0', '0', 'Z', 'UTC'], true);

        if (self::isPostgreSQL($platform)) {
            // Shift the UTC-stored timestamp to the user's local offset
            // Works whether the column is timestamp or timestamptz
            // ::timestamp strips any timezone info to avoid session TimeZone influence
            // Apply timezone interval only if offset is non-zero
            $formattedColumn = $isZeroTz
                ? "({$columnName})::timestamp"
                : "({$columnName} + '{$defaultTimezoneOffset}'::interval)::timestamp";
            // Special handling for weekly grouping ('W' unit → '%Y %U')
            // MySQL %U = Sunday-based week 00–53
            // We approximate with ISO week (Monday-based, 01–53) – common compromise in ports
            // Padded with space like "2026 03" for identical grouping/label behavior
            $sql = ('IYYY IW' === $dbUnit) ?
                "TO_CHAR({$formattedColumn}, 'YYYY') || ' ' || LPAD(TO_CHAR({$formattedColumn}, 'IW')::text, 2, '0')" :
                "TO_CHAR({$formattedColumn}, '{$dbUnit}')";
        } else {
            // MySQL / MariaDB
            // Skip CONVERT_TZ wrapping if offset is zero
            $formattedColumn = $isZeroTz
                ? $columnName
                : "CONVERT_TZ($columnName, '+00:00', '{$defaultTimezoneOffset}')";

            $sql = "DATE_FORMAT({$formattedColumn}, '{$dbUnit}')";
        }

        return $sql;
    }

    /**
     * Returns the correct format string for the given unit.
     */
    public static function getTimeUnitFormat(?AbstractPlatform $platform, string $unit): string
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
        ?AbstractPlatform $platform,
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
    public static function getReadDelayFormula(?AbstractPlatform $platform, string $sentColumn = 'es.date_sent', string $readColumn = 'es.date_read'): string
    {
        if (self::isPostgreSQL($platform)) {
            return "CASE WHEN {$readColumn} IS NOT NULL THEN TO_CHAR({$readColumn} - {$sentColumn}, 'HH24:MI:SS') ELSE '-' END";
        }

        return "CASE WHEN {$readColumn} IS NOT NULL THEN TIMEDIFF({$readColumn}, {$sentColumn}) ELSE '-' END";
    }

    /**
     * Returns interval expression for date arithmetic.
     *
     * MySQL:      INTERVAL 30 MINUTE
     * PostgreSQL: INTERVAL '30 MINUTES'
     */
    public static function getIntervalExpression(
        ?AbstractPlatform $platform,
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
     * Returns platform-safe expression that extracts only the DATE part from a datetime column.
     *
     * PostgreSQL: make_date(EXTRACT(YEAR ...), EXTRACT(MONTH ...), EXTRACT(DAY ...))
     * MySQL/MariaDB: CONCAT(YEAR(), '-', LPAD(MONTH(),2,'0'), '-', LPAD(DAY(),2,'0'))
     */
    public static function getDateOnlyExpression(
        ?AbstractPlatform $platform,
        string $columnName,
    ): string {
        if (self::isPostgreSQL($platform)) {
            return "make_date(
                EXTRACT(YEAR FROM {$columnName})::int,
                EXTRACT(MONTH FROM {$columnName})::int,
                EXTRACT(DAY FROM {$columnName})::int
            )";
        }

        // MySQL / MariaDB
        return "CONCAT(
            YEAR({$columnName}), '-',
            LPAD(MONTH({$columnName}), 2, '0'), '-',
            LPAD(DAY({$columnName}), 2, '0')
        )";
    }

    /**
     * Returns expression to adjust a timestamp by timezone offset (in seconds).
     *
     * PostgreSQL: column + (offset || ' second')::interval
     * MySQL:      TIMESTAMPADD(SECOND, offset, column)
     */
    public static function getOffsetAdjustedDate(
        ?AbstractPlatform $platform,
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
    public static function getHourExpression(?AbstractPlatform $platform, string $column): string
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
        ?AbstractPlatform $platform,
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
    public static function getWeekdayExpression(?AbstractPlatform $platform, string $column): string
    {
        if (self::isPostgreSQL($platform)) {
            return "FLOOR((EXTRACT(DOW FROM {$column}) + 6)::int % 7)";
        }

        return "WEEKDAY({$column})";
    }

    /**
     * Returns date difference in seconds expression (used for read delay, time-between events, etc.).
     *
     * PostgreSQL: EXTRACT(EPOCH FROM (date1 - date2))
     * MySQL:      TIMESTAMPDIFF(SECOND, date1, date2)
     */
    public static function getDateDiffInSeconds(
        ?AbstractPlatform $platform,
        string $dateColumn1,
        string $dateColumn2,
    ): string {
        if (self::isPostgreSQL($platform)) {
            return "EXTRACT(EPOCH FROM ({$dateColumn1} - {$dateColumn2}))";
        }

        return "TIMESTAMPDIFF(SECOND, {$dateColumn1}, {$dateColumn2})";
    }

    public static function getTimeSpentFormula(?AbstractPlatform $platform, string $hitPrefix): string
    {
        $hit  = $hitPrefix.'date_hit';
        $left = $hitPrefix.'date_left';

        if (self::isPostgreSQL($platform)) {
            $thenExpr = "TO_CHAR(({$left} - {$hit}), 'HH24:MI:SS')";
        } else {
            $thenExpr = 'SEC_TO_TIME('.self::getDateDiffInSeconds($platform, $left, $hit).')';
        }

        return "CASE WHEN {$left} IS NOT NULL
                    THEN {$thenExpr}
                    ELSE '' END";
    }

    /* ===================================================================
     * Upsert helpers
     * =================================================================== */

    /**
     * Returns the platform-specific conflict target for upsert.
     *
     * Prefers non-primary unique constraint if available, otherwise falls back to primary key.
     */
    public static function getUpsertConflictTarget(Connection $connection, ClassMetadata $metadata, string $pkColumn): string
    {
        /**
         * Currently Unique Constrains are only used in PostgreSQL
         * So this logic no need to run on any platform beside it.
         */
        if (self::isPostgreSQL($connection->getDatabasePlatform())) {
            // From mapping attributes/annotations
            $uniqueConstraints = $metadata->table['uniqueConstraints'] ?? [];

            foreach ($uniqueConstraints as $uc) {
                $cols = $uc['columns'];
                // Prefer if it doesn't include PK or has more columns
                if (count($cols) > 1 || !in_array($pkColumn, $cols, true)) {
                    return implode(', ', $cols);
                }
            }

            // Fallback: introspect unique indexes (runtime, requires schema access)
            try {
                $helper = new IndexSchemaHelper(
                    $connection,
                    MAUTIC_TABLE_PREFIX
                );

                $indexes = $helper->getTableIndexes($metadata->getTableName());
                foreach ($indexes as $index) {
                    if ($index->isUnique() && !$index->isPrimary()) {
                        $cols = $index->getColumns();
                        if (count($cols) > 1 || !in_array($pkColumn, $cols, true)) {
                            return implode(', ', $cols);
                        }
                    }
                }
            } catch (\Throwable) {
                // Silent fallback if introspection fails
            }
        }

        return $pkColumn; // Fallback: if nothing was found pk column is returned as default
    }

    /**
     * Returns the platform-specific UPDATE expression for upsert.
     *
     * PostgreSQL uses EXCLUDED table, MySQL uses VALUES() function.
     */
    public static function getUpsertUpdateExpression(
        AbstractPlatform $platform,
        string $column,
    ): string {
        if (self::isPostgreSQL($platform)) {
            return "{$column} = EXCLUDED.{$column}";
        }

        return "{$column} = VALUES({$column})";
    }

    /**
     * Universal upsert (REPLACE INTO on MySQL, INSERT ... ON CONFLICT on PostgreSQL).
     *
     * @param string        $tableName     Full table name (with prefix)
     * @param array<string> $columns       List of columns to insert
     * @param string        $selectSql     Sub-select that provides the data (the SELECT part)
     * @param string        $conflictOn    Column(s) for ON CONFLICT (PostgreSQL only). Use comma-separated string for multiple columns.
     * @param array<string> $updateColumns Columns to update on conflict (if empty, all columns except conflict keys are updated)
     */
    public static function getUpsertSql(
        ?AbstractPlatform $platform,
        string $tableName,
        array $columns,
        string $selectSql,
        string $conflictOn,
        array $updateColumns = [],
    ): string {
        $columnList = implode(', ', $columns);

        if (self::isPostgreSQL($platform)) {
            $setClauses = [];
            foreach ($updateColumns as $col) {
                $setClauses[] = self::getUpsertUpdateExpression($platform, $col);
            }

            return "
                INSERT INTO {$tableName} ({$columnList})
                {$selectSql}
                ON CONFLICT ({$conflictOn})
                DO UPDATE SET ".implode(', ', $setClauses);
        }

        // MySQL: REPLACE INTO
        return "
            REPLACE INTO {$tableName} ({$columnList})
            {$selectSql}";
    }

    /**
     * Returns platform-specific summarize upsert (INSERT ... ON CONFLICT / ON DUPLICATE KEY) SQL.
     *
     * @param string        $tableName  Target table
     * @param string        $columns    Target columns
     * @param string        $innerSql   Subquery that provides the data
     * @param string        $conflictOn Columns that form the unique constraint (for Postgres ON CONFLICT)
     * @param array<string> $updateSet  Columns to update on conflict (without table prefix)
     */
    public static function getSummarizeUpsertStatement(
        ?AbstractPlatform $platform,
        string $tableName,
        string $columns,
        string $innerSql,
        string $conflictOn,
        array $updateSet,
    ): string {
        $setClauses = [];
        foreach ($updateSet as $col) {
            $setClauses[] = self::getUpsertUpdateExpression($platform, $col);
        }

        if (self::isPostgreSQL($platform)) {
            return "
                INSERT INTO {$tableName} ({$columns})
                SELECT * FROM ({$innerSql}) AS tmp
                ON CONFLICT ({$conflictOn})
                DO UPDATE SET
                    ".implode(',', $setClauses).'
            ';
        }

        return "
            INSERT INTO {$tableName} ({$columns})
            SELECT {$columns} FROM ({$innerSql}) AS tmp
            ON DUPLICATE KEY UPDATE
                ".implode(',', $setClauses).'
        ';
    }

    /**
     * Processes an identifier field during upsert and directly modifies the provided arrays and $hasId flag by reference.
     *
     * This centralizes all platform-specific identifier handling:
     * - Existing entity → include ID in INSERT
     * - New PostgreSQL entity → use NEXTVAL()
     * - MySQL special case for LAST_INSERT_ID()
     *
     * @param callable           $makeUpdate Function that returns the update expression for a column
     * @param array<string>      &$columns
     * @param array<mixed>       &$values
     * @param array<string|null> &$types
     * @param array<string>      &$set
     * @param array<string>      &$update
     *
     * @param-out array<string>  $columns
     * @param-out array<mixed>   $values
     * @param-out array<string|null> $types
     * @param-out array<string>  $set
     * @param-out array<string>  $update
     *
     * @return bool Whether the identifier was processed as an existing entity (true = $hasId should be set)
     */
    public static function processIdentifierForUpsert(
        Connection $connection,
        object $entity,
        ClassMetadata $metadata,
        string $tableName,
        string $fieldName,
        callable $makeUpdate,
        array &$columns,
        array &$values,
        array &$types,
        array &$set,
        array &$update,
    ): bool {
        $platform   = $connection->getDatabasePlatform();
        $identifier = $metadata->getSingleIdentifierFieldName(); // usually 'id'

        $value  = $metadata->getFieldValue($entity, $fieldName);
        $column = $metadata->getColumnName($fieldName);
        $type   = $metadata->getTypeOfField($fieldName);

        if ($metadata->isIdentifier($fieldName)) {
            if ($value) {
                $columns[] = $column;
                $values[]  = $value;
                $types[]   = $type;
                $set[]     = '?';
                $update[]  = (string) $makeUpdate($column); // Cast to string to satisfy PHPSTAN array<string>

                return true; // Existing entity: include id in INSERT (needed for conflict matching)
            } elseif (self::isPostgreSQL($platform)) {
                // New entity on PG: use nextval in VALUES (no bound param)
                $sequence  = self::getSerialSequence($connection, $tableName, $column);
                $columns[] = $column;
                $set[]     = "NEXTVAL('{$sequence}')";
            } elseif ($fieldName === $identifier) {
                // MySQL special case for LAST_INSERT_ID
                $update[] = "{$column} = LAST_INSERT_ID({$column})";
            }

            return false;
        }

        $columns[] = $column;
        $values[]  = $value;
        $types[]   = $type;
        $set[]     = '?';
        $update[]  = (string) $makeUpdate($column); // Cast to string to satisfy PHPSTAN array<string>

        return false;
    }

    /**
     * Builds and executes platform-specific upsert (INSERT ... ON CONFLICT / ON DUPLICATE KEY).
     *
     * This method handles the full upsert logic including:
     * - Building SQL for PostgreSQL (ON CONFLICT + RETURNING) and MySQL (ON DUPLICATE KEY)
     * - Returning the generated/existing ID to the entity
     * - Detecting whether the operation was an INSERT or UPDATE
     *
     * @param array<string> $columns Target columns
     * @param array<string> $values
     * @param array<string> $types
     * @param array<string> $set
     * @param array<string> $update  Columns to update
     *
     * @return array<bool> {wasInserted, wasUpdated}
     *
     * @throw RecordNotFoundException
     */
    public static function getUpsertStatement(
        Connection $connection,
        object $entity,
        ClassMetadata $metadata,
        string $tableName,
        array $columns = [],
        array $values = [],
        array $types = [],
        array $set = [],
        array $update = [],
        bool $hasId = false,
    ): array {
        $platform   = $connection->getDatabasePlatform();
        $identifier = $metadata->getSingleIdentifierFieldName(); // usually 'id'

        $columnList = implode(', ', $columns);
        $setList    = implode(', ', $set);
        $updateList = implode(', ', $update);

        if (self::isPostgreSQL($platform)) {
            $idColumn   = $metadata->getColumnName($identifier);
            // Detect best conflict target (prefer non-PK unique constraint)
            $conflictTarget = self::getUpsertConflictTarget($connection, $metadata, $idColumn);

            // Always RETURNING to get both the (generated or existing) id and insert detection
            $sql = "INSERT INTO {$tableName} ($columnList) VALUES ($setList) "
                ."ON CONFLICT ($conflictTarget) DO UPDATE SET $updateList "
                ."RETURNING {$idColumn}, (xmax = 0) AS is_new";

            $result = $connection->fetchAssociative($sql, $values, $types);

            if (false === $result) {
                // Should never happen in upsert
                throw new RecordNotFoundException('Upsert failed - no row returned');
            }

            $generatedOrExistingId = (int) $result['id'];
            $wasInserted           = (bool) $result['is_new'];
            $wasUpdated            = !$wasInserted;

            // Always set the ID back (important for new entities)
            $metadata->setFieldValue($entity, $identifier, $generatedOrExistingId);
        } else {
            // MySQL: Use affected rows count (1 = Insert, 2 = Update)
            $sql = "INSERT INTO {$tableName} ($columnList) VALUES ($setList) ".
                "ON DUPLICATE KEY UPDATE $updateList";

            $affectedRows = $connection->executeStatement($sql, $values, $types);
            $wasInserted  = (1 === $affectedRows);
            $wasUpdated   = (2 === $affectedRows);

            if (!$hasId) {
                $id = (int) $connection->lastInsertId();
                $metadata->setFieldValue($entity, $identifier, $id);
            }
        }

        return [$wasInserted, $wasUpdated];
    }

    /* ===================================================================
     * LeadEventLogRepository helpers
     * =================================================================== */

    /**
     * Returns SQL to drop a temporary table (works for both MySQL and PostgreSQL).
     */
    public static function getDropTemporaryTableSql(?AbstractPlatform $platform, string $tempTableName, bool $ifExists = false): string
    {
        $condition = $ifExists ? 'IF EXISTS ' : '';
        $temporary = !self::isPostgreSQL($platform) ? 'TEMPORARY ' : '';

        return "DROP {$temporary}TABLE {$condition}{$tempTableName}";
    }

    /**
     * Returns SQL to create a temporary table from a SELECT.
     * PostgreSQL requires "AS", MySQL does not (and uses different quoting style).
     */
    public static function getCreateTemporaryTableSql(
        ?AbstractPlatform $platform,
        string $tempTableName,
        string $selectSql,
    ): string {
        $as = self::isPostgreSQL($platform) ? ' AS ' : ' ';

        return "CREATE TEMPORARY TABLE {$tempTableName}{$as}{$selectSql}";
    }

    /**
     * Returns DELETE query for batch removal of rows using a temporary table.
     *
     * Supports both single-column and multi-column matching.
     *
     * PostgreSQL: DELETE FROM ... USING (subquery) WHERE col1 = d.col1 AND col2 = d.col2
     * MySQL:      DELETE t FROM ... JOIN (subquery) d USING (col1, col2)
     *
     * @param string[] $joinColumns Columns used for matching (e.g. ['lead_id'] or ['leadlist_id', 'lead_id'])
     */
    public static function getDeleteAnonymousContactsUsingTempTableSql(
        ?AbstractPlatform $platform,
        string $tableName,
        string $tempTableName,
        array $joinColumns,
        int $batchSize,
    ): string {
        $subSelect = 'SELECT '.implode(', ', $joinColumns)." FROM {$tempTableName} LIMIT {$batchSize}";

        if (self::isPostgreSQL($platform)) {
            $whereConditions = [];
            foreach ($joinColumns as $col) {
                $whereConditions[] = "lll.{$col} = d.{$col}";
            }

            return sprintf(
                'DELETE FROM %s lll USING (%s) d WHERE %s',
                $tableName,
                $subSelect,
                implode(' AND ', $whereConditions)
            );
        }

        // MySQL
        $usingClause = implode(', ', $joinColumns);

        return sprintf(
            'DELETE lll FROM %s lll JOIN (%s) d USING (%s)',
            $tableName,
            $subSelect,
            $usingClause
        );
    }

    /**
     * Returns batched DELETE query for removing leads from a specific list (lead_lists_leads table).
     *
     * PostgreSQL requires ctid hack because it does not support LIMIT on DELETE with a simple WHERE.
     * MySQL supports direct LIMIT.
     */
    public static function getDeleteByListIdSql(
        ?AbstractPlatform $platform,
        string $tableName,
        int $batchSize,
        string $listIdParam = ':listId',
    ): string {
        if (self::isPostgreSQL($platform)) {
            return sprintf(
                'DELETE FROM %s
                 WHERE leadlist_id = '.$listIdParam.'
                   AND ctid IN (
                       SELECT ctid
                       FROM %s
                       WHERE leadlist_id = '.$listIdParam.'
                       LIMIT %d
                   )',
                $tableName,
                $tableName,
                $batchSize
            );
        }

        return sprintf(
            'DELETE FROM %s
             WHERE leadlist_id = '.$listIdParam.'
             LIMIT %d',
            $tableName,
            $batchSize
        );
    }

    /* ===================================================================
     * TagModel helpers
     * =================================================================== */

    /**
     * Returns SQL for replacing secondary tag associations with primary tag.
     *
     * Equivalent to MySQL's UPDATE IGNORE on the lead_tags_xref table.
     * Skips rows that would violate the unique (lead_id, tag_id) constraint.
     */
    public static function getUpdateLeadTagAssociationSql(
        ?AbstractPlatform $platform,
        Tag $primaryTag,
        Tag $secondaryTag,
    ): string {
        $primaryId   = (int) $primaryTag->getId();
        $secondaryId = (int) $secondaryTag->getId();

        if (self::isPostgreSQL($platform)) {
            // PostgreSQL version
            return sprintf(
                'UPDATE %slead_tags_xref t1
                 SET tag_id = %d
                 WHERE tag_id = %d
                   AND NOT EXISTS (
                       SELECT 1
                       FROM %slead_tags_xref t2
                       WHERE t2.lead_id = t1.lead_id
                         AND t2.tag_id = %d
                   )',
                MAUTIC_TABLE_PREFIX, $primaryId, $secondaryId, MAUTIC_TABLE_PREFIX, $primaryId
            );
        }

        // MySQL / MariaDB version
        return sprintf('UPDATE IGNORE %slead_tags_xref SET tag_id = %d WHERE tag_id = %d', MAUTIC_TABLE_PREFIX, $primaryId, $secondaryId);
    }

    /* ===================================================================
     * Other helpers
     * =================================================================== */

    /**
     * Returns expression that safely casts to integer.
     *
     * MySQL:      just the column (already numeric or implicitly cast)
     * PostgreSQL: explicit cast to int because status_code is often stored as varchar/text
     */
    public static function applyTypeIfStrict(?AbstractPlatform $platform, string $column, string $type='integer'): string
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
    public static function castIfStrict(?AbstractPlatform $platform, string $column, string $type='text'): string
    {
        if (self::isPostgreSQL($platform)) {
            return "CAST({$column} AS {$type})";
        }

        return $column;
    }

    public const MYSQL_MAX_INDEX_ALLOWED = 64;

    /**
     * In PostgreSQL there is basically no limitation.
     * But for our purpose we set it to reasonable number which will probably be never reached.
     */
    public const POSTGRESQL_MAX_INDEX_ALLOWED = 1024;

    /**
     * Return max index allowed per platform.
     */
    public static function getMaxIndexAllowed(?AbstractPlatform $platform = null): int
    {
        return self::isPostgreSQL($platform) ? self::POSTGRESQL_MAX_INDEX_ALLOWED : self::MYSQL_MAX_INDEX_ALLOWED;
    }

    /**
     * Return characters length expression.
     *
     * PostgreSQL: LENGTH(col)
     * MySQL:      CHAR_LENGTH(col)
     */
    public static function getCharLengthSql(
        ?AbstractPlatform $platform,
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
        ?AbstractPlatform $platform,
        string $expression,
        string $separator = ',',
        ?string $orderBy = null,
    ): string {
        if (self::isPostgreSQL($platform)) {
            $sql = "STRING_AGG({$expression}, '{$separator}'";

            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }

            return $sql.')';
        }

        // MySQL
        $sql = "GROUP_CONCAT({$expression}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $sql." SEPARATOR '{$separator}')";
    }

    /**
     * Returns normalized value for comparison on PostgreSQL (numeric/boolean coercion).
     *
     * On MySQL we keep original value (implicit casting works).
     * On PostgreSQL we explicitly cast when field type is numeric or boolean.
     */
    public static function normalizeComparisonValue(
        ?AbstractPlatform $platform,
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
     * Returns normalized search value should (lowercased for postgresql).
     *
     * On PostgreSQL (+ ORM) we lowercase the value because we use LOWER(column).
     */
    public static function normalizeSearchValue(
        ?AbstractPlatform $platform,
        string $searchValue,
    ): string {
        return self::isPostgreSQL($platform) ? mb_strtolower($searchValue) : $searchValue;
    }

    /**
     * Returns ID handling for raw INSERT when Doctrine cannot auto-generate the ID.
     *
     * MySQL:      no ID column in INSERT (auto_increment)
     * PostgreSQL: id = NEXTVAL('table_id_seq')
     *
     * @return array<string, string>
     */
    public static function getInsertIdValues(
        ?AbstractPlatform $platform,
        string $tableName,
        string $idColumn = 'id',
    ): array {
        if (self::isPostgreSQL($platform)) {
            $sequence = $tableName.'_'.$idColumn.'_seq';

            return [$idColumn => "NEXTVAL('{$sequence}')"];
        }

        return [];
    }

    /**
     * Returns platform-specific ID column and SELECT part for INSERT when using direct SQL.
     *
     * PostgreSQL needs explicit nextval() because Doctrine does not auto-handle sequences in raw SQL.
     * MySQL uses auto_increment (no id column in INSERT).
     *
     * @return array<string, string>
     */
    public static function getInsertIdHandling(
        ?AbstractPlatform $platform,
        string $tableName,
        ?ClassMetadata $metadata = null,
        string $idColumn = 'id',
    ): array {
        if (self::isPostgreSQL($platform)) {
            // Get sequence name from metadata (or fallback to Doctrine default)
            $sequenceName = $metadata ? $metadata->getSequenceName($platform) : $tableName.'_'.$idColumn.'_seq';

            return [
                'idColumn' => $idColumn,
                'idSelect' => "NEXTVAL('{$sequenceName}') AS {$idColumn}, ",
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
    public static function getShareLockClause(?AbstractPlatform $platform): string
    {
        return self::isPostgreSQL($platform) ? 'FOR SHARE' : 'LOCK IN SHARE MODE';
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
     * Returns "ADD" or "ADD COLUMN" depending on platform.
     *
     * MySQL:      ADD column_definition
     * PostgreSQL: ADD COLUMN column_definition
     */
    public static function getAddColumnKeyword(?AbstractPlatform $platform): string
    {
        return self::isPostgreSQL($platform) ? 'ADD COLUMN' : 'ADD';
    }

    /**
     * Returns full column definition for a GENERATED column.
     *
     * Handles differences in GENERATED ALWAYS AS syntax, STORED/VIRTUAL, and comments.
     */
    public static function getGeneratedColumnDefinition(
        ?AbstractPlatform $platform,
        string $columnType,
        string $expression,
        bool $stored = true,
    ): string {
        if (self::isPostgreSQL($platform)) {
            $as      = 'GENERATED ALWAYS AS';
            $stored  = $stored ? ' STORED' : ' VIRTUAL';  // PG 12-17 requires STORED; 18+ supports VIRTUAL
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
    public static function allowsTextInIndex(?AbstractPlatform $platform): bool
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
        // Use this till we apply doctrine-dbal-pgsql-platform-indexes.patch
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
                $columns    = array_map(fn ($part): string => trim($part, '"'), $columns);
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
     * Returns column metadata from information_schema.
     *
     * Returns array of columns (usually one row) matching the given table + column name.
     * Works on both MySQL/MariaDB and PostgreSQL.
     *
     * @return array<array<string, mixed>>
     */
    public static function getColumnMetadata(
        Connection $connection,
        string $fullTableName,
        string $columnName,
        string $tableSchema = 'public',
    ): array {
        $platform = $connection->getDatabasePlatform();

        $params = [
            'db'     => $connection->getDatabase(),
            'table'  => $fullTableName,
            'column' => $columnName,
        ];

        if (self::isPostgreSQL($platform)) {
            $sql = 'SELECT * FROM information_schema.columns
                    WHERE table_catalog = :db
                      AND table_schema = :schema
                      AND table_name   = :table
                      AND column_name  = :column';

            $params['schema'] = $tableSchema;
        } else {
            // MySQL / MariaDB
            $sql = 'SELECT * FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = :db
                      AND TABLE_NAME   = :table
                      AND COLUMN_NAME  = :column';
        }

        return $connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Returns SQL to DROP an index.
     *
     * PostgreSQL: DROP INDEX ...
     * MySQL:      ALTER TABLE ... DROP INDEX ...
     */
    public static function getDropIndexSql(
        ?AbstractPlatform $platform,
        string $tableName,
        string $indexName,
        bool $withAlter = false,
        bool $ifExists = false,
    ): string {
        $if = $ifExists ? 'IF EXISTS ' : '';
        if (self::isPostgreSQL($platform)) {
            return sprintf('DROP INDEX %s%s', $if, $indexName);
        }

        // MySQL / MariaDB
        if (!$withAlter) {
            return sprintf('DROP INDEX %s%s ON %s', $if, $indexName, $tableName);
        }

        return sprintf(
            'ALTER TABLE %s DROP INDEX %s',
            $tableName,
            $indexName
        );
    }

    /**
     * Platform-safe get create a (unique) index sql.
     *
     * PostgreSQL: CREATE UNIQUE INDEX IF NOT EXISTS ...
     * MySQL:      ALTER TABLE ... ADD UNIQUE INDEX ...
     *
     * @param array<string> $columns
     */
    public static function getCreateIndexSql(
        ?AbstractPlatform $platform,
        string $tableName,
        string $indexName,
        array $columns,
        bool $unique = false,
        bool $withAlter = false,
        bool $ifNotExists = false,
    ): string {
        $columnList    = implode(', ', $columns);
        $uniqueKeyword = $unique ? 'UNIQUE ' : '';

        // we can use alter only on non postgresql
        if (!self::isPostgreSQL($platform) && $withAlter) {
            return sprintf(
                'ALTER TABLE %s ADD %sINDEX %s (%s)',
                $tableName,
                $uniqueKeyword,
                $indexName,
                $columnList
            );
        }

        return sprintf(
            'CREATE %sINDEX '.($ifNotExists ? 'IF NOT EXISTS ' : '').'%s ON %s (%s)',
            $uniqueKeyword,
            $indexName,
            $tableName,
            $columnList
        );
    }

    /**
     * Returns platform-specific DISTINCT expression for COUNT(DISTINCT ...).
     *
     * MySQL:      DISTINCT col1, col2, col3
     * PostgreSQL: DISTINCT (col1, col2, col3)   — required for multi-column DISTINCT
     */
    public static function getDistinctMultiColumnExpression(
        ?AbstractPlatform $platform,
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
    public static function quoteIdentifier(?AbstractPlatform $platform, string $identifier): string
    {
        $quoteChar = self::isPostgreSQL($platform) ? '"' : '`';

        return $quoteChar.$identifier.$quoteChar;
    }

    /**
     * Returns fully quoted "tableAlias"."columnName" or `tableAlias`.`columnName`.
     *
     * Expects input in format "table_alias.column_name".
     */
    public static function quoteColumn(?AbstractPlatform $platform, string $fullColumnName, bool $isIdentifier = false): string
    {
        if (!str_contains($fullColumnName, '.') || $isIdentifier) {
            return self::quoteIdentifier($platform, $fullColumnName);
        }

        [$tableAlias, $columnName] = explode('.', $fullColumnName, 2);

        return implode('.', [self::quoteIdentifier($platform, $tableAlias), self::quoteIdentifier($platform, $columnName)]);
    }

    /**
     * Removes platform-specific quoting from a column identifier (for normalization/comparison).
     */
    public static function unquoteIdentifier(?AbstractPlatform $platform, string $fullColumnName): string
    {
        $quoteChar = self::isPostgreSQL($platform) ? '"' : '`';

        return preg_match('/^['.$quoteChar.'a-zA-Z0-9_\.\$]+$/', $fullColumnName)
            ? str_replace($quoteChar, '', $fullColumnName)
            : $fullColumnName;
    }

    /**
     * Returns aggregator expression with platform-specific precision handling.
     *
     * MySQL:      AVG(column)
     * PostgreSQL: AVG(column)::numeric(10, 4)   — ensures consistent decimal precision
     */
    public static function getAggregatorExpression(
        ?AbstractPlatform $platform,
        string $aggregator,
        string $expression,
    ): string {
        $sql = sprintf('%s(%s)', $aggregator, $expression);
        if (self::isPostgreSQL($platform) && 'AVG' === $aggregator) {
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
    public static function allowsIndexHint(?AbstractPlatform $platform): bool
    {
        return !self::isPostgreSQL($platform);
    }

    public static function syncSerialSequence(Connection $connection, string $table, string $field = 'id'): bool
    {
        if (self::isPostgreSQL($connection->getDatabasePlatform())) {
            $sequence = DatabasePlatform::getSerialSequence(
                $connection,
                $table,
                $field
            );

            if ($sequence) {
                $maxId = $connection->fetchOne("SELECT MAX($field) FROM $table");
                $next  = $maxId ? (int) $maxId + 1 : 1;

                return !$connection->executeStatement('SELECT setval(?, ?, false)', [$sequence, $next]);
            }

            return false;
        }

        return true;
    }

    /**
     * Returns the name of the sequence for a given table and field (PostgreSQL only).
     *
     * First attempts to use the standard `pg_get_serial_sequence()` function.
     * Falls back to Doctrine's default naming convention (`{table}_{field}_seq`)
     * if the sequence is not registered or visible to `pg_get_serial_sequence()`.
     *
     * This is required because PostgreSQL tables created via Doctrine migrations
     * often use GENERATED ... AS IDENTITY without a visible named sequence.
     *
     * @param string $fullTable Full table name (with prefix if applicable)
     * @param string $field     Column name (defaults to 'id')
     *
     * @return string|null Sequence name or empty string if none found
     */
    public static function getSerialSequence(Connection $connection, string $fullTable, string $field = 'id'): ?string
    {
        // Step 1: Try standard pg_get_serial_sequence (may return NULL)
        $sequence    = $connection->fetchOne("SELECT pg_get_serial_sequence('$fullTable', '$field')");

        // Step 2: Fallback - set common sequence name as doctrine do
        if (!$sequence) {
            // Doctrine schema tool/migrations created the table with GENERATED ... AS IDENTITY
            // without linking a named sequence in a way visible to pg_get_serial_sequence()
            // Test DB uses a different config that doesn't register the sequence properly
            $doctrineSequence = $fullTable.'_'.$field.'_seq';
            if ($connection->fetchOne(
                "SELECT 1 FROM pg_class WHERE relname = ? AND relkind = 'S'",
                [$doctrineSequence])) {
                $sequence = $doctrineSequence;
            }
        }

        return $sequence;
    }
}
