<?php

/*
 * @copyright   2014-2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Services;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\LeadSegmentFilters;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;

class LeadSegmentQueryBuilder
{
    use LeadSegmentFilterQueryBuilderTrait;

    /** @var EntityManager */
    private $entityManager;

    /** @var RandomParameterName */
    private $randomParameterName;

    private $tableAliases = [];

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
    private $schema;

    public function __construct(EntityManager $entityManager, RandomParameterName $randomParameterName)
    {
        $this->entityManager       = $entityManager;
        $this->randomParameterName = $randomParameterName;
        $this->schema              = $this->entityManager->getConnection()->getSchemaManager();
    }

    public function getLeadsQueryBuilder($id, LeadSegmentFilters $leadSegmentFilters)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = new \Mautic\LeadBundle\Segment\Query\QueryBuilder($this->entityManager->getConnection());

        $queryBuilder->select('*')->from('leads', 'l');

        /** @var LeadSegmentFilter $filter */
        foreach ($leadSegmentFilters as $filter) {
            $queryBuilder = $filter->applyQuery($queryBuilder);
        }

        return $queryBuilder;
        echo 'SQL parameters:';
        dump($q->getParameters());

        // Leads that do not have any record in the lead_lists_leads table for this lead list
        // For non null fields - it's apparently better to use left join over not exists due to not using nullable
        // fields - https://explainextended.com/2009/09/18/not-in-vs-not-exists-vs-left-join-is-null-mysql/
        $listOnExpr = $q->expr()->andX($q->expr()->eq('ll.leadlist_id', $id), $q->expr()->eq('ll.lead_id', 'l.id'));

        if (!empty($batchLimiters['dateTime'])) {
            // Only leads in the list at the time of count
            $listOnExpr->add($q->expr()->lte('ll.date_added', $q->expr()->literal($batchLimiters['dateTime'])));
        }

        $q->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll', $listOnExpr);

        $expr->add($q->expr()->isNull('ll.lead_id'));

        if ($batchExpr->count()) {
            $expr->add($batchExpr);
        }

        if ($expr->count()) {
            $q->andWhere($expr);
        }

        if (!empty($limit)) {
            $q->setFirstResult($start)->setMaxResults($limit);
        }

        // remove any possible group by
        $q->resetQueryPart('groupBy');

        var_dump($q->getSQL());
        echo 'SQL parameters:';
        dump($q->getParameters());

        $results = $q->execute()->fetchAll();

        $leads = [];
        foreach ($results as $r) {
            $leads = ['count' => $r['lead_count'], 'maxId' => $r['max_id']];
            if ($withMinId) {
                $leads['minId'] = $r['min_id'];
            }
        }

        return $leads;
    }

    private function getQueryPart(LeadSegmentFilter $filter, QueryBuilder $qb)
    {
        $qb = $filter->createQuery($qb);

        return $qb;

        //            //the next one will determine the group
        //            if ($leadSegmentFilter->getGlue() === 'or') {
        //                // Create a new group of andX expressions
        //                if ($groupExpr->count()) {
        //                    $groups[]  = $groupExpr;
        //                    $groupExpr = $q->expr()
        //                                   ->andX()
        //                    ;
        //                }
        //            }

        $parameterName = $this->generateRandomParameterName();

        //@todo what is this?
        //        $ignoreAutoFilter = false;
        //
        //            $func = $filter->getFunc();
        //
        //            // Generate a unique alias
        //            $alias = $this->generateRandomParameterName();
        //
        //            var_dump($func . ":" . $leadSegmentFilter->getField());
        //            var_dump($exprParameter);

        switch ($leadSegmentFilter->getField()) {
            case 'hit_url':
            case 'referer':
            case 'source':
            case 'source_id':
            case 'url_title':
                $operand = in_array($func, ['eq', 'like', 'regexp', 'notRegexp', 'startsWith', 'endsWith', 'contains']) ? 'EXISTS' : 'NOT EXISTS';

                $ignoreAutoFilter = true;
                $column           = $leadSegmentFilter->getField();

                if ($column === 'hit_url') {
                    $column = 'url';
                }

                $subqb = $this->entityManager->getConnection()->createQueryBuilder()->select('id')
                                             ->from(MAUTIC_TABLE_PREFIX.'page_hits', $alias);

                switch ($func) {
                    case 'eq':
                    case 'neq':
                        $parameters[$parameter] = $leadSegmentFilter->getFilter();
                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->eq($alias.'.'.$column, $exprParameter), $q->expr()
                                                                                                         ->eq($alias.'.lead_id', 'l.id')));
                        break;
                    case 'regexp':
                    case 'notRegexp':
                        $parameters[$parameter] = $this->prepareRegex($leadSegmentFilter->getFilter());
                        $not                    = ($func === 'notRegexp') ? ' NOT' : '';
                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->eq($alias.'.lead_id', 'l.id'), $alias.'.'.$column.$not.' REGEXP '.$exprParameter));
                        break;
                    case 'like':
                    case 'notLike':
                    case 'startsWith':
                    case 'endsWith':
                    case 'contains':
                        switch ($func) {
                            case 'like':
                            case 'notLike':
                            case 'contains':
                                $parameters[$parameter] = '%'.$leadSegmentFilter->getFilter().'%';
                                break;
                            case 'startsWith':
                                $parameters[$parameter] = $leadSegmentFilter->getFilter().'%';
                                break;
                            case 'endsWith':
                                $parameters[$parameter] = '%'.$leadSegmentFilter->getFilter();
                                break;
                        }

                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->like($alias.'.'.$column, $exprParameter), $q->expr()
                                                                                                           ->eq($alias.'.lead_id', 'l.id')));
                        break;
                }

                $groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));
                break;
            case 'device_model':
                $ignoreAutoFilter = true;
                $operand          = in_array($func, ['eq', 'like', 'regexp', 'notRegexp']) ? 'EXISTS' : 'NOT EXISTS';

                $column = $leadSegmentFilter->getField();
                $subqb  = $this->entityManager->getConnection()->createQueryBuilder()->select('id')
                                              ->from(MAUTIC_TABLE_PREFIX.'lead_devices', $alias);
                switch ($func) {
                    case 'eq':
                    case 'neq':
                        $parameters[$parameter] = $leadSegmentFilter->getFilter();
                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->eq($alias.'.'.$column, $exprParameter), $q->expr()
                                                                                                         ->eq($alias.'.lead_id', 'l.id')));
                        break;
                    case 'like':
                    case '!like':
                        $parameters[$parameter] = '%'.$leadSegmentFilter->getFilter().'%';
                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->like($alias.'.'.$column, $exprParameter), $q->expr()
                                                                                                           ->eq($alias.'.lead_id', 'l.id')));
                        break;
                    case 'regexp':
                    case 'notRegexp':
                        $parameters[$parameter] = $this->prepareRegex($leadSegmentFilter->getFilter());
                        $not                    = ($func === 'notRegexp') ? ' NOT' : '';
                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->eq($alias.'.lead_id', 'l.id'), $alias.'.'.$column.$not.' REGEXP '.$exprParameter));
                        break;
                }

                $groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));

                break;
            case 'hit_url_date':
            case 'lead_email_read_date':
                $operand = (in_array($func, ['eq', 'gt', 'lt', 'gte', 'lte', 'between'])) ? 'EXISTS' : 'NOT EXISTS';
                $table   = 'page_hits';
                $column  = 'date_hit';

                if ($leadSegmentFilter->getField() === 'lead_email_read_date') {
                    $column = 'date_read';
                    $table  = 'email_stats';
                }

                if ($filterField == 'lead_email_read_date') {
                    var_dump($func);
                }

                $subqb = $this->entityManager->getConnection()->createQueryBuilder()->select('id')
                                             ->from(MAUTIC_TABLE_PREFIX.$table, $alias);

                switch ($func) {
                    case 'eq':
                    case 'neq':
                        $parameters[$parameter] = $leadSegmentFilter->getFilter();

                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->eq($alias.'.'.$column, $exprParameter), $q->expr()
                                                                                                         ->eq($alias.'.lead_id', 'l.id')));
                        break;
                    case 'between':
                    case 'notBetween':
                        // Filter should be saved with double || to separate options
                        $parameter2              = $this->generateRandomParameterName();
                        $parameters[$parameter]  = $leadSegmentFilter->getFilter()[0];
                        $parameters[$parameter2] = $leadSegmentFilter->getFilter()[1];
                        $exprParameter2          = ":$parameter2";
                        $ignoreAutoFilter        = true;
                        $field                   = $column;

                        if ($func === 'between') {
                            $subqb->where($q->expr()->andX($q->expr()
                                                             ->gte($alias.'.'.$field, $exprParameter), $q->expr()
                                                                                                             ->lt($alias.'.'.$field, $exprParameter2), $q->expr()
                                                                                                                                                             ->eq($alias.'.lead_id', 'l.id')));
                        } else {
                            $subqb->where($q->expr()->andX($q->expr()
                                                             ->lt($alias.'.'.$field, $exprParameter), $q->expr()
                                                                                                            ->gte($alias.'.'.$field, $exprParameter2), $q->expr()
                                                                                                                                                             ->eq($alias.'.lead_id', 'l.id')));
                        }
                        break;
                    default:
                        $parameter2 = $this->generateRandomParameterName();

                        if ($filterField == 'lead_email_read_date') {
                            var_dump($exprParameter);
                        }
                        $parameters[$parameter2] = $leadSegmentFilter->getFilter();

                        $subqb->where($q->expr()->andX($q->expr()
                                                         ->$func($alias.'.'.$column, $parameter2), $q->expr()
                                                                                                         ->eq($alias.'.lead_id', 'l.id')));
                        break;
                }
                $groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));
                break;
            case 'page_id':
            case 'email_id':
            case 'redirect_id':
            case 'notification':
                $operand = ($func === 'eq') ? 'EXISTS' : 'NOT EXISTS';
                $column  = $leadSegmentFilter->getField();
                $table   = 'page_hits';
                $select  = 'id';

                if ($leadSegmentFilter->getField() === 'notification') {
                    $table  = 'push_ids';
                    $column = 'id';
                }

                $subqb = $this->entityManager->getConnection()->createQueryBuilder()->select($select)
                                             ->from(MAUTIC_TABLE_PREFIX.$table, $alias);

                if ($leadSegmentFilter->getFilter() == 1) {
                    $subqb->where($q->expr()->andX($q->expr()->isNotNull($alias.'.'.$column), $q->expr()
                                                                                                    ->eq($alias.'.lead_id', 'l.id')));
                } else {
                    $subqb->where($q->expr()->andX($q->expr()->isNull($alias.'.'.$column), $q->expr()
                                                                                                 ->eq($alias.'.lead_id', 'l.id')));
                }

                $groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));
                break;
            case 'sessions':
                $operand = 'EXISTS';
                $table   = 'page_hits';
                $select  = 'COUNT(id)';
                $subqb   = $this->entityManager->getConnection()->createQueryBuilder()->select($select)
                                               ->from(MAUTIC_TABLE_PREFIX.$table, $alias);

                $alias2 = $this->generateRandomParameterName();
                $subqb2 = $this->entityManager->getConnection()->createQueryBuilder()->select($alias2.'.id')
                                              ->from(MAUTIC_TABLE_PREFIX.$table, $alias2);

                $subqb2->where($q->expr()->andX($q->expr()->eq($alias2.'.lead_id', 'l.id'), $q->expr()
                                                                                                ->gt($alias2.'.date_hit', '('.$alias.'.date_hit - INTERVAL 30 MINUTE)'), $q->expr()
                                                                                                                                                                                 ->lt($alias2.'.date_hit', $alias.'.date_hit')));

                $parameters[$parameter] = $leadSegmentFilter->getFilter();

                $subqb->where($q->expr()->andX($q->expr()->eq($alias.'.lead_id', 'l.id'), $q->expr()
                                                                                              ->isNull($alias.'.email_id'), $q->expr()
                                                                                                                                ->isNull($alias.'.redirect_id'), sprintf('%s (%s)', 'NOT EXISTS', $subqb2->getSQL())));

                $opr = '';
                switch ($func) {
                    case 'eq':
                        $opr = '=';
                        break;
                    case 'gt':
                        $opr = '>';
                        break;
                    case 'gte':
                        $opr = '>=';
                        break;
                    case 'lt':
                        $opr = '<';
                        break;
                    case 'lte':
                        $opr = '<=';
                        break;
                }
                if ($opr) {
                    $parameters[$parameter] = $leadSegmentFilter->getFilter();
                    $subqb->having($select.$opr.$leadSegmentFilter->getFilter());
                }
                $groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));
                break;
            case 'hit_url_count':
            case 'lead_email_read_count':
                $operand = 'EXISTS';
                $table   = 'page_hits';
                $select  = 'COUNT(id)';
                if ($leadSegmentFilter->getField() === 'lead_email_read_count') {
                    $table  = 'email_stats';
                    $select = 'COALESCE(SUM(open_count),0)';
                }
                $subqb = $this->entityManager->getConnection()->createQueryBuilder()->select($select)
                                             ->from(MAUTIC_TABLE_PREFIX.$table, $alias);

                $parameters[$parameter] = $leadSegmentFilter->getFilter();
                $subqb->where($q->expr()->andX($q->expr()->eq($alias.'.lead_id', 'l.id')));

                $opr = '';
                switch ($func) {
                    case 'eq':
                        $opr = '=';
                        break;
                    case 'gt':
                        $opr = '>';
                        break;
                    case 'gte':
                        $opr = '>=';
                        break;
                    case 'lt':
                        $opr = '<';
                        break;
                    case 'lte':
                        $opr = '<=';
                        break;
                }

                if ($opr) {
                    $parameters[$parameter] = $leadSegmentFilter->getFilter();
                    $subqb->having($select.$opr.$leadSegmentFilter->getFilter());
                }

                $groupExpr->add(sprintf('%s (%s)', $operand, $subqb->getSQL()));
                break;

            case 'dnc_bounced':
            case 'dnc_unsubscribed':
            case 'dnc_bounced_sms':
            case 'dnc_unsubscribed_sms':
                // Special handling of do not contact
                $func = (($func === 'eq' && $leadSegmentFilter->getFilter()) || ($func === 'neq' && !$leadSegmentFilter->getFilter())) ? 'EXISTS' : 'NOT EXISTS';

                $parts   = explode('_', $leadSegmentFilter->getField());
                $channel = 'email';

                if (count($parts) === 3) {
                    $channel = $parts[2];
                }

                $channelParameter = $this->generateRandomParameterName();
                $subqb            = $this->entityManager->getConnection()->createQueryBuilder()->select('null')
                                                        ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', $alias)
                                                        ->where($q->expr()->andX($q->expr()
                                                                                   ->eq($alias.'.reason', $exprParameter), $q->expr()
                                                                                                                               ->eq($alias.'.lead_id', 'l.id'), $q->expr()
                                                                                                                                                                    ->eq($alias.'.channel', ":$channelParameter")));

                $groupExpr->add(sprintf('%s (%s)', $func, $subqb->getSQL()));

                // Filter will always be true and differentiated via EXISTS/NOT EXISTS
                $leadSegmentFilter->setFilter(true);

                $ignoreAutoFilter = true;

                $parameters[$parameter]        = ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED;
                $parameters[$channelParameter] = $channel;

                break;

            case 'leadlist':
                $table                       = 'lead_lists_leads';
                $column                      = 'leadlist_id';
                $falseParameter              = $this->generateRandomParameterName();
                $parameters[$falseParameter] = false;
                $trueParameter               = $this->generateRandomParameterName();
                $parameters[$trueParameter]  = true;
                $func                        = in_array($func, ['eq', 'in']) ? 'EXISTS' : 'NOT EXISTS';
                $ignoreAutoFilter            = true;

                if ($filterListIds = (array) $leadSegmentFilter->getFilter()) {
                    $listQb = $this->entityManager->getConnection()->createQueryBuilder()->select('l.id, l.filters')
                                                  ->from(MAUTIC_TABLE_PREFIX.'lead_lists', 'l');
                    $listQb->where($listQb->expr()->in('l.id', $filterListIds));
                    $filterLists = $listQb->execute()->fetchAll();
                    $not         = 'NOT EXISTS' === $func;

                    // Each segment's filters must be appended as ORs so that each list is evaluated individually
                    $existsExpr = $not ? $listQb->expr()->andX() : $listQb->expr()->orX();

                    foreach ($filterLists as $list) {
                        $alias = $this->generateRandomParameterName();
                        $id    = (int) $list['id'];
                        if ($id === (int) $listId) {
                            // Ignore as somehow self is included in the list
                            continue;
                        }

                        $listFilters = unserialize($list['filters']);
                        if (empty($listFilters)) {
                            // Use an EXISTS/NOT EXISTS on contact membership as this is a manual list
                            $subQb = $this->createFilterExpressionSubQuery($table, $alias, $column, $id, $parameters, [$alias.'.manually_removed' => $falseParameter]);
                        } else {
                            // Build a EXISTS/NOT EXISTS using the filters for this list to include/exclude those not processed yet
                            // but also leverage the current membership to take into account those manually added or removed from the segment

                            // Build a "live" query based on current filters to catch those that have not been processed yet
                            $subQb      = $this->createFilterExpressionSubQuery('leads', $alias, null, null, $parameters);
                            $filterExpr = $this->generateSegmentExpression($leadSegmentFilters, $subQb, $id);

                            // Left join membership to account for manually added and removed
                            $membershipAlias = $this->generateRandomParameterName();
                            $subQb->leftJoin($alias, MAUTIC_TABLE_PREFIX.$table, $membershipAlias, "$membershipAlias.lead_id = $alias.id AND $membershipAlias.leadlist_id = $id")
                                  ->where($subQb->expr()->orX($filterExpr, $subQb->expr()
                                                                                 ->eq("$membershipAlias.manually_added", ":$trueParameter") //include manually added
                                  ))->andWhere($subQb->expr()->eq("$alias.id", 'l.id'), $subQb->expr()
                                                                                              ->orX($subQb->expr()
                                                                                                          ->isNull("$membershipAlias.manually_removed"), // account for those not in a list yet
                                                                                                    $subQb->expr()
                                                                                                          ->eq("$membershipAlias.manually_removed", ":$falseParameter") //exclude manually removed
                                                                                              ));
                        }

                        $existsExpr->add(sprintf('%s (%s)', $func, $subQb->getSQL()));
                    }

                    if ($existsExpr->count()) {
                        $groupExpr->add($existsExpr);
                    }
                }

                break;
            case 'tags':
            case 'globalcategory':
            case 'lead_email_received':
            case 'lead_email_sent':
            case 'device_type':
            case 'device_brand':
            case 'device_os':
                // Special handling of lead lists and tags
                $func = in_array($func, ['eq', 'in'], true) ? 'EXISTS' : 'NOT EXISTS';

                $ignoreAutoFilter = true;

                // Collect these and apply after building the query because we'll want to apply the lead first for each of the subqueries
                $subQueryFilters = [];
                switch ($leadSegmentFilter->getField()) {
                    case 'tags':
                        $table  = 'lead_tags_xref';
                        $column = 'tag_id';
                        break;
                    case 'globalcategory':
                        $table  = 'lead_categories';
                        $column = 'category_id';
                        break;
                    case 'lead_email_received':
                        $table  = 'email_stats';
                        $column = 'email_id';

                        $trueParameter                        = $this->generateRandomParameterName();
                        $subQueryFilters[$alias.'.is_read']   = $trueParameter;
                        $parameters[$trueParameter]           = true;
                        break;
                    case 'lead_email_sent':
                        $table  = 'email_stats';
                        $column = 'email_id';
                        break;
                    case 'device_type':
                        $table  = 'lead_devices';
                        $column = 'device';
                        break;
                    case 'device_brand':
                        $table  = 'lead_devices';
                        $column = 'device_brand';
                        break;
                    case 'device_os':
                        $table  = 'lead_devices';
                        $column = 'device_os_name';
                        break;
                }

                $subQb = $this->createFilterExpressionSubQuery($table, $alias, $column, $leadSegmentFilter->getFilter(), $parameters, $subQueryFilters);

                $groupExpr->add(sprintf('%s (%s)', $func, $subQb->getSQL()));
                break;
            case 'stage':
                // A note here that SQL EXISTS is being used for the eq and neq cases.
                // I think this code might be inefficient since the sub-query is rerun
                // for every row in the outer query's table. This might have to be refactored later on
                // if performance is desired.

                $subQb = $this->entityManager->getConnection()->createQueryBuilder()->select('null')
                                             ->from(MAUTIC_TABLE_PREFIX.'stages', $alias);

                switch ($func) {
                    case 'empty':
                        $groupExpr->add($q->expr()->isNull('l.stage_id'));
                        break;
                    case 'notEmpty':
                        $groupExpr->add($q->expr()->isNotNull('l.stage_id'));
                        break;
                    case 'eq':
                        $parameters[$parameter] = $leadSegmentFilter->getFilter();

                        $subQb->where($q->expr()->andX($q->expr()->eq($alias.'.id', 'l.stage_id'), $q->expr()
                                                                                                       ->eq($alias.'.id', ":$parameter")));
                        $groupExpr->add(sprintf('EXISTS (%s)', $subQb->getSQL()));
                        break;
                    case 'neq':
                        $parameters[$parameter] = $leadSegmentFilter->getFilter();

                        $subQb->where($q->expr()->andX($q->expr()->eq($alias.'.id', 'l.stage_id'), $q->expr()
                                                                                                       ->eq($alias.'.id', ":$parameter")));
                        $groupExpr->add(sprintf('NOT EXISTS (%s)', $subQb->getSQL()));
                        break;
                }

                break;
            case 'integration_campaigns':
                $parameter2       = $this->generateRandomParameterName();
                $operand          = in_array($func, ['eq', 'neq']) ? 'EXISTS' : 'NOT EXISTS';
                $ignoreAutoFilter = true;

                $subQb = $this->entityManager->getConnection()->createQueryBuilder()->select('null')
                                             ->from(MAUTIC_TABLE_PREFIX.'integration_entity', $alias);
                switch ($func) {
                    case 'eq':
                    case 'neq':
                        if (strpos($leadSegmentFilter->getFilter(), '::') !== false) {
                            list($integrationName, $campaignId) = explode('::', $leadSegmentFilter->getFilter());
                        } else {
                            // Assuming this is a Salesforce integration for BC with pre 2.11.0
                            $integrationName = 'Salesforce';
                            $campaignId      = $leadSegmentFilter->getFilter();
                        }

                        $parameters[$parameter]  = $campaignId;
                        $parameters[$parameter2] = $integrationName;
                        $subQb->where($q->expr()->andX($q->expr()
                                                         ->eq($alias.'.integration', ":$parameter2"), $q->expr()
                                                                                                          ->eq($alias.'.integration_entity', "'CampaignMember'"), $q->expr()
                                                                                                                                                                      ->eq($alias.'.integration_entity_id', ":$parameter"), $q->expr()
                                                                                                                                                                                                                                ->eq($alias.'.internal_entity', "'lead'"), $q->expr()
                                                                                                                                                                                                                                                                               ->eq($alias.'.internal_entity_id', 'l.id')));
                        break;
                }

                $groupExpr->add(sprintf('%s (%s)', $operand, $subQb->getSQL()));

                break;
            default:
                if (!$column) {
                    // Column no longer exists so continue
                    continue;
                }

                switch ($func) {
                    case 'between':
                    case 'notBetween':
                        // Filter should be saved with double || to separate options
                        $parameter2              = $this->generateRandomParameterName();
                        $parameters[$parameter]  = $leadSegmentFilter->getFilter()[0];
                        $parameters[$parameter2] = $leadSegmentFilter->getFilter()[1];
                        $exprParameter2          = ":$parameter2";
                        $ignoreAutoFilter        = true;

                        if ($func === 'between') {
                            $groupExpr->add($q->expr()->andX($q->expr()->gte($field, $exprParameter), $q->expr()
                                                                                                        ->lt($field, $exprParameter2)));
                        } else {
                            $groupExpr->add($q->expr()->andX($q->expr()->lt($field, $exprParameter), $q->expr()
                                                                                                       ->gte($field, $exprParameter2)));
                        }
                        break;

                    case 'notEmpty':
                        $groupExpr->add($q->expr()->andX($q->expr()->isNotNull($field), $q->expr()
                                                                                          ->neq($field, $q->expr()
                                                                                                          ->literal(''))));
                        $ignoreAutoFilter = true;
                        break;

                    case 'empty':
                        $leadSegmentFilter->setFilter('');
                        $groupExpr->add($this->generateFilterExpression($q, $field, 'eq', $exprParameter, true));
                        break;

                    case 'in':
                    case 'notIn':
                        $cleanFilter = [];
                        foreach ($leadSegmentFilter->getFilter() as $key => $value) {
                            $cleanFilter[] = $q->expr()->literal(InputHelper::clean($value));
                        }
                        $leadSegmentFilter->setFilter($cleanFilter);

                        if ($leadSegmentFilter->getType() === 'multiselect') {
                            foreach ($leadSegmentFilter->getFilter() as $filter) {
                                $filter = trim($filter, "'");

                                if (substr($func, 0, 3) === 'not') {
                                    $operator = 'NOT REGEXP';
                                } else {
                                    $operator = 'REGEXP';
                                }

                                $groupExpr->add($field." $operator '\\\\|?$filter\\\\|?'");
                            }
                        } else {
                            $groupExpr->add($this->generateFilterExpression($q, $field, $func, $leadSegmentFilter->getFilter(), null));
                        }
                        $ignoreAutoFilter = true;
                        break;

                    case 'neq':
                        $groupExpr->add($this->generateFilterExpression($q, $field, $func, $exprParameter, null));
                        break;

                    case 'like':
                    case 'notLike':
                    case 'startsWith':
                    case 'endsWith':
                    case 'contains':
                        $ignoreAutoFilter = true;

                        switch ($func) {
                            case 'like':
                            case 'notLike':
                                $parameters[$parameter] = (strpos($leadSegmentFilter->getFilter(), '%') === false) ? '%'.$leadSegmentFilter->getFilter().'%' : $leadSegmentFilter->getFilter();
                                break;
                            case 'startsWith':
                                $func                   = 'like';
                                $parameters[$parameter] = $leadSegmentFilter->getFilter().'%';
                                break;
                            case 'endsWith':
                                $func                   = 'like';
                                $parameters[$parameter] = '%'.$leadSegmentFilter->getFilter();
                                break;
                            case 'contains':
                                $func                   = 'like';
                                $parameters[$parameter] = '%'.$leadSegmentFilter->getFilter().'%';
                                break;
                        }

                        $groupExpr->add($this->generateFilterExpression($q, $field, $func, $exprParameter, null));
                        break;
                    case 'regexp':
                    case 'notRegexp':
                        $ignoreAutoFilter       = true;
                        $parameters[$parameter] = $this->prepareRegex($leadSegmentFilter->getFilter());
                        $not                    = ($func === 'notRegexp') ? ' NOT' : '';
                        $groupExpr->add(// Escape single quotes while accounting for those that may already be escaped
                            $field.$not.' REGEXP '.$exprParameter);
                        break;
                    default:
                        $ignoreAutoFilter = true;
                        $groupExpr->add($q->expr()->$func($field, $exprParameter));
                        $parameters[$exprParameter] = $leadSegmentFilter->getFilter();
                }
        }

        if (!$ignoreAutoFilter) {
            $parameters[$parameter] = $leadSegmentFilter->getFilter();
        }

        if ($this->dispatcher && $this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_ON_FILTERING)) {
            $event = new LeadListFilteringEvent($leadSegmentFilter->toArray(), null, $alias, $func, $q, $this->entityManager);
            $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_ON_FILTERING, $event);
            if ($event->isFilteringDone()) {
                $groupExpr->add($event->getSubQuery());
            }
        }

        // Get the last of the filters
        if ($groupExpr->count()) {
            $groups[] = $groupExpr;
        }
        if (count($groups) === 1) {
            // Only one andX expression
            $expr = $groups[0];
        } elseif (count($groups) > 1) {
            // Sets of expressions grouped by OR
            $orX = $q->expr()->orX();
            $orX->addMultiple($groups);

            // Wrap in a andX for other functions to append
            $expr = $q->expr()->andX($orX);
        } else {
            $expr = $groupExpr;
        }

        foreach ($parameters as $k => $v) {
            $paramType = null;

            if (is_array($v) && isset($v['type'], $v['value'])) {
                $paramType = $v['type'];
                $v         = $v['value'];
            }
            $q->setParameter($k, $v, $paramType);
        }

        return $expr;
    }

    /**
     * Generate a unique parameter name.
     *
     * @return string
     */
    private function generateRandomParameterName()
    {
        return $this->randomParameterName->generateRandomParameterName();
    }

    /**
     * @param QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     * @param                                         $column
     * @param                                         $operator
     * @param                                         $parameter
     * @param                                         $includeIsNull true/false or null to auto determine based on operator
     *
     * @return mixed
     */
    public function generateFilterExpression($q, $column, $operator, $parameter, $includeIsNull)
    {
        // in/notIn for dbal will use a raw array
        if (!is_array($parameter) && strpos($parameter, ':') !== 0) {
            $parameter = ":$parameter";
        }

        if (null === $includeIsNull) {
            // Auto determine based on negate operators
            $includeIsNull = (in_array($operator, ['neq', 'notLike', 'notIn']));
        }

        if ($includeIsNull) {
            $expr = $q->expr()->orX($q->expr()->$operator($column, $parameter), $q->expr()->isNull($column));
        } else {
            $expr = $q->expr()->$operator($column, $parameter);
        }

        return $expr;
    }

    /**
     * @param       $table
     * @param       $alias
     * @param       $column
     * @param       $value
     * @param array $parameters
     * @param null  $leadId
     * @param array $subQueryFilters
     *
     * @return QueryBuilder
     */
    protected function createFilterExpressionSubQuery($table, $alias, $column, $value, array &$parameters, array $subQueryFilters = [])
    {
        $subQb   = $this->entityManager->getConnection()->createQueryBuilder();
        $subExpr = $subQb->expr()->andX();

        if ('leads' !== $table) {
            $subExpr->add($subQb->expr()->eq($alias.'.lead_id', 'l.id'));
        }

        foreach ($subQueryFilters as $subColumn => $subParameter) {
            $subExpr->add($subQb->expr()->eq($subColumn, ":$subParameter"));
        }

        if (null !== $value && !empty($column)) {
            $subFilterParamter = $this->generateRandomParameterName();
            $subFunc           = 'eq';
            if (is_array($value)) {
                $subFunc = 'in';
                $subExpr->add($subQb->expr()->in(sprintf('%s.%s', $alias, $column), ":$subFilterParamter"));
                $parameters[$subFilterParamter] = ['value' => $value, 'type' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY];
            } else {
                $parameters[$subFilterParamter] = $value;
            }

            $subExpr->add($subQb->expr()->$subFunc(sprintf('%s.%s', $alias, $column), ":$subFilterParamter"));
        }

        $subQb->select('null')->from(MAUTIC_TABLE_PREFIX.$table, $alias)->where($subExpr);

        return $subQb;
    }

    /**
     * If there is a negate comparison such as not equal, empty, isNotLike or isNotIn then contacts without companies should
     * be included but the way the relationship is handled needs to be different to optimize best for a posit vs negate.
     *
     * @param QueryBuilder       $q
     * @param LeadSegmentFilters $leadSegmentFilters
     */
    private function applyCompanyFieldFilters(QueryBuilder $q, LeadSegmentFilters $leadSegmentFilters)
    {
        $joinType = $leadSegmentFilters->isListFiltersInnerJoinCompany() ? 'join' : 'leftJoin';
        // Join company tables for query optimization
        $q->$joinType('l', MAUTIC_TABLE_PREFIX.'companies_leads', 'cl', 'l.id = cl.lead_id')
          ->$joinType('cl', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'cl.company_id = comp.id');

        // Return only unique contacts
        $q->groupBy('l.id');
    }

    private function generateSegmentExpression(LeadSegmentFilters $leadSegmentFilters, QueryBuilder $q, $listId = null)
    {
        var_dump(debug_backtrace()[1]['function']);
        $expr = $this->getListFilterExpr($leadSegmentFilters, $q, $listId);

        if ($leadSegmentFilters->isHasCompanyFilter()) {
            $this->applyCompanyFieldFilters($q, $leadSegmentFilters);
        }

        return $expr;
    }

    /**
     * @return LeadSegmentFilterDescriptor
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param LeadSegmentFilterDescriptor $translator
     *
     * @return LeadSegmentQueryBuilder
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
     *
     * @return LeadSegmentQueryBuilder
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;

        return $this;
    }
}
