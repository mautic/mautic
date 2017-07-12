<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadDeviceRepository.
 */
class LeadDeviceRepository extends CommonRepository
{
    /**
     * {@inhertidoc}.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder($this->getTableAlias())
            ->select($this->getTableAlias());
        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'd';
    }

    /**
     * @param      $lead
     * @param null $deviceName
     * @param null $deviceBrand
     * @param null $deviceModel
     *
     * @return LeadDevice|null
     */
    public function getDeviceEntity(Lead $lead, $deviceName = null, $deviceBrand = null, $deviceModel = null)
    {
        $alias = $this->getTableAlias();
        $qb = $this->createQueryBuilder($alias);

        if ($lead !== null) {
            $qb->andWhere(
                $qb->expr()->eq($alias.'.lead', ':lead')
            )
                ->setParameter('lead', $lead);
        }

        if ($deviceName !== null) {
            $qb->andWhere(
                $qb->expr()->eq($alias.'.device', ':device')
            )
                ->setParameter('device', $deviceName);
        }

        if ($deviceBrand !== null) {
            $qb->andWhere(
                $qb->expr()->eq($alias.'.deviceBrand', ':deviceBrand')
            )
                ->setParameter('deviceBrand', $deviceBrand);
        }

        if ($deviceModel !== null) {
            $qb->andWhere(
                $qb->expr()->eq($alias.'.deviceModel', ':deviceModel')
            )
                ->setParameter('deviceModel', $deviceModel);
        }

        $results = $qb->getQuery()->getResult();

        return (count($results)) ? $results[0] : null;
    }

    /**
     * @param      $lead
     * @param null $deviceName
     * @param null $deviceBrand
     * @param null $deviceModel
     *
     * @return array
     */
    public function getDevice($lead, $deviceName = null, $deviceBrand = null, $deviceModel = null)
    {
        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('es.id as id, es.device as device, es.device_fingerprint')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es');

        if ($lead !== null) {
            $sq->andWhere(
                $sq->expr()->eq('es.lead_id', $lead->getId())
            );
        }

        if ($deviceName !== null) {
            $sq->andWhere(
                $sq->expr()->eq('es.device', ':device')
            )
                ->setParameter('device', $deviceName);
        }

        if ($deviceBrand !== null) {
            $sq->andWhere(
                $sq->expr()->eq('es.device_brand', ':deviceBrand')
            )
                ->setParameter('deviceBrand', $deviceBrand);
        }

        if ($deviceModel !== null) {
            $sq->andWhere(
                $sq->expr()->eq('es.device_model', ':deviceModel')
            )
                ->setParameter('deviceModel', $deviceModel);
        }

        //get totals
        $device = $sq->execute()->fetchAll();

        return (!empty($device)) ? $device[0] : [];
    }

    /**
     * @param string $fingerprint
     *
     * @return LeadDevice
     */
    public function getDeviceByFingerprint($fingerprint)
    {
        if (!$fingerprint) {
            return null;
        }

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('es.id as id, es.lead_id as lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es');

        $sq->where(
            $sq->expr()->eq('es.device_fingerprint', ':fingerprint')
        )
            ->setParameter('fingerprint', $fingerprint);

        //get the first match
        $device = $sq->execute()->fetch();

        return $device ? $device : null;
    }
}
