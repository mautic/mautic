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
 * This migration is to change the charset of table emails_draft to utf8mb4.
 */
final class Version20201104000000 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    protected static $tableName = 'emails_draft';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if ('utf8mb4' === $table->getColumn('html')->getPlatformOption('charset')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE
                    %s
                    CONVERT TO CHARACTER SET utf8mb4
                    COLLATE utf8mb4_unicode_ci;',
                $this->getPrefixedTableName()
            )
        );
    }
}
