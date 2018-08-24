<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject;


use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;

interface ObjectInterface
{
    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return ObjectMapping[]
     */
    public function create(array $objects);

    /**
     * @param array             $ids
     * @param ObjectChangeDAO[] $objects
     */
    public function update(array $ids, array $objects);

    /**
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param int                $start
     * @param int                $limit
     *
     * @return mixed
     */
    public function findObjectsBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to, $start, $limit);
}