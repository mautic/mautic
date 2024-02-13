<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230522141144 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable("{$this->prefix}plugin_citrix_events");
        }, sprintf('Table %s already exists', "{$this->prefix}plugin_citrix_events"));
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable("{$this->prefix}plugin_citrix_events")) {
            $schema->dropTable("{$this->prefix}plugin_citrix_events");
        }
    }
}
