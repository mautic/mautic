<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_1 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'sync_object_mapping';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->table))->hasColumn('integration_reference_id');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("
            ALTER TABLE `{$this->concatPrefix($this->table)}`
            DROP INDEX `{$this->concatPrefix('integration_object')}`
        ");

        $this->addSql("
            ALTER TABLE `{$this->concatPrefix($this->table)}`
            ADD `integration_reference_id` varchar(191) NULL AFTER `internal_object_name`
        ");

        $this->addSql("
            CREATE INDEX {$this->concatPrefix('integration_object')}
            ON {$this->concatPrefix($this->table)}(integration, integration_object_name, integration_object_id, integration_reference_id);
        ");

        $this->addSql("
            CREATE INDEX {$this->concatPrefix('integration_reference')}
            ON {$this->concatPrefix($this->table)}(integration, integration_object_name, integration_reference_id, integration_object_id);
        ");
    }
}
