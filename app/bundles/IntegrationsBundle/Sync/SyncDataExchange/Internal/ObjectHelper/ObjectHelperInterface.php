<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper;

use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;

interface ObjectHelperInterface
{
    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return ObjectMapping[]
     */
    public function create(array $objects): array;

    /**
     * @param array             $ids
     * @param ObjectChangeDAO[] $objects
     *
     * @return UpdatedObjectMappingDAO[]
     */
    public function update(array $ids, array $objects): array;

    /**
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int                $start
     * @param int                $limit
     *
     * @return array
     */
    public function findObjectsBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to, $start, $limit): array;

    /**
     * @param array $ids
     *
     * @return array
     */
    public function findObjectsByIds(array $ids): array;

    /**
     * @param array $fields
     *
     * @return array
     */
    public function findObjectsByFieldValues(array $fields): array;
}
