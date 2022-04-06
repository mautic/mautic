<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

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

    /**
     * @param object $subQueryBuilder
     * @param object $filter
     * @param string $tableAlias
     *
     * @return void
     */
    protected function selectQuery($subQueryBuilder, $filter, $tableAlias)
    {
        $subQueryBuilder
            ->select('NULL')->from($filter->getTable(), $tableAlias)
            ->andWhere($tableAlias.'.lead_id = l.id')
            ->andWhere($tableAlias.'.date_submitted = ( select max(fs.date_submitted) from '.MAUTIC_TABLE_PREFIX.'form_submissions as fs where fs.lead_id = l.id)');
    }
}
