<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * This migration is a fix for issue https://backlog.acquia.com/browse/MAUT-4708.
 */
final class Version20201103000000 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    protected static $tableName = 'emails_beefree_metadata';

    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        if (!$table->getColumn('html')->getNotnull()) {
            throw new SkipMigration('Migration already executed. Skipping.');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE %s CHANGE css css LONGTEXT DEFAULT NULL, CHANGE html html LONGTEXT DEFAULT NULL;',
                $this->getPrefixedTableName()
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'DELETE from %s WHERE html IS NULL;',
                $this->getPrefixedTableName()
            )
        );

        $this->addSql(
            sprintf(
                'ALTER TABLE %s CHANGE css css LONGTEXT NOT NULL, CHANGE html html LONGTEXT NOT NULL;',
                $this->getPrefixedTableName()
            )
        );
    }
}
