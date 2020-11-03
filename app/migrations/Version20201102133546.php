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
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20201102133546.
 */
final class Version20201102133546 extends AbstractMauticMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $tableName = $this->prefix.'email_assets_xref';
        $indexName = 'IDX_39CFAB07A832C1C9';

        if (!$schema->getTable($tableName)->hasIndex($indexName)) {
            throw new SkipMigration('Schema includes this migration');
        }

        $this->addSql('ALTER TABLE '.$tableName.' DROP INDEX '.$indexName.';');
    }
}
