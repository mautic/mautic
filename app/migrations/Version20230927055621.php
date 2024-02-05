<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230927055621 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'emails');

        if (!$table->hasColumn('continue_sending')) {
            $this->addSql("ALTER TABLE {$this->prefix}emails ADD `continue_sending` TINYINT(1) DEFAULT NULL");
            $this->addSql("UPDATE {$this->prefix}emails SET `continue_sending`= 1 WHERE publish_up IS NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'emails');

        if ($table->hasColumn('continue_sending')) {
            $this->addSql("ALTER TABLE {$this->prefix}emails DROP continue_sending");
        }
    }
}
