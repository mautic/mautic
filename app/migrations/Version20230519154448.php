<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230519154448 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => !$schema->hasTable("{$this->prefix}plugin_crm_pipedrive_owners"),
            "Table {$this->prefix}plugin_crm_pipedrive_owners was already removed"
        );
    }

    public function up(Schema $schema): void
    {
        $schema->dropTable("{$this->prefix}plugin_crm_pipedrive_owners");
    }
}
