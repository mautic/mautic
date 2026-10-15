<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\DriverManager;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Test\EnvLoader;
use PHPUnit\Framework\TestCase;

final class TimezoneConversionCompatibilityTest extends TestCase
{
    public function testDstBoundariesMatchMysqlConversion(): void
    {
        EnvLoader::load();
        $connection = DriverManager::getConnection([
            'driver' => $_ENV['DB_DRIVER'] ?? getenv('DB_DRIVER') ?: 'pdo_mysql',
            'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST'),
            'port' => $_ENV['DB_PORT'] ?? getenv('DB_PORT'),
            'dbname' => $_ENV['DB_NAME'] ?? getenv('DB_NAME'),
            'user' => $_ENV['DB_USER'] ?? getenv('DB_USER'),
            'password' => $_ENV['DB_PASSWD'] ?? getenv('DB_PASSWD'),
        ]);
        $connection->beginTransaction();

        try {
            $postgres = DatabasePlatform::isPostgreSQL($connection->getDatabasePlatform());
            if ($postgres) {
                $schema = 'mautic_timezone_'.bin2hex(random_bytes(6));
                $connection->executeStatement('CREATE SCHEMA '.$schema);
                $connection->executeStatement('SET LOCAL search_path TO '.$schema.', pg_catalog');
                $connection->executeStatement("SET LOCAL statement_timeout = '5s'");
                foreach (PostgreSqlCompatibilityFunctions::getSql() as $sql) {
                    if (str_starts_with($sql, 'CREATE OR REPLACE FUNCTION convert_tz(')) {
                        $connection->executeStatement($sql);
                    }
                }
            }

            $query = $postgres
                ? "SELECT (convert_tz(CAST(? AS timestamp) AT TIME ZONE 'UTC', ?, ?) AT TIME ZONE 'UTC')::text"
                : 'SELECT CONVERT_TZ(?, ?, ?)';
            foreach (['+00:00', '+05:45'] as $sessionTimezone) {
                $connection->executeStatement($postgres
                    ? 'SET LOCAL TIME ZONE '.$connection->quote($sessionTimezone)
                    : 'SET time_zone = '.$connection->quote($sessionTimezone));
                foreach ($this->cases() as $label => [$date, $from, $to, $expected]) {
                    $this->assertSame($expected, $connection->fetchOne($query, [$date, $from, $to]), $label.' in session '.$sessionTimezone);
                }
            }
        } finally {
            $connection->rollBack();
            $connection->close();
        }
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    private function cases(): array
    {
        return [
            'ny gap start' => ['2026-03-08 02:00:00', 'America/New_York', 'UTC', '2026-03-08 07:00:00'],
            'ny gap start microseconds' => ['2026-03-08 02:00:00.123456', 'America/New_York', 'UTC', '2026-03-08 07:00:00.123456'],
            'ny gap middle' => ['2026-03-08 02:30:00', 'America/New_York', 'UTC', '2026-03-08 07:00:00'],
            'ny gap microseconds' => ['2026-03-08 02:30:00.123456', 'America/New_York', 'UTC', '2026-03-08 07:00:00.123456'],
            'ny gap end' => ['2026-03-08 02:59:59', 'America/New_York', 'UTC', '2026-03-08 07:00:00'],
            'ny after gap' => ['2026-03-08 03:00:00', 'America/New_York', 'UTC', '2026-03-08 07:00:00'],
            'ny fold start' => ['2026-11-01 01:00:00', 'America/New_York', 'UTC', '2026-11-01 05:00:00'],
            'ny fold middle' => ['2026-11-01 01:30:00', 'America/New_York', 'UTC', '2026-11-01 05:30:00'],
            'ny fold microseconds' => ['2026-11-01 01:30:00.123456', 'America/New_York', 'UTC', '2026-11-01 05:30:00.123456'],
            'ny fold end' => ['2026-11-01 01:59:59', 'America/New_York', 'UTC', '2026-11-01 05:59:59'],
            'ny after fold' => ['2026-11-01 02:00:00', 'America/New_York', 'UTC', '2026-11-01 07:00:00'],
            'utc before gap' => ['2026-03-08 06:59:59', 'UTC', 'America/New_York', '2026-03-08 01:59:59'],
            'utc after gap' => ['2026-03-08 07:00:00', 'UTC', 'America/New_York', '2026-03-08 03:00:00'],
            'utc first fold' => ['2026-11-01 05:30:00', 'UTC', 'America/New_York', '2026-11-01 01:30:00'],
            'utc second fold' => ['2026-11-01 06:30:00', 'UTC', 'America/New_York', '2026-11-01 01:30:00'],
            'berlin gap' => ['2026-03-29 02:30:00', 'Europe/Berlin', 'UTC', '2026-03-29 01:00:00'],
            'berlin fold' => ['2026-10-25 02:30:00', 'Europe/Berlin', 'UTC', '2026-10-25 00:30:00'],
            'lord howe gap' => ['2026-10-04 02:15:00', 'Australia/Lord_Howe', 'UTC', '2026-10-03 15:30:00'],
            'lord howe fold' => ['2026-04-05 01:45:00', 'Australia/Lord_Howe', 'UTC', '2026-04-04 14:45:00'],
            'samoa missing day' => ['2011-12-30 12:00:00', 'Pacific/Apia', 'UTC', '2011-12-30 10:00:00'],
            'offset to named' => ['2026-03-08 08:30:00', '+01:30', 'America/New_York', '2026-03-08 03:00:00'],
            'named to offset gap' => ['2026-03-08 02:30:00', 'America/New_York', '+03:30', '2026-03-08 10:30:00'],
            'named to named fold' => ['2026-11-01 01:30:00', 'America/New_York', 'Europe/Berlin', '2026-11-01 06:30:00'],
        ];
    }
}
