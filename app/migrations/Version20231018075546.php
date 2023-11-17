<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20231018075546 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable(MAUTIC_TABLE_PREFIX.'pages')->hasColumn('tracking_disabled')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(MAUTIC_TABLE_PREFIX.'pages');
        $table->addColumn('tracking_disabled', 'boolean', ['default' => false]);
    }
}
