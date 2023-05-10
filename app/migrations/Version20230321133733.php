<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230321133733 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE asset_downloads ADD utm_campaign VARCHAR(191) DEFAULT NULL, ADD utm_content VARCHAR(191) DEFAULT NULL, ADD utm_medium VARCHAR(191) DEFAULT NULL, ADD utm_source VARCHAR(191) DEFAULT NULL, ADD utm_term VARCHAR(191) DEFAULT
 NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE asset_downloads DROP COLUMN utm_campaign, utm_content, utm_medium, utm_source, utm_term;');
    }
}
