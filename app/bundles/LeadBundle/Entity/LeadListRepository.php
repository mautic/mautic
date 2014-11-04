<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * LeadListRepository
 */
class LeadListRepository extends CommonRepository
{

    /**
     * {@inheritdoc}
     *
     * @param int $id
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
     * {@inheritdoc}
     *
     * @param array      $args
     * @param Translator $translator
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('l');

        $this->buildClauses($q, $args);

        $query = $q->getQuery();
        $result = new Paginator($query);

        if (!empty($args['includeLeadIds'])) {
            $return = array(
                'total' => count($result)
            );

            $iterator = $result->getIterator();
            foreach ($iterator as &$i) {
                $leadLists = $this->getLeadsByList($i, true);
                $i->setLeads($leadLists[$i['id']]);
            }

            $return['lists'] = $iterator;
            return $return;
        }

        return $result;
    }

    /**
     * Get a list of lists
     *
     * @param bool $user
     * @param string $alias
     * @param string $id
     * @param bool $withLeads
     * @param bool $withFilters

     * @return array
     */
    public function getLists($user = false, $alias = '', $id = '', $withLeads = false, $withFilters = false)
    {
        static $lists = array();

        if (is_object($user)) {
            $user = $user->getId();
        }

        $key = (int) $user . $alias . $id. (int) $withLeads;
        if (isset($lists[$key])) {
            return $lists[$key];
        }

        $q = $this->_em->createQueryBuilder()
            ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

        if ($withLeads) {
            $q->select('partial l.{id, name, alias, filters}, partial il.{id}, partial el.{id}')
                ->leftJoin('l.includedLeads', 'il')
                ->leftJoin('l.excludedLeads', 'el');
        } elseif ($withFilters) {
            $q->select('partial l.{id, name, alias, filters}');
        } else {
            $q->select('partial l.{id, name, alias}');
        }

        $q->where($q->expr()->eq('l.isPublished', true));

        if (!empty($user)) {
            $q->andWhere($q->expr()->eq('l.isGlobal', true));

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
                $leadLists = $this->getLeadsByList($i, true);
                $i['leads'] = $leadLists[$i['id']];
            }
        }

        $lists[$key] = $results;
        return $results;
    }

    /**
     * Get lists for a specific lead
     *
     * @param      $lead
     * @param bool $forList
     *
     * @return mixed
     */
    public function getLeadLists($lead, $forList = false)
    {
        static $return = array();

        if (is_array($lead)) {
            $q = $this->_em->createQueryBuilder()
                ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

            if ($forList) {
                $q->select('partial l.{id, alias, name}, partial il.{id}');
            } else {
                $q->select('l, partial il.{id}');
            }

            $q->leftJoin('l.includedLeads', 'il');

            $q->where(
                $q->expr()->in('il.id', ':leads')
            )->setParameter('leads', $lead);

            $result = $q->getQuery()->getArrayResult();

            $return = array();
            foreach ($result as $r) {
                foreach($r['includedLeads'] as $l) {
                    $return[$l['id']][$r['id']] = $r;
                }
            }

            return $return;
        } else {
            if (empty($return[$lead])) {
                $q = $this->_em->createQueryBuilder()
                    ->from('MauticLeadBundle:LeadList', 'l', 'l.id');

                if ($forList) {
                    $q->select('partial l.{id, alias, name}');
                } else {
                    $q->select('l');
                }

                $q->leftJoin('l.includedLeads', 'il');

                $q->where(
                    $q->expr()->eq('il.id', (int)$lead)
                );

                $return[$lead] = $q->getQuery()->getResult();
            }

            return $return[$lead];
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
            $q->select('partial l.{id, name, alias, filters}, partial il.{id}, partial el.{id}')
                ->leftJoin('l.includedLeads', 'il')
                ->leftJoin('l.excludedLeads', 'el');
        } elseif ($withFilters) {
            $q->select('partial l.{id, name, alias, filters}');
        } else {
            $q->select('partial l.{id, name, alias}');
        }

        $q->where($q->expr()->eq('l.isPublished', true));

        $q->andWhere($q->expr()->eq('l.isGlobal', true));
        $q->orderBy('l.name');

        $results = $q->getQuery()->getArrayResult();

        if ($withLeads) {
            foreach ($results as &$i) {
                $leadLists = $this->getLeadsByList($i, true);
                $i['leads'] = $leadLists[$i['id']];
            }
        }

        return $results;
    }

    /**
     * Get a count of leads that belong to the list
     *
     * @param array $filters
     */
    public function getLeadCount($filters, $list = null)
    {
        $q          = $this->_em->getConnection()->createQueryBuilder();
        $parameters = array();
        $expr       = $this->getListFilterExpr($filters, $parameters, $q, false, $list);
        $q->select('count(*) as recipientCount')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where($expr);
        foreach ($parameters as $k => $v) {
            $q->setParameter($k, $v);
        }
        $result = $q->execute()->fetchAll();

        return (!empty($result[0])) ? $result[0]['recipientCount'] : 0;
    }

    /**
     * @param      $lists
     * @param bool $idOnly
     * @param bool $dynamic
     *
     * @return array
     */
    public function getLeadsByList($lists, $idOnly = false, $dynamic = false)
    {
        static $leads = array(), $currentOnlyLeads = array();

        if (!$lists instanceof PersistentCollection && !is_array($lists) || isset($lists['id'])) {
            $lists = array($lists);
        }

        $return = array();
        foreach ($lists as $l) {

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

            if (!$dynamic) {
                if (!isset($currentOnlyLeads[$id])) {
                    $q = $this->_em->getConnection()->createQueryBuilder();
                    if ($idOnly) {
                        $q->select('ll.lead_id')
                            ->from(MAUTIC_TABLE_PREFIX . 'lead_lists_included_leads', 'll');
                    } else {
                        $q->select('l.*')
                            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
                            ->leftJoin('l', MAUTIC_TABLE_PREFIX . 'lead_lists_included_leads', 'll', 'l.id = ll.lead_id');
                    }

                    $q ->where($q->expr()->eq('ll.leadlist_id', ':list'))
                        ->setParameter('list', $id);
                    $results    = $q->execute()->fetchAll();
                    $currentOnlyLeads[$id] = array();
                    foreach ($results as $r) {
                        if ($idOnly) {
                            $currentOnlyLeads[$id][] = $r['lead_id'];
                        } else {
                            $currentOnlyLeads[$id][$r['id']] = $r;
                        }
                    }
                    unset($filters, $parameters, $q, $expr);
                }
                $return[$id] = $currentOnlyLeads[$id];

            } else {
                if (!isset($leads[$id])) {
                    $q          = $this->_em->getConnection()->createQueryBuilder();
                    $parameters = array();
                    $expr       = $this->getListFilterExpr($filters, $parameters, $q, false, $l);

                    $select = ($idOnly) ? 'l.id' : 'l.*';
                    $q->select($select)
                        ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
                        ->where($expr);
                    foreach ($parameters as $k => $v) {
                        $q->setParameter($k, $v);
                    }

                    $results    = $q->execute()->fetchAll();
                    $leads[$id] = array();
                    foreach ($results as $r) {
                        if ($idOnly) {
                            $leads[$id][] = $r['id'];
                        } else {
                            $leads[$id][$r['id']] = $r;
                        }

                    }
                    unset($filters, $parameters, $q, $expr);
                }
                $return[$id] = $leads[$id];
            }
        }
        return $return;
    }

    /**
     * @param      $filters
     * @param      $parameters
     * @param      $q
     * @param bool $not
     *
     * @return mixed
     */
    public function getListFilterExpr($filters, &$parameters, &$q, $not = false, $list = null)
    {
        $group       = false;
        $options     = $this->getFilterExpressionFunctions();
        $expr        = $q->expr()->orX();
        $useExpr     =& $expr;

        foreach ($filters as $k => $details) {
            $uniqueFilter              = $this->generateRandomParameterName();
            $parameters[$uniqueFilter] = $details['filter'];

            $uniqueFilter              = ":$uniqueFilter";
            //DQL does not have a not() function so we have to use the opposite
            $func                      = (!$not) ? $options[$details['operator']]['func'] :
                $options[$details['operator']]['oFunc'];
            $field                     = "l.{$details['field']}";

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
            if ($func == 'notEmpty') {
                $useExpr->add(
                    $q->expr()->andX(
                        $q->expr()->isNotNull($field, $uniqueFilter),
                        $q->expr()->neq($field, $q->expr()->literal(''))
                    )
                );
            } elseif ($func == 'empty') {
                $useExpr->add(
                    $q->expr()->orX(
                        $q->expr()->isNull($field, $uniqueFilter),
                        $q->expr()->eq($field, $q->expr()->literal(''))
                    )
                );
            } else {
                $useExpr->add($q->expr()->$func($field, $uniqueFilter));
            }
        }
        if ($group !== false) {
            //add the group if not added yet
            $expr->add($group);
        }

        /*
        //add manually added leads
        if ($list !== null) {
            if ($list instanceof LeadList) {
                $includedLeads = $list->getIncludedLeads();
                $excludedLeads = $list->getExcludedLeads();
            } else {
                $includedLeads = $list['includedLeads'];
                $excludedLeads = $list['excludedLeads'];
            }

            $includeIds  = array();
            foreach ($includedLeads as $lead) {
                $includeIds[] = ($lead instanceof Lead) ? $lead->getId() : $lead['id'];
            }

            $excludeIds    = array();
            foreach ($excludedLeads as $lead) {
                $excludeIds[] = ($lead instanceof Lead) ? $lead->getId() : $lead['id'];
            }

            $manualExpr = $q->expr()->andX();
            if (!empty($includeIds)) {
                $manualExpr->add(
                    $q->expr()->in('l.id', $includeIds)
                );
            }

            if (!empty($excludeIds)) {
                $manualExpr->add(
                    $q->expr()->notIn('l.id', $excludeIds)
                );
            }

            if ($manualExpr->count()) {
                $returnExpr = $q->expr()->orX();
                $returnExpr->add($expr);
                $returnExpr->add($manualExpr);
                return $returnExpr;
            }
        }
        */

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
            '='      =>
                array(
                    'label' => 'mautic.lead.list.form.operator.equals',
                    'func'  => 'eq',
                    'oFunc' => 'neq'
                ),
            '!='     =>
                array(
                    'label' => 'mautic.lead.list.form.operator.notequals',
                    'func'  => 'neq',
                    'oFunc' => 'eq'
                ),
            '&#62;'   =>
                array(
                    'label' => 'mautic.lead.list.form.operator.greaterthan',
                    'func'  => 'gt',
                    'oFunc' => 'lt'
                ),
            '&#62;='   =>
                array(
                    'label' => 'mautic.lead.list.form.operator.greaterthanequals',
                    'func'  => 'gte',
                    'oFunc' => 'lt'
                ),
            '&#60;'    =>
                array(
                    'label' => 'mautic.lead.list.form.operator.lessthan',
                    'func'  => 'lt',
                    'oFunc' => 'gt'
                ),
            '&#60;='   =>
                array(
                    'label' => 'mautic.lead.list.form.operator.lessthanequals',
                    'func'  => 'lte',
                    'oFunc' => 'gt'
                ),
            'empty'  =>
                array(
                    'label' => 'mautic.lead.list.form.operator.isempty',
                    'func'  => 'empty', //special case
                    'oFunc' => 'notEmpty'
                ),
            '!empty' =>
                array(
                    'label' => 'mautic.lead.list.form.operator.isnotempty',
                    'func'  => 'notEmpty', //special case
                    'oFunc' => 'empty'
                ),
            'like'   =>
                array(
                    'label' => 'mautic.lead.list.form.operator.islike',
                    'func'  => 'like',
                    'oFunc' => 'notLike'
                ),
            '!like'  =>
                array(
                    'label' => 'mautic.lead.list.form.operator.isnotlike',
                    'func'  => 'notLike',
                    'oFunc' => 'like'
                )
        );

        return ($operator === null) ? $operatorOptions : $operatorOptions[$operator];
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX(
            $q->expr()->like('l.name',  ':'.$unique),
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
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        $command         = $field = $filter->command;
        $string          = $filter->string;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.is'):
                switch($string) {
                    case $this->translator->trans('mautic.core.searchcommand.ismine'):
                        $expr = $q->expr()->eq("l.createdBy", $this->currentUser->getId());
                        break;
                    case $this->translator->trans('mautic.lead.list.searchcommand.isglobal'):
                        $expr = $q->expr()->eq("l.isGlobal", 1);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                        $expr = $q->expr()->eq("l.isPublished", 1);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                        $expr = $q->expr()->eq("l.isPublished", 0);
                        break;
                }
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
                $expr = $q->expr()->like('l.name', ':'.$unique);
                break;
        }

        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";
        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }
        return array(
            $expr,
            ($returnParameter) ? array("$unique" => $string) : array()
        );
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return array(
            'mautic.core.searchcommand.is' => array(
                'mautic.lead.list.searchcommand.isglobal',
                'mautic.core.searchcommand.ismine',
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isinactive'
            ),
            'mautic.core.searchcommand.name'
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

    public function getTableAlias()
    {
        return 'l';
    }
}
