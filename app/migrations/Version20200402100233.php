<?php

declare(strict_types=1);

/*
 * @copyright   <year> Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200402100233 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'This migration fixes "Serialized array includes null-byte" error when merging some contacts.';
    }

    public function preUp(Schema $schema): void
    {
        $this->getIpDetailsWithNullByteSymbols();
        throw new \Exception();
    }

    public function up(Schema $schema): void
    {
        // Please modify to your needs
    }

    /**
     * Get hourly average based on last 30 days of sending.
     *
     * @return float|int
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getIpDetailsWithNullByteSymbols()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->container->get('doctrine')->getManager()->createQueryBuilder();
        $queryBuilder
            ->from('MauticCoreBundle:IpAddress', 'ia')
            ->where($queryBuilder->expr()->like('ip_details'));
    }
}
