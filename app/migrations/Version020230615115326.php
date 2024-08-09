<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * This migration must run first otherwise the pre-up assertions for other migrations will fail on M5.
 */
final class Version020230615115326 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->prefix}message_channels` CHANGE `properties` `properties` JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}emails` CHANGE `headers` `headers` JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}form_fields` CHANGE `conditions` `conditions` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}form_fields` CHANGE `validation` `validation` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}dynamic_content` CHANGE `utm_tags` `utm_tags` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}sms_message_stats` CHANGE `details` `details` JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}sync_object_mapping` CHANGE `internal_storage` `internal_storage` JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}imports` CHANGE `properties` `properties` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}lead_event_log` CHANGE `properties` `properties` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}reports` CHANGE `settings` `settings` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("ALTER TABLE `{$this->prefix}tweet_stats` CHANGE `response_details` `response_details` JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
    }
}
