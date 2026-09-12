<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

/**
 * Words that cannot be used as an unquoted identifier in MySQL or MariaDB.
 *
 * Mautic generates column names from user supplied aliases and writes them into SQL
 * unquoted, so it has to know which ones need to be moved out of the way. DBAL 4
 * deprecated its keyword lists without a replacement, so the list lives here. It is the
 * union of the reserved words of the newest MySQL and MariaDB releases DBAL knows about,
 * which keeps an alias usable on either engine.
 */
final class ReservedWords
{
    /**
     * @var array<string, true>|null
     */
    private static ?array $lookup = null;

    /**
     * @var list<string>
     */
    private const array WORDS = [
        'ACCESSIBLE', 'ADD', 'ADMIN', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'ARRAY', 'AS', 'ASC', 'ASENSITIVE', 'AUTO',
        'BEFORE', 'BERNOULLI', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE',
        'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE',
        'CROSS', 'CUBE', 'CUME_DIST', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR',
        'DATABASE', 'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE',
        'DEFAULT', 'DELAYED', 'DELETE', 'DENSE_RANK', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW',
        'DIV', 'DOUBLE', 'DROP', 'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'EMPTY', 'ENCLOSED', 'ESCAPED', 'EXCEPT', 'EXISTS',
        'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FIRST_VALUE', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR', 'FORCE', 'FOREIGN',
        'FROM', 'FULLTEXT', 'FUNCTION', 'GENERAL', 'GENERATED', 'GET', 'GRANT', 'GROUP', 'GROUPING', 'GROUPS', 'GTIDS',
        'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE',
        'IGNORE_SERVER_IDS', 'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2',
        'INT3', 'INT4', 'INT8', 'INTEGER', 'INTERSECT', 'INTERVAL', 'INTO', 'IO_AFTER_GTIDS', 'IO_BEFORE_GTIDS', 'IS',
        'ITERATE', 'JOIN', 'JSON_TABLE', 'KEY', 'KEYS', 'KILL', 'LAG', 'LAST_VALUE', 'LATERAL', 'LEAD', 'LEADING',
        'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LOG',
        'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY', 'MANUAL', 'MASTER_BIND', 'MASTER_HEARTBEAT_PERIOD',
        'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH', 'MAXVALUE', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MEMBER',
        'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG',
        'NTH_VALUE', 'NTILE', 'NULL', 'NUMERIC', 'OF', 'OFFSET', 'ON', 'OPTIMIZE', 'OPTIMIZER_COSTS', 'OPTION',
        'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'OVER', 'PARALLEL', 'PARSE_TREE', 'PARTITION',
        'PERCENT_RANK', 'PERSIST', 'PERSIST_ONLY', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'QUALIFY', 'RANGE',
        'RANK', 'READ', 'READS', 'READ_WRITE', 'REAL', 'RECURSIVE', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME',
        'REPEAT', 'REPLACE', 'REQUIRE', 'RESIGNAL', 'RESTRICT', 'RETURN', 'RETURNING', 'REVOKE', 'RIGHT', 'RLIKE',
        'ROW', 'ROWS', 'ROW_NUMBER', 'S3', 'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE',
        'SEPARATOR', 'SET', 'SHOW', 'SIGNAL', 'SLOW', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION',
        'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING',
        'STORED', 'STRAIGHT_JOIN', 'SYSTEM', 'TABLE', 'TABLESAMPLE', 'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT',
        'TINYTEXT', 'TO', 'TO_DATE', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED',
        'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR',
        'VARCHARACTER', 'VARYING', 'VECTOR', 'VIRTUAL', 'WHEN', 'WHERE', 'WHILE', 'WINDOW', 'WITH', 'WRITE', 'XOR',
        'YEAR_MONTH', 'ZEROFILL',
    ];

    public static function isReserved(string $word): bool
    {
        self::$lookup ??= array_fill_keys(self::WORDS, true);

        return isset(self::$lookup[strtoupper($word)]);
    }
}
