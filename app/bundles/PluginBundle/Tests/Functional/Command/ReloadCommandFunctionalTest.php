<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Functional\Command;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Plugin;

final class ReloadCommandFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $prefix;

    protected function setUp(): void
    {
        $this->configParams['messenger_dsn_email'] = 'doctrine://default';

        parent::setUp();

        $this->prefix = (string) self::getContainer()->getParameter('mautic.db_table_prefix');

        $this->testSymfonyCommand('messenger:setup-transports', ['transport' => 'email']);
        $this->uninstallFocusPlugin();
    }

    /**
     * Ensures that a new plugin installs completely when messenger_messages already exists.
     *
     * Case: a queue uses the Doctrine transport, and a plugin with entities has no row in plugins
     * and no tables yet. Symfony's messenger listener adds messenger_messages to the plugin schema.
     * Before the fix, the install failed with "table already exists" and left the plugin tables
     * without foreign keys. PluginBundleBase now skips tables that already exist.
     */
    public function testFirstReloadInstallsPluginWhenMessengerTableExists(): void
    {
        $this->testSymfonyCommand('mautic:plugins:reload');

        $this->assertInstanceOf(Plugin::class, $this->em->getRepository(Plugin::class)->findOneBy(['bundle' => 'MauticFocusBundle']));
        $this->assertFocusSchemaIsComplete();
    }

    private function uninstallFocusPlugin(): void
    {
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['focus_stats', 'focus'] as $table) {
            $this->connection->executeStatement("DROP TABLE IF EXISTS `{$this->prefix}{$table}`");
        }
        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->connection->delete($this->prefix.'plugins', ['bundle' => 'MauticFocusBundle']);
    }

    private function assertFocusSchemaIsComplete(): void
    {
        $this->assertSame(['categories'], $this->getReferencedTables('focus'));
        $this->assertSame(['focus', 'leads'], $this->getReferencedTables('focus_stats'));
    }

    /**
     * @return string[]
     */
    private function getReferencedTables(string $table): array
    {
        $foreignKeys = $this->connection->createSchemaManager()->listTableForeignKeys($this->prefix.$table);
        $tables      = array_map(
            fn (ForeignKeyConstraint $foreignKey): string => substr($foreignKey->getForeignTableName(), strlen($this->prefix)),
            $foreignKeys
        );
        sort($tables);

        return $tables;
    }
}
