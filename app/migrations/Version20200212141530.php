<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200212141530 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Removes device_fingerprint column from the lead_devices table';
    }

    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if (!$schema->getTable($this->prefix.'lead_devices')->hasColumn('device_fingerprint')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_devices DROP device_fingerprint");
    }
}
