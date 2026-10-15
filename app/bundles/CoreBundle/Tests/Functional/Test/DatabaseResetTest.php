<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Test;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class DatabaseResetTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testConfiguredDriverMatchesConnection(): void
    {
        $this->assertSame($this->connection->getParams()['driver'], $this->configParams['db_driver']);
        $this->assertSame($this->connection->getParams()['charset'], $this->configParams['db_charset']);
    }

    public function testDumpCacheSeparatesDatabaseServers(): void
    {
        $connection = $this->em->getConnection();
        $cachePath = new \ReflectionMethod(MauticMysqlTestCase::class, 'getSqlFilePath');
        $paths = [];

        try {
            foreach ([[], ['host' => 'another-host'], ['port' => 6543], ['dbname' => 'another-database'], ['version' => '180000']] as $overrides) {
                $this->connection = $this->createMock(Connection::class);
                $this->connection->method('getDatabasePlatform')->willReturn($connection->getDatabasePlatform());
                $this->connection->method('getParams')->willReturn(array_merge($connection->getParams(), $overrides));
                $this->connection->method('fetchOne')->willReturn($overrides['version'] ?? '160000');
                $path = $cachePath->invoke($this, 'fresh_db');
                $this->assertSame($path, $cachePath->invoke($this, 'fresh_db'));
                $paths[] = $path;
            }

            $this->assertCount(count($paths), array_unique($paths));
        } finally {
            $this->connection = $connection;
        }
    }

    public function testResetRestoresFixturesAndMigrationMetadata(): void
    {
        $resetDatabase = new \ReflectionMethod(MauticMysqlTestCase::class, 'resetDatabase');
        $resetDatabase->invoke($this);
        $migrationTable = $this->connection->quoteIdentifier($_ENV['MAUTIC_MIGRATIONS_TABLE_NAME']);
        $migrationsBefore = $this->connection->fetchAllAssociative('SELECT * FROM '.$migrationTable.' ORDER BY version');
        $this->assertNotEmpty($migrationsBefore);
        $table = $this->em->getClassMetadata(User::class)->getTableName();
        $usersBefore = $this->connection->fetchAllAssociative('SELECT * FROM '.$this->connection->quoteIdentifier($table).' ORDER BY id');
        $this->assertNotEmpty($usersBefore);
        if ($this->isPostgresqlPlatform()) {
            $sequence = DatabasePlatform::getSerialSequence($this->connection, $table);
            $this->assertNotNull($sequence);
            $nextId = $this->connection->fetchOne('SELECT nextval(?)', [$sequence]);
        }

        $this->connection->executeStatement('UPDATE '.$this->connection->quoteIdentifier($table)." SET first_name = 'Changed by reset test'");
        $resetDatabase->invoke($this);

        $this->assertSame($usersBefore, $this->connection->fetchAllAssociative('SELECT * FROM '.$this->connection->quoteIdentifier($table).' ORDER BY id'));
        $this->assertSame($migrationsBefore, $this->connection->fetchAllAssociative('SELECT * FROM '.$migrationTable.' ORDER BY version'));
        if (isset($sequence)) {
            $this->assertSame($nextId, $this->connection->fetchOne('SELECT nextval(?)', [$sequence]));
        }
    }

    public function testPostgresqlResetRemovesDynamicFormResultTables(): void
    {
        if (!$this->isPostgresqlPlatform()) {
            self::markTestSkipped('Tests PostgreSQL schema cleanup during database reset.');
        }

        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'     => 'Reset schema regression',
            'formType' => 'standalone',
            'fields'   => [['label' => 'Email', 'type' => 'email', 'alias' => 'email']],
        ]);
        self::assertResponseIsSuccessful();
        $form = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['form'];
        $prefix = self::getContainer()->getParameter('mautic.db_table_prefix');
        $resultTable = $prefix.'form_results_'.$form['id'].'_'.$form['alias'];
        $this->assertTrue($this->connection->createSchemaManager()->tablesExist([$resultTable]));

        (new \ReflectionMethod(MauticMysqlTestCase::class, 'resetDatabase'))->invoke($this);

        $this->assertFalse($this->connection->createSchemaManager()->tablesExist([$resultTable]));
    }

    public function testPostgresqlRestoreRejectsInvalidSql(): void
    {
        if (!$this->isPostgresqlPlatform()) {
            self::markTestSkipped('Tests psql error handling.');
        }

        $file = tempnam(sys_get_temp_dir(), 'mautic-invalid-sql-');
        $this->assertNotFalse($file);
        file_put_contents($file, 'THIS IS INVALID SQL;');

        try {
            $this->expectException(ProcessFailedException::class);
            (new \ReflectionMethod(MauticMysqlTestCase::class, 'applySqlFromFile'))->invoke($this, $file);
        } finally {
            unlink($file);
        }
    }
}
