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
 * Class LeadEventLogRepository.
 */
class LeadEventLogRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Returns paginator with failed rows.
     *
     * @param int   $importId
     * @param array $args
     *
     * @return Paginator
     */
    public function getFailedRows($importId, array $args = [])
    {
        return $this->getSpecificRows($importId, 'failed', $args);
    }

    /**
     * Returns paginator with specific type of rows.
     *
     * @param int    $importId
     * @param string $type
     * @param array  $args
     *
     * @return Paginator
     */
    public function getSpecificRows($importId, $type, array $args = [])
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
                            'value'  => 'lead',
                        ],
                        [
                            'column' => $this->getTableAlias().'.object',
                            'expr'   => 'eq',
                            'value'  => 'import',
                        ],
                        [
                            'column' => $this->getTableAlias().'.action',
                            'expr'   => 'eq',
                            'value'  => 'failed',
                        ],
                        [
                            'column' => $this->getTableAlias().'.objectId',
                            'expr'   => 'eq',
                            'value'  => $importId,
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
     * @param string $bundle
     * @param string $object
     * @param Lead   $lead
     * @param array  $options
     *
     * @return array
     */
    public function getEventsByLead($bundle, $object, Lead $lead, array $options)
    {
        if (empty($lead)) {
            return [];
        }

        $alias = $this->getTableAlias();

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_event_log', $alias)
            ->where($alias.'.lead_id = :lead')
            ->setParameter('lead', $lead->getId())
            ->andWhere($alias.'.bundle = :bundle')
            ->setParameter('bundle', $bundle)
            ->andWhere($alias.'.object = :object')
            ->setParameter('object', $object);

        if (isset($options['search']) && $options['search']) {
            $qb->andWhere($qb->expr()->like($alias.'.action', $qb->expr()->literal('%'.$options['search'].'%')));
        }

        return $this->getTimelineResults($qb, $options, $alias.'.action', $alias.'.date_added', ['query'], ['date_added']);
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
