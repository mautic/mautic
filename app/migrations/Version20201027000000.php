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

final class Version20201027000000 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    protected static $tableName = 'emails_beefree_metadata';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if ($table->hasColumn('draft_html')) {
            throw new SkipMigration(sprintf('Table %s already has the migration. Skipping migration', $this->getPrefixedTableName()));
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE %s ADD draft_css LONGTEXT DEFAULT NULL, ADD draft_html LONGTEXT DEFAULT NULL',
                $this->getPrefixedTableName())
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN draft_html, DROP COLUMN draft_css', $this->getPrefixedTableName()));
    }
}
