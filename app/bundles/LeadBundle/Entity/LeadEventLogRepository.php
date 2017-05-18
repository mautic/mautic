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
     * Defines default table alias for lead_event_log table.
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'lel';
    }
}
