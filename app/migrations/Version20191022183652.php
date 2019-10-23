<?php

/*
 * @package     Mautic
 * @copyright   2019 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191022183652 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if (!$schema->getTable("{$this->prefix}leads")->hasColumn('googleplus')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        /** @var QueryBuilder $query */
        $query                     = $this->container->get('doctrine')->getConnection()->createQueryBuilder();
        $hasContactsWithGooglePlus = (bool) $query->select('COUNT(googleplus) as count')
            ->from("{$this->prefix}leads")
            ->where($query->expr()->andX(
                $query->expr()->isNotNull('googleplus'),
                $query->expr()->neq('googleplus', $query->expr()->literal(''))
            ))
            ->execute()
            ->fetchColumn();

        if ($hasContactsWithGooglePlus) {
            throw new SkipMigrationException('Running this migration will result in data loss for this instance.');
        }
    }

    public function up(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE `{$this->prefix}leads` DROP COLUMN `googleplus`;
SQL;

        $this->addSql($sql);
    }
}
