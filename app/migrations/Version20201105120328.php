<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201105120328 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'push_notifications');

        if ('utf8mb4' === $table->getColumn('heading')->getPlatformOption('charset')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $pushNotifications = [
            ['name' => 'name',        'type' => 'VARCHAR(191)'],
            ['name' => 'description', 'type' => 'LONGTEXT'],
            ['name' => 'heading',     'type' => 'LONGTEXT'],
            ['name' => 'message',     'type' => 'LONGTEXT'],
            ['name' => 'button',      'type' => 'LONGTEXT'],
        ];

        foreach ($pushNotifications as $column) {
            $this->addSql("ALTER TABLE {$this->prefix}push_notifications 
                CHANGE {$column['name']} {$column['name']} {$column['type']} 
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }
}
