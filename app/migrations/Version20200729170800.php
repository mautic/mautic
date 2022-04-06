<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration for changing column_value to a longtext from varchar.
 */
class Version20200729170800 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    private $table = 'sync_object_field_change_report';

    /**
     * {@inheritdoc}
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->prefix.$this->table)->getColumn('column_value')->getType() instanceof TextType) {
            throw new SkipMigration('column_value is already the correct type.');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}{$this->table} MODIFY column_value LONGTEXT NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}{$this->table} MODIFY column_value VARCHAR(255) NOT NULL");
    }
}
