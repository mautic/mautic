<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20221014081742 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());

        if ($table->hasColumn('payload_compressed')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());
        $table->addColumn('payload_compressed', Types::BLOB, ['notnull' => false, 'length' => MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB]);
        $table->modifyColumn('payload', ['type' => Type::getType(Types::TEXT), 'notnull' => false]);
    }

    private function getTableName(): string
    {
        return $this->prefix.'webhook_queue';
    }
}
