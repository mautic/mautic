<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\ORM\PersistentCollection;
use Mautic\CoreBundle\Doctrine\QueryFormatter\AbstractFormatter;
use Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;

/**
 * LeadListRepository
 */
class LeadListRepository extends CommonRepository
{

    /**
     * {@inheritdoc}
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function getEntity($id = 0)
    {
        try {
            $entity = $this
                ->createQueryBuilder('l')
                ->where('l.id = :listId')
                ->setParameter('listId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (\Exception $e) {
            $entity = null;
        }

        return $entity;
    }

    /**
     * Get a list of lists
     *
     * @param bool   $user
     * @param string $alias
     * @param string $id
     * @param bool   $withLeads
     * @param bool   $withFilters
     *
     * @return array
     */
    public function getLists($user = false, $alias = '', $id = '', $withLeads = false, $withFilters = false)
    {
        static $lists = array();

        if (is_object($user)) {
            $user = $user->getId();
        }

        $key = (int) $user.$alias.$id.(int) $withLeads;
        if (isset($lists[$key])) {
            return $lists[$key];
        }

        $q = $this->_em->createQueryBuilder()
            ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

        if ($withLeads || $withFilters) {
            $q->select('partial l.{id, name, alias, filters}');
        } else {
            $q->select('partial l.{id, name, alias}');
        }

        $q->andWhere($q->expr()->eq('l.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');

        if (!empty($user)) {
            $q->andWhere($q->expr()->eq('l.isGlobal', ':true'));
            $q->orWhere('l.createdBy = :user');
            $q->setParameter('user', $user);
        }

        if (!empty($alias)) {
            $q->andWhere('l.alias = :alias');
            $q->setParameter('alias', $alias);
        }

        if (!empty($id)) {
            $q->andWhere(
                $q->expr()->neq('l.id', $id)
            );
        }

        $q->orderBy('l.name');

        $results = $q->getQuery()->getArrayResult();

        if ($withLeads) {
            foreach ($results as &$i) {
                $leadLists  = $this->getLeadsByList($i, array('idOnly' => true));
                $i['leads'] = $leadLists[$i['id']];
            }
        }

        $lists[$key] = $results;

        return $results;
    }

    /**
     * Get lists for a specific lead
     *
     * @param       $lead
     * @param bool  $forList
     * @param bool  $singleArrayHydration
     *
     * @return mixed
     */
    public function getLeadLists($lead, $forList = false, $singleArrayHydration = false)
    {
        if (is_array($lead)) {
            $q = $this->_em->createQueryBuilder()
                ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

            if ($forList) {
                $q->select('partial l.{id, alias, name}, partial il.{lead, list, dateAdded, manuallyAdded, manuallyRemoved}');
            } else {
                $q->select('l, partial lead.{id}');
            }

            $q->leftJoin('l.leads', 'il')
                ->leftJoin('il.lead', 'lead');

            $q->where(
                $q->expr()->andX(
                    $q->expr()->in('lead.id', ':leads'),
                    $q->expr()->in('il.manuallyRemoved', ':false')
                )
            )
                ->setParameter('leads', $lead)
                ->setParameter('false', false, 'boolean');

            $result = $q->getQuery()->getArrayResult();

            $return = array();
            foreach ($result as $r) {
                foreach ($r['leads'] as $l) {
                    $return[$l['lead_id']][$r['id']] = $r;
                }
            }

            return $return;
        } else {
            $q = $this->_em->createQueryBuilder()
                ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

            if ($forList) {
                $q->select('partial l.{id, alias, name}, partial il.{lead, list, dateAdded, manuallyAdded, manuallyRemoved}');
            } else {
                $q->select('l');
            }

            $q->leftJoin('l.leads', 'il');

            $q->where(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(il.lead)', (int) $lead),
                    $q->expr()->in('il.manuallyRemoved', ':false')
                )
            )
                ->setParameter('false', false, 'boolean');

            return ($singleArrayHydration) ? $q->getQuery()->getArrayResult() : $q->getQuery()->getResult();
        }
    }

    /**
     * Return a list of global lists
     *
     * @param bool $withLeads
     * @param bool $withFilters
     *
     * @return array
     */
    public function getGlobalLists($withLeads = false, $withFilters = false)
    {
        $q = $this->_em->createQueryBuilder()
            ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

        if ($withLeads) {
            $q->select('partial l.{id, name, alias, filters}, partial il.{lead_id}')
                ->leftJoin('l.leads', 'il')
                ->andWhere(
                    $q->expr()->eq('il.manuallyRemoved', ':false')
                )
                ->setParameter('false', false, 'boolean');
        } elseif ($withFilters) {
            $q->select('partial l.{id, name, alias, filters}');
        } else {
            $q->select('partial l.{id, name, alias}');
        }

        $q->where($q->expr()->eq('l.isPublished', 'true'))
            ->setParameter(':true', true, 'boolean');

        $q->andWhere($q->expr()->eq('l.isGlobal', ':true'));
        $q->orderBy('l.name');

        $results = $q->getQuery()->getArrayResult();

        if ($withLeads) {
            foreach ($results as &$i) {
                $leadLists  = $this->getLeadsByList($i, array('idOnly' => true));
                $i['leads'] = $leadLists[$i['id']];
            }
        }

        return $results;
    }

    /**
     * Get a count of leads that belong to the list
     *
     * @param $listIds
     *
     * @return array
     */
    public function getLeadCount($listIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(l.lead_id) as thecount, l.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l');

        $returnArray = (is_array($listIds));

        if (!$returnArray) {
            $listIds = array($listIds);
        }

        $q->where(
            $q->expr()->in('l.leadlist_id', $listIds),
            $q->expr()->eq('l.manually_removed', ':false')
        )
            ->setParameter('false', false, 'boolean')
            ->groupBy('l.leadlist_id');

        $result = $q->execute()->fetchAll();

        $return = array();
        foreach ($result as $r) {
            $return[$r['leadlist_id']] = $r['thecount'];
        }

        // Ensure lists without leads have a value
        foreach ($listIds as $l) {
            if (!isset($return[$l])) {
                $return[$l] = 0;
            }
        }

        return ($returnArray) ? $return : $return[$listIds[0]];
    }

    /**
     * @param       $lists
     * @param array $args
     *
     * @return array
     */
    public function getLeadsByList($lists, $args = array())
    {
        $idOnly        = (!array_key_exists('idOnly', $args)) ? false : $args['idOnly'];
        $newOnly       = (!array_key_exists('newOnly', $args)) ? false : $args['newOnly'];
        $existingOnly  = (!array_key_exists('existingOnly', $args)) ? false : $args['existingOnly'];
        $dynamic       = (!array_key_exists('dynamic', $args)) ? false : $args['dynamic'];
        $batchLimiters = (!array_key_exists('batchLimiters', $args)) ? false : $args['batchLimiters'];
        $includeManual = (!array_key_exists('includeManual', $args)) ? true : $args['includeManual'];
        $countOnly     = (!array_key_exists('countOnly', $args)) ? false : $args['countOnly'];
        $filterOutIds  = (!array_key_exists('filterOutIds', $args)) ? false : $args['filterOutIds'];
        $start         = (!array_key_exists('start', $args)) ? false : $args['start'];
        $limit         = (!array_key_exists('limit', $args)) ? false : $args['limit'];

        if (!$lists instanceof PersistentCollection && !is_array($lists) || isset($lists['id'])) {
            $lists = array($lists);
        }

        $return = array();
        foreach ($lists as $l) {
            $leads = ($countOnly) ? 0 : array();

            if ($l instanceof LeadList) {
                $id      = $l->getId();
                $filters = $l->getFilters();
            } elseif (is_array($l)) {
                $id      = $l['id'];
                $filters = (!$dynamic) ? array() : $l['filters'];
            } elseif (!$dynamic) {
                $id      = $l;
                $filters = array();
            }

            if ($dynamic && $filters) {
                $q          = $this->_em->getConnection()->createQueryBuilder();
                $parameters = array();
                $expr       = $this->getListFilterExpr($filters, $parameters, $q, false, $l);

                if ($countOnly) {
                    $select = $includeManual ? 'l.id, count(distinct(l.id)) as lead_count' : 'count(distinct(l.id)) as lead_count, max(id) as max_id';
                } elseif ($idOnly) {
                    $select = 'distinct(l.id)';
                    $q->orderBy('l.id', 'ASC');
                } else {
                    $select = 'l.*';
                    $q->orderBy('l.id', 'ASC');
                }

                $q->select($select);

                $q->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                    ->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll', 'l.id = ll.lead_id');

                foreach ($parameters as $k => $v) {
                    $q->setParameter($k, $v);
                }

                if ($filterOutIds) {
                    $expr->add(
                        $q->expr()->andX(
                            $q->expr()->notIn('ll.lead_id', $filterOutIds),
                            $q->expr()->eq('ll.manually_added', ':false'),
                            $q->expr()->eq('ll.manually_removed', ':false')
                        )
                    );
                    $q->setParameter(':false', false, 'boolean');
                }

                // Set batch limiters to ensure the same group is used
                if ($batchLimiters) {
                    $expr->add(
                    // Only leads in the list at the time of count
                        $q->expr()->orX(
                            $q->expr()->isNull('ll.lead_id'),
                            $q->expr()->lte('ll.date_added', $q->expr()->literal($batchLimiters['dateTime']))
                        )
                    );

                    if (!empty($batchLimiters['maxId'])) {
                        // Only leads that existed at the time of count
                        $expr->add(
                            $q->expr()->lte('l.id', $batchLimiters['maxId'])
                        );
                    }
                }

                if ($newOnly) {
                    $dq = $this->_em->getConnection()->createQueryBuilder();
                    $dq->select('new_check.lead_id')
                        ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'new_check')
                        ->where(
                            $dq->expr()->andX(
                                $dq->expr()->eq('new_check.leadlist_id', (int) $id),
                                $dq->expr()->eq('new_check.lead_id', 'l.id')
                            )
                        );
                    $q->andWhere('l.id NOT IN '.sprintf('(%s)', $dq->getSQL()));
                } elseif ($existingOnly) {
                    $expr->add(
                        $q->expr()->andX(
                            $q->expr()->isNotNull('ll.lead_id'),
                            $q->expr()->eq('ll.leadlist_id', (int) $id),
                            $q->expr()->eq('ll.manually_added', ':false'),
                            $q->expr()->eq('ll.manually_removed', ':false')
                        )
                    );
                    $q->setParameter(':false', false, 'boolean');
                }

                // Set limits if applied
                if (!empty($limit)) {
                    $q->setFirstResult($start)
                        ->setMaxResults($limit);
                }

                $q->andWhere($expr);

                $results = $q->execute()->fetchAll();

                $dynamicLeads = array();
                foreach ($results as $r) {
                    if ($countOnly) {
                        if ($includeManual) {
                            $leads = $r['lead_count'];
                        } else {
                            $leads = array(
                                'count' => $r['lead_count'],
                                'maxId' => $r['max_id']
                            );
                        }
                    } elseif ($idOnly) {
                        $leads[] = $r['id'];
                    } else {
                        $leads[] = $r;
                    }
                    if ($includeManual) {
                        $dynamicLeads[] = $r['id'];
                    }
                }
            }

            // Get a list of manually added leads and merge them with dynamic if $includeManual
            if (!$dynamic || ($includeManual && !$limit)) {
                $q = $this->_em->getConnection()->createQueryBuilder();
                if ($countOnly) {
                    $q->select('max(ll.lead_id) as max_id, count(distinct(ll.lead_id)) as lead_count')
                        ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll');
                } elseif ($idOnly) {
                    $q->select('distinct(ll.lead_id) as id')
                        ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
                        ->orderBy('ll.lead_id', 'ASC');
                } else {
                    $q->select('l.*')
                        ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                        ->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll', 'l.id = ll.lead_id')
                        ->orderBy('ll.lead_id', 'ASC');
                }

                // Filter by list
                $expr = $q->expr()->andX(
                    $q->expr()->eq('ll.leadlist_id', ':list'),
                    $q->expr()->eq('ll.manually_removed', ':false')
                );

                $q->setParameter('list', (int) $id)
                    ->setParameter('false', false, 'boolean');

                // Set limits if applied
                if (!empty($limit)) {
                    $q->setFirstResult($start)
                        ->setMaxResults($limit);

                } elseif (!$includeManual) {
                    // Exclude manually added
                    $expr->add(
                        $q->expr()->eq('ll.manually_added', ':false')
                    );

                    // Set batch limiters to ensure the same group is used
                    if ($batchLimiters) {
                        $expr->add(
                        // Only leads in the list at the time of count
                            $q->expr()->lte('ll.date_added', $q->expr()->literal($batchLimiters['dateTime']))
                        );

                        if (!empty($batchLimiters['maxId'])) {
                            // Only leads that existed at the time of count
                            $expr->add(
                                $q->expr()->lte('ll.lead_id', $batchLimiters['maxId'])
                            );
                        }
                    }
                }

                if (!empty($dynamicLeads)) {
                    $expr->add(
                        $q->expr()->notIn('ll.lead_id', $dynamicLeads)
                    );
                }

                if ($filterOutIds) {
                    $expr->add(
                        $q->expr()->andX(
                            $q->expr()->notIn('ll.lead_id', $filterOutIds),
                            $q->expr()->eq('ll.manually_added', ':false'),
                            $q->expr()->eq('ll.manually_removed', ':false')
                        )
                    );
                    $q->setParameter(':false', false, 'boolean');
                }

                $q->where($expr);

                $results = $q->execute()->fetchAll();

                foreach ($results as $r) {
                    if ($countOnly) {
                        if ($includeManual) {
                            $leads += $r['lead_count'];
                        } else {
                            $leads = array(
                                'count' => $r['lead_count'],
                                'maxId' => $r['max_id']
                            );
                        }

                    } elseif ($idOnly) {
                        $leads[] = $r['id'];
                    } else {
                        $leads[] = $r;
                    }
                }
            }

            $return[$id] = $leads;

            unset($filters, $parameters, $q, $expr, $results, $dynamicExpr, $dynamicLeads, $leads);
        }

        return $return;
    }

    /**
     * Get manually added leads for a list
     *
     * @param $lists
     * @param $idOnly
     *
     * @return array
     */
    public function getManuallyAddedLeads($lists, $idOnly)
    {
        if (!$lists instanceof PersistentCollection && !is_array($lists) || isset($lists['id'])) {
            $lists = array($lists);
        }

        $return = array();
        foreach ($lists as $l) {

            if ($l instanceof LeadList) {
                $id = $l->getId();
            } elseif (is_array($l)) {
                $id = $l['id'];
            } else {
                $id = $l;
            }

            $q = $this->_em->getConnection()->createQueryBuilder();
            if ($idOnly) {
                $q->select('ll.lead_id as id')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll');
            } else {
                $q->select('l.*')
                    ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                    ->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll', 'l.id = ll.lead_id');
            }

            $q->where(
                $q->expr()->andX(
                    $q->expr()->eq('ll.manually_added', ':true'),
                    $q->expr()->eq('ll.leadlist_id', ':list')
                )
            )
                ->setParameter('list', $id)
                ->setParameter('true', true, 'boolean');

            $results = $q->execute()->fetchAll();

            foreach ($results as $r) {
                if ($idOnly) {
                    $return[$id][] = $r['id'];
                } else {
                    $return[$id][$r['id']] = $r;
                }
            }
        }

        return $return;
    }

    /**
     * @param                                   $filters
     * @param                                   $parameters
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @param bool|false                        $not
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression
     */
    public function getListFilterExpr($filters, &$parameters, QueryBuilder $q, $not = false)
    {
        // Get table columns
        $schema    = $this->_em->getConnection()->getSchemaManager();
        /** @var \Doctrine\DBAL\Schema\Column[] $leadTable */
        $leadTable = $schema->listTableColumns(MAUTIC_TABLE_PREFIX.'leads');

        $group   = false;
        $options = $this->getFilterExpressionFunctions();
        $expr    = $q->expr()->andX();
        $useExpr =& $expr;

        foreach ($filters as $k => $details) {
            $column = isset($leadTable[$details['field']]) ? $leadTable[$details['field']] : false;

            //DBAL does not have a not() function so we have to use the opposite
            $func  = (!$not)
                ? $options[$details['operator']]['expr']
                :
                $options[$details['operator']]['negate_expr'];
            $field = "l.{$details['field']}";

            // Format the field based on platform specific functions that DBAL doesn't support natively
            if ($column) {
                $formatter  = AbstractFormatter::createFormatter($this->_em->getConnection());
                $columnType = $column->getType();

                switch ($details['type']) {
                    case 'datetime':
                        if (!$columnType instanceof UTCDateTimeType) {
                            $field = $formatter->toDateTime($field);
                        }
                        break;
                    case 'date':
                        if (!$columnType instanceof DateType && !$columnType instanceof UTCDateTimeType) {
                            $field = $formatter->toDate($field);
                        }
                        break;
                    case 'time':
                        if (!$columnType instanceof TimeType && !$columnType instanceof UTCDateTimeType) {
                            $field = $formatter->toTime($field);
                        }
                        break;
                    case 'number':
                        if (!$columnType instanceof IntegerType && !$columnType instanceof FloatType) {
                            $field = $formatter->toNumeric($field);
                        }
                        break;
                }
            }

            //the next one will determine the group
            $glue = (isset($filters[$k + 1])) ? $filters[$k + 1]['glue'] : $details['glue'];

            if ($glue == "or" || $details['glue'] == 'or') {
                //create the group if it doesn't exist
                if ($group === false) {
                    $group = $q->expr()->orX();
                }

                //set expression var to the grouped one
                unset($useExpr);
                $useExpr =& $group;
            } else {
                if ($group !== false) {
                    //add the group
                    $expr->add($group);
                    //reset the group
                    $group = false;
                }

                //reset the expression var to be used
                unset($useExpr);
                $useExpr =& $expr;
            }

            $parameter        = $this->generateRandomParameterName();
            $exprParameter    = ":$parameter";
            $ignoreAutoFilter = false;

            // Special handling of relative date strings
            if ($details['type'] == 'datetime' || $details['type'] == 'date') {
                $relativeDateStrings = $this->getRelativeDateStrings();
                // Check if the column type is a date/time stamp
                $isTimestamp = ($columnType instanceof UTCDateTimeType || $details['type'] == 'datetime');
                $getDate     = function(&$string) use ($isTimestamp, $relativeDateStrings, &$details, &$func, $not) {
                    $key             = array_search($string, $relativeDateStrings);
                    $dtHelper        = new DateTimeHelper('midnight today', null, 'local');
                    $requiresBetween = (in_array($func, array('eq', 'neq')) && $isTimestamp);

                    $timeframe  = str_replace('mautic.lead.list.', '', $key);
                    $modifier   = false;
                    $isRelative = true;

                    switch ($timeframe) {
                        case 'today':
                        case 'tomorrow':
                        case 'yesterday':
                            if ($timeframe == 'yesterday') {
                                $dtHelper->modify('-1 day');
                            } elseif ($timeframe == 'tomorrow') {
                                $dtHelper->modify('+1 day');
                            }

                            // Today = 2015-08-28 00:00:00
                            if ($requiresBetween) {
                                // eq:
                                //  field >= 2015-08-28 00:00:00
                                //  field < 2015-08-29 00:00:00

                                // neq:
                                // field < 2015-08-28 00:00:00
                                // field >= 2015-08-29 00:00:00
                                $modifier = '+1 day';
                            } else {
                                // lt:
                                //  field < 2015-08-28 00:00:00
                                // gt:
                                //  field > 2015-08-28 23:59:59

                                // lte:
                                //  field <= 2015-08-28 23:59:59
                                // gte:
                                //  field >= 2015-08-28 00:00:00
                                if (in_array($func, array('gt', 'lte'))) {
                                    $modifier = '+1 day -1 second';
                                }
                            }
                            break;
                        case 'week_last':
                        case 'week_next':
                        case 'week_this':
                            $interval = str_replace('week_', '', $timeframe);
                            $dtHelper->setDateTime('midnight monday ' . $interval .' week', null);

                            // This week: Monday 2015-08-24 00:00:00
                            if ($requiresBetween) {
                                // eq:
                                //  field >= Mon 2015-08-24 00:00:00
                                //  field <  Mon 2015-08-31 00:00:00

                                // neq:
                                // field <  Mon 2015-08-24 00:00:00
                                // field >= Mon 2015-08-31 00:00:00
                                $modifier = '+1 week';
                            } else {
                                // lt:
                                //  field < Mon 2015-08-24 00:00:00
                                // gt:
                                //  field > Sun 2015-08-30 23:59:59

                                // lte:
                                //  field <= Sun 2015-08-30 23:59:59
                                // gte:
                                //  field >= Mon 2015-08-24 00:00:00
                                if (in_array($func, array('gt', 'lte'))) {
                                    $modifier = '+1 week -1 second';
                                }
                            }
                            break;

                        case 'month_last':
                        case 'month_next':
                        case 'month_this':
                            $interval = substr($key, -4);
                            $dtHelper->setDateTime('midnight first day of ' . $interval . ' month', null);

                            // This month: 2015-08-01 00:00:00
                            if ($requiresBetween) {
                                // eq:
                                //  field >= 2015-08-01 00:00:00
                                //  field <  2015-09:01 00:00:00

                                // neq:
                                // field <  2015-08-01 00:00:00
                                // field >= 2016-09-01 00:00:00
                                $modifier = '+1 month';
                            } else {
                                // lt:
                                //  field < 2015-08-01 00:00:00
                                // gt:
                                //  field > 2015-08-31 23:59:59

                                // lte:
                                //  field <= 2015-08-31 23:59:59
                                // gte:
                                //  field >= 2015-08-01 00:00:00
                                if (in_array($func, array('gt', 'lte'))) {
                                    $modifier = '+1 month -1 second';
                                }
                            }
                            break;
                        case 'year_last':
                        case 'year_next':
                        case 'year_this':
                            $interval = substr($key, -4);
                            $dtHelper->setDateTime('midnight first day of ' . $interval . ' year', null);

                            // This year: 2015-01-01 00:00:00
                            if ($requiresBetween) {
                                // eq:
                                //  field >= 2015-01-01 00:00:00
                                //  field <  2016-01-01 00:00:00

                                // neq:
                                // field <  2015-01-01 00:00:00
                                // field >= 2016-01-01 00:00:00
                                $modifier = '+1 year';
                            } else {
                                // lt:
                                //  field < 2015-01-01 00:00:00
                                // gt:
                                //  field > 2015-12-31 23:59:59

                                // lte:
                                //  field <= 2015-12-31 23:59:59
                                // gte:
                                //  field >= 2015-01-01 00:00:00
                                if (in_array($func, array('gt', 'lte'))) {
                                    $modifier = '+1 year -1 second';
                                }
                            }
                            break;
                        default:
                            $isRelative = false;
                            break;
                    }

                    if ($isRelative) {
                        if ($requiresBetween) {
                            $startWith = ($isTimestamp) ? $dtHelper->toUtcString('Y-m-d H:i:s') : $dtHelper->toUtcString('Y-m-d');

                            $dtHelper->modify($modifier);
                            $endWith = ($isTimestamp) ? $dtHelper->toUtcString('Y-m-d H:i:s') : $dtHelper->toUtcString('Y-m-d');

                            // Use a between statement
                            $func              = ($func == 'neq') ? 'notBetween' : 'between';
                            $details['filter'] = array($startWith, $endWith);
                        } else {
                            if ($modifier) {
                                $dtHelper->modify($modifier);
                            }

                            $details['filter'] = ($isTimestamp) ? $dtHelper->toUtcString('Y-m-d H:i:s') : $dtHelper->toUtcString('Y-m-d');
                        }
                    }
                };

                if (is_array($details['filter'])) {
                    foreach ($details['filter'] as &$filterValue) {
                        $getDate($filterValue);
                    }
                } else {
                    $getDate($details['filter']);
                }
            }

            switch ($details['field']) {
                case 'dnc_bounced':
                case 'dnc_unsubscribed':
                    // Special handling of dnc status
                    $details['filter'] = (!empty($details['filter']));
                    $subqb             = $this->_em->getConnection()->createQueryBuilder();

                    // Generate a unique alias
                    $alias  = $this->generateRandomParameterName();
                    $column = str_replace('dnc_', '', $details['field']);
                    $subqb->select($alias . '.lead_id')
                        ->from(MAUTIC_TABLE_PREFIX.'email_donotemail', $alias)
                        ->where(
                            $subqb->expr()->eq($alias . '.' . $column, $exprParameter)
                        );
                    $inFunc = (in_array($func, array('neq', 'notIn'))) ? 'NOT IN' : 'IN';
                    $useExpr->add(
                        $q->expr()->comparison('l.id', $inFunc, sprintf('(%s)', $subqb->getSQL()))
                    );
                    break;

                case 'leadlist':
                    $ignoreAutoFilter = true;

                    $func = (in_array($func, array('neq', 'notIn'))) ? 'NOT IN' : 'IN';
                    // Special handling of lead lists
                    foreach ($details['filter'] as &$value) {
                        $value = (int) $value;
                    }

                    // Generate a unique alias
                    $alias = $this->generateRandomParameterName();

                    $subqb = $this->_em->getConnection()->createQueryBuilder();
                    $subqb->select($alias . '.lead_id')
                        ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', $alias)
                        ->where(
                            $subqb->expr()->in($alias . '.leadlist_id', $details['filter'])
                        );
                    $useExpr->add(
                        $q->expr()->comparison('l.id', $func, sprintf('(%s)', $subqb->getSQL()))
                    );
                    break;
                case 'tags':
                    // Tag filter
                    $ignoreAutoFilter = true;

                    $func = (in_array($func, array('neq', 'notIn'))) ? 'NOT IN' : 'IN';
                    foreach ($details['filter'] as &$value) {
                        $value = (int) $value;
                    }

                    $alias = $this->generateRandomParameterName();
                    $sq = $this->_em->getConnection()->createQueryBuilder();
                    $sq->select($alias.'.lead_id')
                        ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', $alias)
                        ->where(
                            $sq->expr()->in($alias.'.tag_id', $details['filter'])
                        );
                    $useExpr->add(
                        $q->expr()->comparison('l.id', $func, sprintf('(%s)', $sq->getSQL()))
                    );
                    break;
                default:
                    switch ($func) {
                        case 'in':
                        case 'notIn':
                            foreach ($details['filter'] as &$value) {
                                $value = $q->expr()->literal(
                                    InputHelper::clean($value)
                                );
                            }
                            $useExpr->add(
                                $q->expr()->$func($field, $details['filter'])
                            );

                            $ignoreAutoFilter = true;

                            break;
                        case 'between':
                        case 'notBetween':
                        // Filter should be saved with double || to separate options
                        $parameter2              = $this->generateRandomParameterName();
                        $parameters[$parameter]  = $details['filter'][0];
                        $parameters[$parameter2] = $details['filter'][1];
                        $exprParameter2          = ":$parameter2";
                        $ignoreAutoFilter        = true;

                        if ($func == 'between') {
                            $useExpr->add(
                                $q->expr()->andX(
                                    $q->expr()->gte($field, $exprParameter),
                                    $q->expr()->lt($field, $exprParameter2)
                                )
                            );
                        } else {
                            $useExpr->add(
                                $q->expr()->andX(
                                    $q->expr()->lt($field, $exprParameter),
                                    $q->expr()->gte($field, $exprParameter2)
                                )
                            );
                        }

                            break;
                        case 'notEmpty':
                            $useExpr->add(
                                $q->expr()->andX(
                                    $q->expr()->isNotNull($field),
                                    $q->expr()->neq($field, $q->expr()->literal(''))
                                )
                            );

                            break;
                        case 'empty':
                            $useExpr->add(
                                $q->expr()->orX(
                                    $q->expr()->isNull($field),
                                    $q->expr()->eq($field, $q->expr()->literal(''))
                                )
                            );

                            break;
                        case 'neq':
                            $useExpr->add(
                                $q->expr()->orX(
                                    $q->expr()->isNull($field),
                                    $q->expr()->neq($field, $exprParameter)
                                )
                            );

                            break;
                        case 'like':
                        case 'notLike':
                            if (strpos($details['filter'], '%') === false) {
                                $details['filter'] = '%'.$details['filter'].'%';
                            }
                        default:
                            $useExpr->add($q->expr()->$func($field, $exprParameter));
                            break;
                    }
            }

            if (!$ignoreAutoFilter) {
                $parameters[$parameter] = $details['filter'];
            }
        }
        if ($group !== false) {
            //add the group if not added yet
            $expr->add($group);
        }

        return $expr;
    }

    /**
     * @param null $operator
     *
     * @return array
     */
    public function getFilterExpressionFunctions($operator = null)
    {
        $operatorOptions = array(
            '='          =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.equals',
                    'expr'        => 'eq',
                    'negate_expr' => 'neq'
                ),
            '!='         =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.notequals',
                    'expr'        => 'neq',
                    'negate_expr' => 'eq'
                ),
            'gt'         =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.greaterthan',
                    'expr'        => 'gt',
                    'negate_expr' => 'lt'
                ),
            'gte'        =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.greaterthanequals',
                    'expr'        => 'gte',
                    'negate_expr' => 'lt'
                ),
            'lt'         =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.lessthan',
                    'expr'        => 'lt',
                    'negate_expr' => 'gt'
                ),
            'lte'        =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.lessthanequals',
                    'expr'        => 'lte',
                    'negate_expr' => 'gt'
                ),
            'empty'      =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.isempty',
                    'expr'        => 'empty', //special case
                    'negate_expr' => 'notEmpty'
                ),
            '!empty'     =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.isnotempty',
                    'expr'        => 'notEmpty', //special case
                    'negate_expr' => 'empty'
                ),
            'like'       =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.islike',
                    'expr'        => 'like',
                    'negate_expr' => 'notLike'
                ),
            '!like'      =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.isnotlike',
                    'expr'        => 'notLike',
                    'negate_expr' => 'like'
                ),
            'between'    =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.between',
                    'expr'        => 'between', //special case
                    'negate_expr' => 'notBetween',
                    // @todo implement in list UI
                    'hide'        => true
                ),
            '!between' =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.notbetween',
                    'expr'        => 'notBetween', //special case
                    'negate_expr' => 'between',
                    // @todo implement in list UI
                    'hide'        => true
                ),
            'in'         =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.in',
                    'expr'        => 'in',
                    'negate_expr' => 'notIn'
                ),
            '!in'      =>
                array(
                    'label'       => 'mautic.lead.list.form.operator.notin',
                    'expr'        => 'notIn',
                    'negate_expr' => 'in'
                ),
        );

        return ($operator === null) ? $operatorOptions : $operatorOptions[$operator];
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX(
            $q->expr()->like('l.name', ':'.$unique),
            $q->expr()->like('l.alias', ':'.$unique)
        );
        if ($filter->not) {
            $expr = $q->expr()->not($expr);
        }

        return array(
            $expr,
            array("$unique" => $string)
        );
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
                $expr            = $q->expr()->eq("l.createdBy", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.lead.list.searchcommand.isglobal'):
                $expr            = $q->expr()->eq("l.isGlobal", ":$unique");
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                $expr            = $q->expr()->eq("l.isPublished", ":$unique");
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                $expr            = $q->expr()->eq("l.isPublished", ":$unique");
                $forceParameters = array($unique => false);
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
                $expr = $q->expr()->like('l.name', ':'.$unique);
                break;
        }

        $parameters = array();
        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = array("$unique" => $string);
        }

        return array(
            $expr,
            $parameters
        );
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return array(
            'mautic.lead.list.searchcommand.isglobal',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isinactive',
            'mautic.core.searchcommand.name'
        );
    }

    /**
     * @return array
     */
    public function getRelativeDateStrings()
    {
        $keys = self::getRelativeDateTranslationKeys();

        $strings = array();
        foreach ($keys as $key) {
            $strings[$key] = $this->translator->trans($key);
        }

        return $strings;
    }

    /**
     * @return array
     */
    public static function getRelativeDateTranslationKeys()
    {
        return array(
            'mautic.lead.list.month_last',
            'mautic.lead.list.month_next',
            'mautic.lead.list.month_this',
            'mautic.lead.list.today',
            'mautic.lead.list.tomorrow',
            'mautic.lead.list.yesterday',
            'mautic.lead.list.week_last',
            'mautic.lead.list.week_next',
            'mautic.lead.list.week_this',
            'mautic.lead.list.year_last',
            'mautic.lead.list.year_next',
            'mautic.lead.list.year_this'
        );
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array('l.name', 'ASC')
        );
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'l';
    }
}