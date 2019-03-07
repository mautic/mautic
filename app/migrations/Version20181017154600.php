<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Fixes typo on Córdoba spanish region (Cordóba > Córdoba).
 */
class Version20181017154600 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $oldRegionName = 'Cordóba';
        $newRegionName = 'Córdoba';

        // Fix region name for leads.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update("{$this->prefix}leads")
            ->set('state', ':newRegionName')
            ->where(
                $queryBuilder->expr()->eq('state', $queryBuilder->expr()->literal($oldRegionName))
            )
            ->setParameter('newRegionName', $newRegionName, 'string')
            ->execute();

        // Fix region name for companies.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update("{$this->prefix}companies")
            ->set('companystate', ':newRegionName')
            ->where(
                $queryBuilder->expr()->eq('companystate', $queryBuilder->expr()->literal($oldRegionName))
            )
            ->setParameter('newRegionName', $newRegionName, 'string')
            ->execute();

        // Fix region name for page hits.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update("{$this->prefix}page_hits")
            ->set('region', ':newRegionName')
            ->where(
                $queryBuilder->expr()->eq('region', $queryBuilder->expr()->literal($oldRegionName))
            )
            ->setParameter('newRegionName', $newRegionName, 'string')
            ->execute();

        // Fix region name for video hits.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update("{$this->prefix}video_hits")
            ->set('region', ':newRegionName')
            ->where(
                $queryBuilder->expr()->eq('region', $queryBuilder->expr()->literal($oldRegionName))
            )
            ->setParameter('newRegionName', $newRegionName, 'string')
            ->execute();

        // Fix region name for 'filters' field of lead_lists, dynamic_content & reports.
        // As the old string & the new one are of the same length, a general 'REPLACE' is OK even on (DC2Type:array).
        $this->addSql("UPDATE `{$this->prefix}lead_lists` SET filters = REPLACE(filters, '$oldRegionName', '$newRegionName') WHERE filters LIKE '%$oldRegionName%'");
        $this->addSql("UPDATE `{$this->prefix}dynamic_content` SET filters = REPLACE(filters, '$oldRegionName', '$newRegionName') WHERE filters LIKE '%$oldRegionName%'");
        $this->addSql("UPDATE `{$this->prefix}reports` SET filters = REPLACE(filters, '$oldRegionName', '$newRegionName') WHERE filters LIKE '%$oldRegionName%'");
    }
}
