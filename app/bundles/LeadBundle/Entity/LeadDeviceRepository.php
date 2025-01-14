<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadDeviceRepository.
 */
class LeadDeviceRepository extends CommonRepository
{
    /**
     * {@inhertidoc}.
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
     * @param null $deviceNames
     * @param null $deviceBrands
     * @param null $deviceModels
     * @param null $deviceId
     *
     * @return array
     */
    public function getDevice($lead, $deviceNames = null, $deviceBrands = null, $deviceModels = null, $deviceOss = null, $deviceId = null)
    {
        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('es.id as id, es.device as device')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es');

        if (null !== $deviceNames) {
            if (!is_array($deviceNames)) {
                $deviceNames = [$deviceNames];
            }
            foreach ($deviceNames as $key => $deviceName) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device', ':device'.$key)
                )
                    ->setParameter('device'.$key, $deviceName);
            }
        }

        if (null !== $deviceBrands) {
            if (!is_array($deviceBrands)) {
                $deviceBrands = [$deviceBrands];
            }
            foreach ($deviceBrands as $key => $deviceBrand) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device_brand', ':deviceBrand'.$key)
                )
                    ->setParameter('deviceBrand'.$key, $deviceBrand);
            }
        }

        if (null !== $deviceModels) {
            if (!is_array($deviceModels)) {
                $deviceModels = [$deviceModels];
            }
            foreach ($deviceModels as $key => $deviceModel) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device_model', ':deviceModel'.$key)
                )
                    ->setParameter('deviceModel'.$key, $deviceModel);
            }
        }

        if (null !== $deviceOss) {
            if (!is_array($deviceOss)) {
                $deviceOss = [$deviceOss];
            }
            foreach ($deviceOss as $key => $deviceOs) {
                $sq->andWhere(
                    $sq->expr()->eq('es.device_os_name', ':deviceOs'.$key)
                )
                    ->setParameter('deviceOs'.$key, $deviceOs);
            }
        }

        if (null !== $deviceId) {
            $sq->andWhere(
                $sq->expr()->eq('es.id', $deviceId)
            );
        } elseif (null !== $lead) {
            $sq->andWhere(
                $sq->expr()->eq('es.lead_id', $lead->getId())
            );
        }

        //get totals
        $device = $sq->execute()->fetchAll();

        return (!empty($device)) ? $device[0] : [];
    }

    /**
     * @param string $trackingId
     *
     * @return LeadDevice|null
     */
    public function getByTrackingId($trackingId)
    {
        /** @var LeadDevice $leadDevice */
        $leadDevice = $this->findOneBy([
            'trackingId' => $trackingId,
        ]);

        return $leadDevice;
    }

    /**
     * Check if there is at least one device with filled tracking code assigned to Lead.
     *
     * @return bool
     */
    public function isAnyLeadDeviceTracked(Lead $lead)
    {
        $alias = $this->getTableAlias();
        $qb    = $this->createQueryBuilder($alias);
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq($alias.'.lead', ':lead'),
                $qb->expr()->isNotNull($alias.'.trackingId')
            )
        )
            ->setParameter('lead', $lead);

        $devices = $qb->getQuery()->getResult();

        return !empty($devices);
    }

    /**
     * @return array
     */
    public function getLeadDevices(Lead $lead)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        return $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_devices', 'es')
            ->where('lead_id = :leadId')
            ->setParameter('leadId', (int) $lead->getId())
            ->orderBy('date_added', 'desc')
            ->execute()
            ->fetchAll();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_devices')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }
}
