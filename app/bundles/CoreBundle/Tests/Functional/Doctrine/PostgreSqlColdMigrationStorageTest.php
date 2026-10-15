<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\VersionProviderInterface;
use Mautic\CoreBundle\EventListener\MigrationCommandSubscriber;
use Mautic\CoreBundle\Test\EnvLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class PostgreSqlColdMigrationStorageTest extends TestCase
{
    public function testMigrationCommandCreatesCollationBeforeDoctrineCreatesItsMetadataTable(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? null)) {
            $this->markTestSkipped('PostgreSQL migration metadata bootstrap.');
        }

        $connection = DriverManager::getConnection([
            'driver'   => 'pdo_pgsql',
            'host'     => $_ENV['DB_HOST'],
            'port'     => $_ENV['DB_PORT'],
            'dbname'   => $_ENV['DB_NAME'],
            'user'     => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWD'],
        ]);
        $schema = 'cold_migrations_'.bin2hex(random_bytes(6));

        try {
            $connection->executeStatement('CREATE SCHEMA '.$connection->quoteIdentifier($schema));
            $connection->executeStatement('SET search_path TO '.$connection->quoteIdentifier($schema));
            $this->assertNull($connection->fetchOne("SELECT to_regcollation('mautic_unicode_ci')"));

            $subscriber = new MigrationCommandSubscriber(
                $this->createStub(VersionProviderInterface::class),
                $this->createStub(GeneratedColumnsProviderInterface::class),
                $connection,
            );
            $command = new Command('doctrine:migrations:status');
            $subscriber->prepareMigrationMetadataStorage(new ConsoleCommandEvent($command, new ArrayInput([]), new NullOutput()));
            $this->assertTrue((bool) $connection->fetchOne("SELECT mautic_like('café', 'CAFE')"));

            $storage = new TableMetadataStorage($connection, new AlphabeticalComparator(), new TableMetadataStorageConfiguration());
            $storage->ensureInitialized();

            $this->assertSame(
                'mautic_unicode_ci',
                $connection->fetchOne(
                    <<<'SQL'
                        SELECT coll.collname
                        FROM pg_attribute attribute
                        JOIN pg_collation coll ON coll.oid = attribute.attcollation
                        WHERE attribute.attrelid = to_regclass('doctrine_migration_versions')
                            AND attribute.attname = 'version'
                        SQL
                )
            );
        } finally {
            $connection->executeStatement('DROP SCHEMA IF EXISTS '.$connection->quoteIdentifier($schema).' CASCADE');
            $connection->close();
        }
    }
}
