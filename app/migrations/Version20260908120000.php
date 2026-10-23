<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20260908120000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'sms_messages');

        if (!$table->hasColumn('continue_sending')) {
            $this->addSql("ALTER TABLE {$this->prefix}sms_messages ADD `continue_sending` TINYINT(1) DEFAULT 0 NOT NULL");
            $this->addSql("UPDATE {$this->prefix}sms_messages SET `continue_sending` = 1 WHERE publish_up IS NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'sms_messages');

        if ($table->hasColumn('continue_sending')) {
            $this->addSql("ALTER TABLE {$this->prefix}sms_messages DROP continue_sending");
        }
    }
}
