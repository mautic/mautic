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
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;

/**
 * Class ForeignValueFilterQueryBuilder.
 */
class ChannelClickQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var ContactSegmentFilterFactory
     */
    private $leadSegmentFilterFactory;

    /**
     * ChannelClickQueryBuilder constructor.
     */
    public function __construct(
        RandomParameterName $randomParameterNameService,
        ContactSegmentFilterFactory $leadSegmentFilterFactory
    ) {
        parent::__construct($randomParameterNameService);

        $this->leadSegmentFilterFactory = $leadSegmentFilterFactory;
    }

    /**
     * @return string
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.channel_click.value';
    }

    /**
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterChannel  = $this->getChannel($filter->getField());

        $filterParameters = $filter->getParameterValue();

        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        $tableAlias = $this->generateRandomParameterName();

        $subQb = $queryBuilder->getConnection()->createQueryBuilder();
        $expr  = $subQb->expr()->andX(
            $subQb->expr()->isNotNull($tableAlias.'.redirect_id'),
            $subQb->expr()->isNotNull($tableAlias.'.lead_id'),
            $subQb->expr()->eq($tableAlias.'.source', $subQb->expr()->literal($filterChannel))
        );

        if ($this->isDateBased($filter->getField())) {
            $expr->add(
                $subQb->expr()->$filterOperator($tableAlias.'.date_hit', $filter->getParameterHolder($parameters))
            );
        }

        $subQb->select($tableAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', $tableAlias)
            ->where($expr);

        $queryBuilder->addLogic($queryBuilder->expr()->in('l.id', $subQb->getSQL()), $filter->getGlue());

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    private function getChannel($name)
    {
        if ('email_id' === $name) {
            // BC for existing filter
            return 'email';
        }

        return str_replace(['_clicked_link', '_date'], '', $name);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    private function isDateBased($name)
    {
        return false !== strpos($name, '_date');
    }
}
