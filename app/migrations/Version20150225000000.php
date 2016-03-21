<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Schema update for Version 1.0.0-beta4 to 1.0.0-rc1
 *
 * Class Version20150225000000
 *
 * @package Mautic\Migrations
 */
class Version20150225000000 extends AbstractMauticMigration
{
    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $leadFieldsTable = $schema->getTable($this->prefix . 'lead_fields');
        if ($leadFieldsTable->hasColumn('is_publicly_updatable')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    public function mysqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'addons CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'categories CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails CHANGE content content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'forms CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages CHANGE content content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'points CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_triggers CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'reports CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'roles CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'chat_channels CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'notifications CHANGE message message LONGTEXT NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_fields ADD is_publicly_updatable TINYINT(1) NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails DROP FOREIGN KEY ' . $this->findPropertyName('emails', 'fk', '91861123'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD CONSTRAINT ' . $this->generatePropertyName('emails', 'fk', array('variant_parent_id')) . ' FOREIGN KEY (variant_parent_id) REFERENCES ' . $this->prefix . 'emails (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages DROP FOREIGN KEY ' . $this->findPropertyName('pages', 'fk', '9091A2FB'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ADD CONSTRAINT ' . $this->generatePropertyName('pages', 'fk', array('translation_parent_id')) . ' FOREIGN KEY (translation_parent_id) REFERENCES ' . $this->prefix . 'pages (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_submissions DROP FOREIGN KEY ' . $this->findPropertyName('form_submissions', 'fk', '4C4663E4'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_submissions ADD CONSTRAINT ' . $this->generatePropertyName('form_submissions', 'fk', array('page_id')) . ' FOREIGN KEY (page_id) REFERENCES ' . $this->prefix . 'pages (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'asset_downloads DROP FOREIGN KEY ' . $this->findPropertyName('asset_downloads', 'fk', '6155458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'asset_downloads ADD CONSTRAINT ' . $this->generatePropertyName('asset_downloads', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE SET NULL');
    }

    public function postgresqlUp(Schema $schema)
    {
        $this->addSql('ALTER TABLE ' . $this->prefix . 'addons ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'assets ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaigns ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'campaign_events ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'categories ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ALTER content DROP NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_actions ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'forms ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ALTER content DROP NOT NULL');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'points ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_triggers ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'point_trigger_events ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'reports ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'roles ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'notifications ALTER message TYPE TEXT');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_fields ADD is_publicly_updatable BOOLEAN');
        $this->addSql('UPDATE ' . $this->prefix . 'lead_fields SET is_publicly_updatable = FALSE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_fields ALTER COLUMN is_publicly_updatable SET NOT NULL');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails DROP CONSTRAINT ' . $this->findPropertyName('emails', 'fk', '91861123'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'emails ADD CONSTRAINT ' . $this->generatePropertyName('emails', 'fk', array('variant_parent_id')) . ' FOREIGN KEY (variant_parent_id) REFERENCES ' . $this->prefix . 'emails (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages DROP CONSTRAINT '  . $this->findPropertyName('pages', 'fk', '9091A2FB'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'pages ADD CONSTRAINT ' . $this->generatePropertyName('pages', 'fk', array('translation_parent_id')) . ' FOREIGN KEY (translation_parent_id) REFERENCES ' . $this->prefix . 'pages (id)');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_submissions DROP CONSTRAINT ' . $this->findPropertyName('form_submissions', 'fk', '4C4663E4'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'form_submissions ADD CONSTRAINT ' . $this->generatePropertyName('form_submissions', 'fk', array('page_id')) . ' FOREIGN KEY (page_id) REFERENCES ' . $this->prefix . 'pages (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE ' . $this->prefix . 'asset_downloads DROP CONSTRAINT '  . $this->findPropertyName('asset_downloads', 'fk', '6155458D'));
        $this->addSql('ALTER TABLE ' . $this->prefix . 'asset_downloads ADD CONSTRAINT ' . $this->generatePropertyName('asset_downloads', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function mssqlUp(Schema $schema)
    {
        //was not installable on beta4 due to "﻿may cause cycles or multiple cascade paths" errors with email and page variants
    }
}
