<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;

final class Version20261015160000 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Correct PostgreSQL MySQL compatibility function semantics';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(!DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform()), 'Only PostgreSQL needs compatibility functions.');

        foreach (PostgreSqlCompatibilityFunctions::getSql() as $sql) {
            $this->addSql($sql);
        }
    }
}
