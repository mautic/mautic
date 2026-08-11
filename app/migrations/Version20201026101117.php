<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201026101117 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'emails');

        if ('utf8mb4' === $table->getColumn('subject')->getPlatformOption('charset')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        // Note: all these columns are type of LONGTEXT.
        $tables = [
            'emails'       => ['subject', 'custom_html', 'plain_text', 'name'],
            'email_copies' => ['subject', 'body', 'body_text'],
        ];

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                $this->addSql("ALTER TABLE {$this->prefix}{$table} CHANGE {$column} {$column} LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }
}
