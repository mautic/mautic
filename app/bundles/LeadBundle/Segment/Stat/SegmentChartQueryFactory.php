<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Stat;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\Stat\ChartQuery\SegmentContactsLineChartQuery;

class SegmentChartQueryFactory
{
    /**
     * @return array
     */
    public function getContactsTotal(SegmentContactsLineChartQuery $query, ListModel $listModel)
    {
        $total = $listModel->getRepository()->getLeadCount($query->getSegmentId());

        return $query->getTotalStats($total);
    }

    /**
     * @return array
     */
    public function getContactsAdded(SegmentContactsLineChartQuery $query)
    {
        return ArrayHelper::sum($query->getAddedEventLogStats(), $query->getDataFromLeadListLeads());
    }

    /**
     * @return array
     */
    public function getContactsRemoved(SegmentContactsLineChartQuery $query)
    {
        return $query->getRemovedEventLogStats();
    }
}
