<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject;


use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;

class CompanyObject implements ObjectInterface
{
    /**
     * @var CompanyModel
     */
    private $model;

    /**
     * @var CompanyRepository
     */
    private $repository;

    /**
     * @param OrderDAO          $syncOrder
     * @param ObjectChangeDAO[] $objects
     */
    public function create(array $objects, OrderDAO $syncOrder)
    {
        // TODO: Implement create() method.
    }

    /**
     * @param array             $ids
     * @param ObjectChangeDAO[] $objects
     */
    public function update(array $ids, array $objects)
    {
        // TODO: Implement update() method.
    }
}