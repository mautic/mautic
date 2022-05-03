<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;

class PostCountRepository extends CommonRepository
{
    /**
     * Fetch Lead stats for some period of time.
     *
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStatsPost($dateFrom, $dateTo, $options)
    {
        $chartQuery = new ChartQuery($this->getEntityManager()->getConnection(), $dateFrom, $dateTo);

        // Load points for selected periods
        $q = $chartQuery->prepareTimeDataQuery(MAUTIC_TABLE_PREFIX.'monitor_post_count', 'post_date', $options, 'post_count', 'sum');
        if (isset($options['monitor_id'])) {
            $q->andwhere($q->expr()->eq('t.monitor_id', (int) $options['monitor_id']));
        }

        return $chartQuery->loadAndBuildTimeData($q);
    }
}
