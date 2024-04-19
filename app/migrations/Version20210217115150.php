<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210217115150 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))->hasColumn('deleted')
            && $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))->hasColumn('deleted')
        ) {
            throw new SkipMigration('Deleted column already added in tables');
        }
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))
            ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);

        $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))
            ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);
    }
}
