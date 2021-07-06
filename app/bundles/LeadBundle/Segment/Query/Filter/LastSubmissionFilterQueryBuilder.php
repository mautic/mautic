<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class LastSubmissionFilterQueryBuilder.
 */
class LastSubmissionFilterQueryBuilder extends ForeignValueFilterQueryBuilder
{
    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.foreign.value.lastsubmission';
    }

    protected function selectQuery($subQueryBuilder, $filter, $tableAlias)
    {
        $subQueryBuilder
            ->select('NULL')->from($filter->getTable(), $tableAlias)
            ->andWhere($tableAlias.'.lead_id = l.id')
            ->andWhere($tableAlias.'.date_submitted = ( select max(fs.date_submitted) from '.MAUTIC_TABLE_PREFIX.'form_submissions as fs where fs.lead_id = l.id)');
    }
}
