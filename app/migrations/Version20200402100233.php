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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Entity\IpAddress;

final class Version20200402100233 extends AbstractMauticMigration
{
    const BATCH_SIZE = 50;

    public function getDescription(): string
    {
        return 'This migration fixes "Serialized array includes null-byte" exception when merging some contacts.';
    }

    public function up(Schema $schema): void
    {
        $ipAddressesIterator = $this->getIpAddressesWithNullByteSymbolsIterator();

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();
        $i             = 0;

        /** @var IpAddress $ipAddress */
        while (false !== ($ipAddress = $ipAddressesIterator->next())) {
            $ipAddress = current($ipAddress);
            $ipDetails = $ipAddress->getIpDetails();
            if (!is_array($ipDetails)) {
                continue;
            }

            if (1 > count($ipDetails)) {
                continue;
            }

            $entityHasToBeSaved = false;
            foreach ($ipDetails as $key => $record) {
                // Objects cannot be saved because they contain null byte symbols
                // if we try to serialize them
                if (is_object($record)) {
                    unset($ipDetails[$key]);
                    $entityHasToBeSaved = true;
                }
            }

            if (!$entityHasToBeSaved) {
                continue;
            }

            $ipAddress->setIpDetails($ipDetails);
            $entityManager->persist($ipAddress);
            ++$i;

            if (0 === ($i % static::BATCH_SIZE)) {
                $entityManager->flush();
                $entityManager->clear();
            }
        }

        $entityManager->flush();
    }

    public function getIpAddressesWithNullByteSymbolsIterator(): IterableResult
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->container->get('doctrine')->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('ia')
            ->from('MauticCoreBundle:IpAddress', 'ia')
            ->andWhere($queryBuilder->expr()->like('ia.ipDetails', ':search1'))
            ->setParameter('search1', '%{O:%')
            ->orWhere($queryBuilder->expr()->like('ia.ipDetails', ':search2'))
            ->setParameter('search2', '%;O:%');

        return $queryBuilder->getQuery()->iterate();
    }
}
