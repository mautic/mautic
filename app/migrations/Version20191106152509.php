<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20191106152509 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE {$this->prefix}lead_fields
	        SET `char_length_limit` = NULL 
	        WHERE `type` NOT IN ('text', 'select', 'multiselect', 'phone', 'url', 'email')
	        AND `char_length_limit` IS NOT NULL;
        ");
    }
}
