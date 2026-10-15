<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Functional;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\EventListener\DoctrineEventSubscriber;
use Mautic\Migrations\Version20260120130000;
use Mautic\Migrations\Version20261015160000;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;

final class PostgreSqlCompatibilityFunctionsTest extends MauticMysqlTestCase
{
    #[DataProvider('installationPaths')]
    public function testCompatibilityFunctions(string $installationPath): void
    {
        if (!$this->isPostgresqlPlatform()) {
            $this->markTestSkipped('PostgreSQL compatibility functions are only installed on PostgreSQL.');
        }

        $this->connection->executeStatement('DROP FUNCTION IF EXISTS lower(jsonb)');
        $this->connection->executeStatement('DROP FUNCTION IF EXISTS lower(json)');
        // Reproduce the old helpers so the refresh migration must correct an existing installation.
        $this->connection->executeStatement('CREATE OR REPLACE FUNCTION pg_bool_eq_int(boolean, integer) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT $1 = $2::boolean;$$');
        $this->connection->executeStatement('CREATE OR REPLACE FUNCTION pg_int_eq_bool(integer, boolean) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT $1::boolean = $2;$$');
        $this->connection->executeStatement('CREATE OR REPLACE FUNCTION pg_bool_neq_int(boolean, integer) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT $1 <> $2::boolean;$$');
        $this->connection->executeStatement('CREATE OR REPLACE FUNCTION pg_int_neq_bool(integer, boolean) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT $1::boolean <> $2;$$');

        if ('install' === $installationPath) {
            (new DoctrineEventSubscriber())->postgreSqlMySqlCompact($this->connection);
        } else {
            $migration = 'upgrade' === $installationPath
                ? new Version20260120130000($this->connection, new NullLogger())
                : new Version20261015160000($this->connection, new NullLogger());
            $migration->up(new Schema());
            foreach ($migration->getSql() as $query) {
                $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
            }
        }

        $cases = [
            ["mautic_like('CAFÉ' COLLATE mautic_unicode_ci, 'cafe' COLLATE mautic_unicode_ci)", 'true'],
            ["find_in_set('CAFE', 'other,café' COLLATE mautic_unicode_ci)", '2'],
            ["substring_index('fooBARbaz' COLLATE mautic_unicode_ci, 'bar' COLLATE mautic_unicode_ci, 1)", 'fooBARbaz'],
            ["date_format('2026-02-03 12:45:56+00'::timestamptz, '%Y-%m-%d' COLLATE mautic_unicode_ci)", '2026-02-03'],
            ["mautic_unicode_ci('café') = mautic_unicode_ci('CAFE')", 'true'],
            ["mautic_unicode_ci('ẞ') = mautic_unicode_ci('ß')", 'false'],
            ["mautic_unicode_ci('foo%bar') LIKE mautic_unicode_ci(E'foo\\\\%bar', 1)", 'true'],
            ["regexp_like('WORD', mautic_regexp_pattern(E'\\\\bword\\\\b'), 'ip')", 'true'],
            ['TRUE = 2', 'false'],
            ['2 = TRUE', 'false'],
            ['TRUE <> 2', 'true'],
            ['2 <> TRUE', 'true'],
            ['FALSE = 0', 'true'],
            ['0 = FALSE', 'true'],
            ['FALSE <> 0', 'false'],
            ['0 <> FALSE', 'false'],
            ['NULL::boolean = 1', null],
            ['1 = NULL::boolean', null],
            ['NULL::boolean <> 1', null],
            ['1 <> NULL::boolean', null],
            ['sec_to_time(3661)', '01:01:01'],
            ['sec_to_time(-3661)', '-01:01:01'],
            ['sec_to_time(0)', '00:00:00'],
            ['sec_to_time(360000)', '100:00:00'],
            ['sec_to_time(9223372036854775807::bigint)', '838:59:59'],
            ["sec_to_time('-9223372036854775808'::bigint)", '-838:59:59'],
            ["sec_to_time('-2147483648'::integer)", '-838:59:59'],
            ['sec_to_time(NULL::integer)', null],
            ["unix_timestamp('2026-02-03 12:45:56.9+00'::timestamptz) - unix_timestamp('2026-02-03 12:45:56+00'::timestamptz)", '0'],
            ['unix_timestamp() - floor(EXTRACT(EPOCH FROM statement_timestamp()))::bigint', '0'],
            ["substring_index('www.mautic.org', '.', 2)", 'www.mautic'],
            ["substring_index('www.mautic.org', '.', -2)", 'mautic.org'],
            ["substring_index('www.mautic.org', '.', 0)", ''],
            ["substring_index('www.mautic.org', '.', 10)", 'www.mautic.org'],
            ["substring_index('www.mautic.org', '.', -10)", 'www.mautic.org'],
            ["substring_index('www.mautic.org', '', 1)", ''],
            ["substring_index('', '.', 1)", ''],
            ["substring_index('one::two::three', '::', -2)", 'two::three'],
            ["substring_index(NULL, '.', 1)", null],
            ["lower('[\"UPPER\"]'::jsonb)", '["upper"]'],
            ['lower(NULL::jsonb)', null],
            ["lower('[\"UPPER\"]'::json)", '["upper"]'],
            ['lower(NULL::json)', null],
            ["find_in_set('two', 'one,two,three')", '2'],
            ["find_in_set('two', 'one, two,three')", '0'],
            ["find_in_set(' two', 'one, two,three')", '2'],
            ["find_in_set('', '')", '0'],
            ["find_in_set(NULL, 'one,two')", null],
            ["date_format('2026-02-03 12:45:56+00'::timestamptz, '%c')", '2'],
            ["date_format(convert_tz('2026-02-03 12:45:56+00'::timestamptz, '+00:00', '+03:30'), '%Y-%m-%d %T')", '2026-02-03 16:15:56'],
            ["date_format(convert_tz('2026-02-03 12:45:56+00'::timestamptz, '+03:30', '-02:00'), '%Y-%m-%d %T')", '2026-02-03 07:15:56'],
            ["date_format(convert_tz('2026-02-03 12:45:56+00'::timestamptz, 'UTC', 'America/New_York'), '%Y-%m-%d %T')", '2026-02-03 07:45:56'],
            ["date_format(convert_tz('2026-07-03 12:45:56+00'::timestamptz, 'UTC', 'America/New_York'), '%Y-%m-%d %T')", '2026-07-03 08:45:56'],
            ["date_format(convert_tz('2026-03-08 02:30:00+00'::timestamptz, 'America/New_York', 'UTC'), '%Y-%m-%d %T')", '2026-03-08 07:00:00'],
            ["date_format(convert_tz('2026-11-01 01:30:00+00'::timestamptz, 'America/New_York', 'UTC'), '%Y-%m-%d %T')", '2026-11-01 05:30:00'],
            ["convert_tz('2026-02-03 12:45:56+00'::timestamptz, 'Invalid/Timezone', 'UTC')", null],
        ];

        foreach ($cases as [$expression, $expected]) {
            $this->assertSame($expected, $this->connection->fetchOne('SELECT ('.$expression.')::text'), $expression);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function installationPaths(): iterable
    {
        yield 'fresh install' => ['install'];
        yield 'first upgrade' => ['upgrade'];
        yield 'existing PostgreSQL installation' => ['refresh'];
    }
}
