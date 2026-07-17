<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20260330120000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE {$this->getPrefixedTableName('lead_notes')} SET date_time = COALESCE(date_added, date_modified, NOW()) WHERE date_time IS NULL"
        );
    }
}
