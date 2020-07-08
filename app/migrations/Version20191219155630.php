<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191219155630 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Adds tables for the IntegrationBundle';
    }

    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->hasTable($this->prefix.'sync_object_field_change_report')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "# Dump of table mautic_sync_object_field_change_report
            # ------------------------------------------------------------

            CREATE TABLE `{$this->prefix}sync_object_field_change_report` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `integration` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `object_id` int(11) NOT NULL,
            `object_type` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `modified_at` datetime NOT NULL COMMENT '(DC2Type:datetime)',
            `column_name` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `column_type` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `column_value` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            KEY `{$this->prefix}object_composite_key` (`object_type`,`object_id`,`column_name`),
            KEY `{$this->prefix}integration_object_composite_key` (`integration`,`object_type`,`object_id`,`column_name`),
            KEY `{$this->prefix}integration_object_type_modification_composite_key` (`integration`,`object_type`,`modified_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
        );

        $this->addSql(
            "# Dump of table mautic_sync_object_mapping
            # ------------------------------------------------------------
            
            CREATE TABLE `{$this->prefix}sync_object_mapping` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `date_created` datetime NOT NULL COMMENT '(DC2Type:datetime)',
            `integration` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `internal_object_name` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `integration_reference_id` varchar(191) COLLATE utf8_unicode_ci DEFAULT NULL,
            `internal_object_id` int(11) NOT NULL,
            `integration_object_name` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `integration_object_id` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
            `last_sync_date` datetime NOT NULL COMMENT '(DC2Type:datetime)',
            `internal_storage` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
            `is_deleted` tinyint(1) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `{$this->prefix}internal_object` (`integration`,`internal_object_name`,`internal_object_id`),
            KEY `{$this->prefix}object_match` (`integration`,`internal_object_name`,`integration_object_name`),
            KEY `{$this->prefix}integration_last_sync_date` (`integration`,`last_sync_date`),
            KEY `{$this->prefix}integration_object` (`integration`,`integration_object_name`,`integration_object_id`,`integration_reference_id`),
            KEY `{$this->prefix}integration_reference` (`integration`,`integration_object_name`,`integration_reference_id`,`integration_object_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `{$this->prefix}sync_object_field_change_report`");
        $this->addSql("DROP TABLE `{$this->prefix}sync_object_mapping`");
    }
}
