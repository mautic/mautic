<?php

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
