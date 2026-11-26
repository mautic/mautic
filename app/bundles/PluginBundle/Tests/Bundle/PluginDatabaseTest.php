<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Bundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Bundle\PluginDatabase;
use Mautic\PluginBundle\Entity\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginDatabaseTest extends TestCase
{
    /**
     * Bundle name of the throw-away plugin created for this test.
     */
    private const PROBE_BUNDLE = 'MauticTablePrefixProbeBundle';

    private string $pluginsDir;

    protected function setUp(): void
    {
        $this->pluginsDir = dirname(__DIR__, 5).'/plugins';

        $migrationsDir = $this->pluginsDir.'/'.self::PROBE_BUNDLE.'/Migrations';
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0777, true);
        }

        // The migration queues the SQL it would run instead of touching the database,
        // which keeps this a unit test.
        file_put_contents(
            $migrationsDir.'/Version1.php',
            <<<'PHP'
                <?php

                declare(strict_types=1);

                namespace MauticPlugin\MauticTablePrefixProbeBundle\Migrations;

                use Doctrine\DBAL\Schema\Schema;
                use Mautic\IntegrationsBundle\Migration\AbstractMigration;

                final class Version1 extends AbstractMigration
                {
                    public function shouldExecute(): bool
                    {
                        return true;
                    }

                    protected function isApplicable(Schema $schema): bool
                    {
                        return true;
                    }

                    protected function up(): void
                    {
                        $this->addSql('CREATE TABLE '.$this->concatPrefix('probe').' (id INT)');
                    }
                }
                PHP
        );
    }

    protected function tearDown(): void
    {
        $bundleDir = $this->pluginsDir.'/'.self::PROBE_BUNDLE;
        foreach (['Migrations/Version1.php', 'Migrations'] as $path) {
            if (is_file($bundleDir.'/'.$path)) {
                unlink($bundleDir.'/'.$path);
            } elseif (is_dir($bundleDir.'/'.$path)) {
                rmdir($bundleDir.'/'.$path);
            }
        }
        if (is_dir($bundleDir)) {
            rmdir($bundleDir);
        }
    }

    public function testPluginMigrationsUseTheGivenPrefix(): void
    {
        $this->assertSame(['CREATE TABLE plugin_probe (id INT)'], $this->runPluginUpdate('plugin_'));
    }

    public function testPluginMigrationsFallBackToTheTablePrefixConstant(): void
    {
        $this->assertSame(
            ['CREATE TABLE '.MAUTIC_TABLE_PREFIX.'probe (id INT)'],
            $this->runPluginUpdate(null)
        );
    }

    /**
     * Runs a plugin update for the probe plugin and returns the SQL its migration executed.
     *
     * @return list<string>
     */
    private function runPluginUpdate(?string $configuredPrefix): array
    {
        $executedSql = [];
        $statement   = $this->createMock(Statement::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('prepare')->willReturnCallback(
            static function (string $sql) use (&$executedSql, $statement): Statement {
                $executedSql[] = $sql;

                return $statement;
            }
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $plugin = new Plugin();
        $plugin->setBundle(self::PROBE_BUNDLE);

        (new PluginDatabase($entityManager, $connection, $configuredPrefix))->onPluginUpdate($plugin);

        return $executedSql;
    }
}
