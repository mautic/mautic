<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Mautic\IntegrationsBundle\Migration\Engine;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for the "There is no active transaction" masking bug.
 *
 * When a plugin migration runs DDL, PHP 8+'s pdo_mysql driver implicitly
 * commits/closes the transaction. If the migration then throws a
 * \Doctrine\DBAL\Exception, an unguarded rollback() call throws its own
 * "There is no active transaction" error, which replaces the real exception
 * and aborts `mautic:plugins:reload` before the plugin's version is written.
 *
 * These tests drive Engine::up() through a real (throwing) fixture migration
 * and control the *native* PDO connection's transaction state directly, the
 * same signal Engine::up() itself inspects, to prove:
 *   1) when the transaction is already closed, rollback() is skipped and the
 *      original DBAL exception propagates unmasked, and
 *   2) when the transaction is still open, rollback() still runs as before
 *      (the fix is additive, not a behavior change on the normal path).
 */
final class EngineTest extends TestCase
{
    private const BUNDLE_NAME = 'EngineTestBundle';

    private string $pluginPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Engine::up() discovers migrations by scanning a real filesystem
        // directory ("<pluginPath>/Migrations/"), so each test points it at
        // a throw-away fixture migration class that always queues one SQL
        // statement and fails when that statement is executed.
        $this->pluginPath = sys_get_temp_dir().'/mautic-engine-test-'.uniqid('', true);
        mkdir($this->pluginPath.'/Migrations', 0777, true);
    }

    protected function tearDown(): void
    {
        $migrationsDir = $this->pluginPath.'/Migrations';
        foreach (glob($migrationsDir.'/*.php') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($migrationsDir);
        @rmdir($this->pluginPath);

        parent::tearDown();
    }

    public function testRollbackIsSkippedAndOriginalExceptionPropagatesWhenTransactionAlreadyClosed(): void
    {
        // Simulates MySQL's implicit DDL commit: by the time the migration
        // throws, there is no active transaction left to roll back.
        $nativeConnection = new PDO('sqlite::memory:');
        self::assertFalse($nativeConnection->inTransaction());

        $entityManager = $this->buildEntityManager($nativeConnection);
        $entityManager->expects(self::once())->method('beginTransaction');
        $entityManager->expects(self::never())->method('commit');
        $entityManager->expects(self::never())->method('rollback');

        $engine = new Engine($entityManager, 'plugin_', $this->pluginPath, self::BUNDLE_NAME);

        try {
            $engine->up();
            self::fail('Expected the original DBAL exception to propagate.');
        } catch (DBALException $e) {
            self::assertSame('simulated DDL failure', $e->getMessage());
        }
    }

    public function testRollbackStillRunsWhenTransactionIsStillActive(): void
    {
        // Normal (non-DDL) failure path: the transaction is still open when
        // the exception is thrown, so rollback() must still be called, same
        // as before this fix.
        $nativeConnection = new PDO('sqlite::memory:');
        $nativeConnection->beginTransaction();
        self::assertTrue($nativeConnection->inTransaction());

        $entityManager = $this->buildEntityManager($nativeConnection);
        $entityManager->expects(self::once())->method('beginTransaction');
        $entityManager->expects(self::never())->method('commit');
        $entityManager->expects(self::once())->method('rollback');

        $engine = new Engine($entityManager, 'plugin_', $this->pluginPath, self::BUNDLE_NAME);

        try {
            $engine->up();
            self::fail('Expected the original DBAL exception to propagate.');
        } catch (DBALException $e) {
            self::assertSame('simulated DDL failure', $e->getMessage());
        }
    }

    /**
     * @return MockObject&EntityManager
     */
    private function buildEntityManager(PDO $nativeConnection): MockObject
    {
        $this->writeFixtureMigration();

        $schema = $this->createMock(Schema::class);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('introspectSchema')->willReturn($schema);

        $statement = $this->createMock(Statement::class);
        $statement->method('executeStatement')->willThrowException(new DBALException('simulated DDL failure'));

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('prepare')->willReturn($statement);
        $connection->method('getNativeConnection')->willReturn($nativeConnection);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getConnection')->willReturn($connection);

        return $entityManager;
    }

    /**
     * Writes a throw-away migration class with a name unique to this test
     * run, so `require_once` in Engine::getMigrationClasses() never sees the
     * same fully-qualified class name declared twice in one PHP process.
     */
    private function writeFixtureMigration(): void
    {
        $className = 'ThrowingMigration'.str_replace('.', '', uniqid('', true));

        $source = <<<PHP
            <?php

            declare(strict_types=1);

            namespace MauticPlugin\EngineTestBundle\Migrations;

            use Doctrine\DBAL\Schema\Schema;
            use Mautic\IntegrationsBundle\Migration\AbstractMigration;

            class {$className} extends AbstractMigration
            {
                protected function isApplicable(Schema \$schema): bool
                {
                    return true;
                }

                protected function up(): void
                {
                    \$this->addSql('ALTER TABLE plugin_foo ADD COLUMN bar INT');
                }
            }
            PHP;

        file_put_contents($this->pluginPath.'/Migrations/'.$className.'.php', $source);
    }
}
