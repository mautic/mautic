<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201207114926 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE {$this->prefix}lead_fields SET is_unique_identifer = 0 WHERE object = 'company';");

        $this->addSql("UPDATE {$this->prefix}lead_fields SET is_unique_identifer = 1 WHERE object = 'company' and alias in ('companyname');");
    }
}
