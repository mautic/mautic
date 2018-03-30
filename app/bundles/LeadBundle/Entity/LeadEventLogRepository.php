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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadEventLogRepository.
 */
class LeadEventLogRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Returns paginator with failed rows.
     *
     * @param        $importId
     * @param array  $args
     * @param string $bundle
     * @param string $object
     *
     * @return Paginator
     */
    public function getFailedRows($importId, array $args = [], $bundle = 'lead', $object = 'import')
    {
        return $this->getSpecificRows($importId, 'failed', $args, $bundle, $object);
    }

    /**
     * Returns paginator with specific type of rows.
     *
     * @param        $objectId
     * @param        $action
     * @param array  $args
     * @param string $bundle
     * @param string $object
     *
     * @return Paginator
     */
    public function getSpecificRows($objectId, $action, array $args = [], $bundle = 'lead', $object = 'import')
    {
        return $this->getEntities(
            array_merge(
            [
                'start'      => 0,
                'limit'      => 100,
                'orderBy'    => $this->getTableAlias().'.dateAdded',
                'orderByDir' => 'ASC',
                'filter'     => [
                    'force' => [
                        [
                            'column' => $this->getTableAlias().'.bundle',
                            'expr'   => 'eq',
                            'value'  => $bundle,
                        ],
                        [
                            'column' => $this->getTableAlias().'.object',
                            'expr'   => 'eq',
                            'value'  => $object,
                        ],
                        [
                            'column' => $this->getTableAlias().'.action',
                            'expr'   => 'eq',
                            'value'  => $action,
                        ],
                        [
                            'column' => $this->getTableAlias().'.objectId',
                            'expr'   => 'eq',
                            'value'  => $objectId,
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ],
            $args)
        );
    }

    /**
     * Loads data for specified lead events.
     *
     * @param string    $bundle
     * @param string    $object
     * @param Lead|null $lead
     * @param array     $options
     *
     * @return array
     */
    public function getEventsByLead($bundle, $object, Lead $lead = null, array $options = [])
    {
        $alias = $this->getTableAlias();
        $qb    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_event_log', $alias);

        if ($lead) {
            $qb->andWhere($alias.'.lead_id = :lead')
                ->setParameter('lead', $lead->getId());
        }

        $qb->andWhere($alias.'.bundle = :bundle')
            ->setParameter('bundle', $bundle)
            ->andWhere($alias.'.object = :object')
            ->setParameter('object', $object);

        if (!empty($options['search'])) {
            $qb->andWhere($qb->expr()->like($alias.'.properties', $qb->expr()->literal('%'.$options['search'].'%')));
        }

        return $this->getTimelineResults($qb, $options, $alias.'.action', $alias.'.date_added', [], ['date_added']);
    }

    /**
     * Loads data for specified lead events by action.
     *
     * @param string    $action
     * @param Lead|null $lead
     * @param array     $options
     *
     * @return array
     */
    public function getEventsByAction($action, Lead $lead = null, array $options = [])
    {
        $alias = $this->getTableAlias();
        $qb    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_event_log', $alias);

        if ($lead) {
            $qb->andWhere($alias.'.lead_id = :lead')
                ->setParameter('lead', $lead->getId());
        }

        $qb->andWhere($alias.'.action = :action')
            ->setParameter('action', $action);

        if (!empty($options['search'])) {
            $qb->andWhere($qb->expr()->like($alias.'.properties', $qb->expr()->literal('%'.$options['search'].'%')));
        }

        return $this->getTimelineResults($qb, $options, $alias.'.action', $alias.'.date_added', [], ['date_added']);
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param int $fromLeadId
     * @param int $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_event_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }

    /**
     * Defines default table alias for lead_event_log table.
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'lel';
    }
}
