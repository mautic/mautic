<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;

class PostCountRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        return parent::getEntities($args);
    }

    /**
     * Fetch Lead stats for some period of time.
     *
     * @param int    $quantity of units
     * @param string $unit     of time php.net/manual/en/class.dateinterval.php#dateinterval.props
     * @param array  $options
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStatsPost($dateFrom, $dateTo, $options)
    {
        $chartQuery = new ChartQuery($this->getEntityManager()->getConnection(), $dateFrom, $dateTo);

        // Load points for selected period
        $q = $chartQuery->prepareTimeDataQuery(MAUTIC_TABLE_PREFIX.'monitor_post_count', 'post_date', $options);
        if (isset($options['monitor_id'])) {
            $q->andwhere($q->expr()->eq('t.monitor_id', (int) $options['monitor_id']));
        }

        $data = $chartQuery->loadAndBuildTimeData($q);

        return $data;
    }
}
