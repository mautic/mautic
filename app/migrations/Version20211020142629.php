<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20211020142629 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE {$this->getPrefixedTableName('leads')} SET date_modified = date_added WHERE date_modified IS NULL;");
    }
}
