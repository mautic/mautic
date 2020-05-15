<?php

declare(strict_types=1);

/*
 * @package     Mautic
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200507122854 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $fieldsTable = $schema->getTable($this->prefix.'form_fields');

        if ($fieldsTable->hasColumn('parent_id')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE {$this->prefix}form_fields ADD parent_id VARCHAR(255) DEFAULT NULL;"
        );
    }
}
