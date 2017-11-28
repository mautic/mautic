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
     * @param string    $bundle
     * @param string    $object
     * @param Lead|null $lead
     * @param array     $options
     *
     * @return array
     */
    public function getEventsByLead($bundle, $object, Lead $lead = null, array $options)
    {
        $alias = $this->getTableAlias();
        $qb    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_event_log', $alias)
            ->where($alias.'.bundle = :bundle')
            ->setParameter('bundle', $bundle)
            ->andWhere($alias.'.object = :object')
            ->setParameter('object', $object);

        if ($lead) {
            $qb->andWhere($alias.'.lead_id = :lead')
                ->setParameter('lead', $lead->getId());
        }

        if (!empty($options['search'])) {
            $qb->andWhere($qb->expr()->like($alias.'.properties', $qb->expr()->literal('%'.$options['search'].'%')));
        }

        return $this->getTimelineResults($qb, $options, $alias.'.original_file', $alias.'.date_added', ['query'], ['date_added']);
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
