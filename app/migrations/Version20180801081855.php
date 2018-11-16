<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180801081855 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable(MAUTIC_TABLE_PREFIX.'lead_lists')->hasColumn('is_preference_center')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_lists ADD is_preference_center TINYINT(1) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $qb     = $this->connection->createQueryBuilder();
        $qb->select('ll.id,ll.is_global')
            ->from($this->prefix.'lead_lists', 'll');
        $results = $qb->execute()->fetchAll();

        foreach ($results as $result) {
            $qb->update(MAUTIC_TABLE_PREFIX.'lead_lists')
                ->set('is_preference_center', $qb->expr()->literal($result['is_global']))
                ->where(
                    $qb->expr()->eq('id', $result['id'])
                )
                ->execute();
        }
    }
}
