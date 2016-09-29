<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\PointBundle\Model\TriggerModel;

/**
 * LeadRepository
 */
class LeadRepository extends CommonRepository
{
    /**
     * @var array
     */
    private $availableSocialFields = array();

    /**
     * @var array
     */
    private $availableSearchFields = array();

    /**
     * Required to get the color based on a lead's points
     *
     * @var TriggerModel
     */
    private $triggerModel;

    /**
     * Used by search functions to search social profiles
     *
     * @param array $fields
     */
    public function setAvailableSocialFields(array $fields)
    {
        $this->availableSocialFields = $fields;
    }

    /**
     * Used by search functions to search using aliases as commands
     *
     * @param array $fields
     */
    public function setAvailableSearchFields(array $fields)
    {
        $this->availableSearchFields = $fields;
    }

    /**
     * Sets trigger model
     *
     * @param TriggerModel $triggerModel
     */
    public function setTriggerModel(TriggerModel $triggerModel)
    {
        $this->triggerModel = $triggerModel;
    }

    /**
     * Gets a list of unique values from fields for autocompletes
     *
     * @param        $field
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getValueList($field, $search = '', $limit = 10, $start = 0)
    {
        $col = 'l.'.$field;
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select("DISTINCT $col")
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');

        $q->where(
            $q->expr()->andX(
                $q->expr()->neq($col, $q->expr()->literal('')),
                $q->expr()->isNotNull($col)
            )
        );

        if (!empty($search)) {
            $q->andWhere("$col LIKE :search")
                ->setParameter('search', "{$search}%");
        }

        $q->orderBy($col);

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of leads based on field value
     *
     * @param $field
     * @param $value
     * @param $ignoreId
     *
     * @return array
     */
    public function getLeadsByFieldValue($field, $value, $ignoreId = null)
    {
        $col = 'l.'.$field;

        if ($field == 'email') {
            // Prevent emails from being case sensitive
            $col   = "LOWER($col)";
            $value = strtolower($value);
        }

        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where("$col = :search")
            ->setParameter("search", $value);

        if ($ignoreId) {
            $q->andWhere('l.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        $results = $q->execute()->fetchAll();

        if (count($results)) {
            $ids = array();
            foreach ($results as $r) {
                $ids[] = $r['id'];
            }

            $q = $this->_em->createQueryBuilder()
                ->select('l')
                ->from('MauticLeadBundle:Lead', 'l');
            $q->where(
                $q->expr()->in('l.id', ':ids')
            )
                ->setParameter('ids', $ids)
                ->orderBy('l.dateAdded', 'DESC');
            $results = $q->getQuery()->getResult();

            /** @var Lead $lead */
            foreach ($results as $lead) {
                $lead->setAvailableSocialFields($this->availableSocialFields);
            }
        }

        return $results;
    }

    /**
     * Get a list of lead entities
     *
     * @param      $uniqueFieldsWithData
     * @param null $leadId
     *
     * @return array
     */
    public function getLeadsByUniqueFields($uniqueFieldsWithData, $leadId = null)
    {
        // get the list of IDs
        $idList = $this->getLeadIdsByUniqueFields($uniqueFieldsWithData, $leadId);

        // init to empty array
        $results = array();

        // if we didn't get anything return empty
        if (!count(($idList))) {
            return $results;
        }

        $ids = array();

        // we know we have at least one
        foreach ($idList as $r) {
            $ids[] = $r['id'];
        }

        $q = $this->_em->createQueryBuilder()
            ->select('l')
            ->from('MauticLeadBundle:Lead', 'l');

        $q->where(
            $q->expr()->in('l.id', ':ids')
        )
            ->setParameter('ids', $ids)
            ->orderBy('l.dateAdded', 'DESC');

        $results = $q->getQuery()->getResult();

        /** @var Lead $lead */
        foreach ($results as $lead) {
            $lead->setAvailableSocialFields($this->availableSocialFields);
            if (!empty($this->triggerModel)) {
                $lead->setColor($this->triggerModel->getColorForLeadPoints($lead->getPoints()));
            }

            $fieldValues = $this->getFieldValues($lead->getId());
            $lead->setFields($fieldValues);
        }

        return $results;
    }

    /**
     * Get list of lead Ids by unique field data.
     *
     * @param $uniqueFieldsWithData is an array of columns & values to filter by
     * @param int $leadId is the current lead id. Added to query to skip and find other leads.
     *
     * @return array
     */
    public function getLeadIdsByUniqueFields($uniqueFieldsWithData, $leadId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');

        // loop through the fields and
        foreach($uniqueFieldsWithData as $col => $val) {
            $q->orWhere("l.$col = :" . $col)
                ->setParameter($col, $val);
        }

        // if we have a lead ID lets use it
        if (!empty($leadId)) {
            // make sure that its not the id we already have
            $q->andWhere("l.id != " . $leadId);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param string $email
     * @param boolean $all Set to true to return all matching lead id's
     *
     * @return array|null
     */
    public function getLeadByEmail($email, $all = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where("LOWER(email) = :search")
            ->setParameter('search', strtolower($email));

        $result = $q->execute()->fetchAll();

        if (count($result)) {
            return $all ? $result : $result[0];
        } else {
            return null;
        }
    }

    /**
     * Get leads by IP address
     *
     * @param      $ip
     * @param bool $byId
     *
     * @return array
     */
    public function getLeadsByIp($ip, $byId = false)
    {
        $q = $this->createQueryBuilder('l')
            ->leftJoin('l.ipAddresses', 'i');
        $col = ($byId) ? 'i.id' : 'i.ipAddress';
        $q->where($col . ' = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('l.dateAdded', 'DESC');
        $results = $q->getQuery()->getResult();

        /** @var Lead $lead */
        foreach ($results as $lead) {
            $lead->setAvailableSocialFields($this->availableSocialFields);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     * @param $flush
     */
    public function saveEntity($entity, $flush = true)
    {
        $this->_em->persist($entity);

        if ($flush)
            $this->_em->flush($entity);

        $fields = $entity->getUpdatedFields();
        if (!empty($fields)) {
            $this->_em->getConnection()->update(MAUTIC_TABLE_PREFIX . 'leads', $fields, array('id' => $entity->getId()));
        }
    }


    /**
     * Persist an array of entities
     *
     * @param array $entities
     */
    public function saveEntities($entities)
    {
        foreach ($entities as $k => $entity) {
            // Leads cannot be batched due to requiring the ID to update the fields
            $this->saveEntity($entity);
        }
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getLead($id)
    {
        $fq = $this->_em->getConnection()->createQueryBuilder();
        $fq->select('l.*')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where('l.id = ' . $id);
        $results = $fq->execute()->fetchAll();
        return (isset($results[0])) ? $results[0] : array();
    }

    /**
     * Get a list of fields and values
     *
     * @param           $id
     * @param bool|true $byGroup
     *
     * @return array
     */
    public function getFieldValues($id, $byGroup = true)
    {
        //Get the list of custom fields
        $fq = $this->_em->getConnection()->createQueryBuilder();
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as "group", f.field_order')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->where('f.is_published = :published')
            ->orderBy('f.field_order', 'asc')
            ->setParameter('published', true, 'boolean');
        $results = $fq->execute()->fetchAll();

        $fields = array();
        foreach ($results as $r) {
            $fields[$r['alias']] = $r;
        }

        //use DBAL to get entity fields
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('*')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where('l.id = :leadId')
            ->setParameter('leadId', $id);
        $leadValues = $q->execute()->fetch();
        $this->removeNonFieldColumns($leadValues);

        // Reorder leadValues based on field order
        $leadValues = array_merge(array_flip(array_keys($fields)), $leadValues);

        $fieldValues = array();
        //loop over results to put fields in something that can be assigned to the entities
        foreach ($leadValues as $k => $r) {
            if (isset($fields[$k])) {
                if ($byGroup) {
                    if ($fields[$k]['type'] === 'multiselect') {
                        $r = explode("|", $r);
                    }
                    $fieldValues[$fields[$k]['group']][$fields[$k]['alias']]          = $fields[$k];
                    $fieldValues[$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                } else {
                    $fieldValues[$fields[$k]['alias']]          = $fields[$k];
                    $fieldValues[$fields[$k]['alias']]['value'] = $r;
                }
            }
        }

        if ($byGroup) {
            //make sure each group key is present
            $groups = array('core', 'social', 'personal', 'professional');
            foreach ($groups as $g) {
                if (!isset($fieldValues[$g])) {
                    $fieldValues[$g] = array();
                }
            }
        }
        return $fieldValues;
    }

    /**
     * {@inheritdoc}
     *
     * @param integer $id
     *
     * @return mixed|null
     */
    public function getEntity($id = 0)
    {
        try {
            /** @var Lead $entity */
            $entity = $this
                ->createQueryBuilder('l')
                ->select('l, u, i')
                ->leftJoin('l.ipAddresses', 'i')
                ->leftJoin('l.owner', 'u')
                ->where('l.id = :leadId')
                ->setParameter('leadId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (\Exception $e) {
            $entity = null;
        }

        if ($entity != null) {
            if (!empty($this->triggerModel)) {
                $entity->setColor($this->triggerModel->getColorForLeadPoints($entity->getPoints()));
            }

            $fieldValues = $this->getFieldValues($id);
            $entity->setFields($fieldValues);

            $entity->setAvailableSocialFields($this->availableSocialFields);
        }

        return $entity;
    }

    /**
     * Get a list of leads
     *
     * @param array $args
     *
     * @return array
     */
    public function getEntities($args = array())
    {
        //Get the list of custom fields
        $fq = $this->_em->getConnection()->createQueryBuilder();
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as "group"')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->where('f.is_published = :published')
            ->setParameter('published', true, 'boolean');
        $results = $fq->execute()->fetchAll();

        $fields = array();
        foreach ($results as $r) {
            $fields[$r['alias']] = $r;
        }

        unset($results);

        //Fix arguments if necessary
        $args = $this->convertOrmProperties('Mautic\\LeadBundle\\Entity\\Lead', $args);

        //DBAL
        $dq = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $dq->select('COUNT(l.id) as count')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = l.owner_id');

        // Filter by an entity query
        if (isset($args['entity_query'])) {
            $dq->andWhere(
                sprintf('EXISTS (%s)', $args['entity_query']->getSQL())
            );

            if (isset($args['entity_parameters'])) {
                foreach ($args['entity_parameters'] as $name => $value) {
                    $dq->setParameter($name, $value);
                }
            }
        }

        $this->buildWhereClause($dq, $args);

        //get a total count
        $result = $dq->execute()->fetchAll();
        $total  = $result[0]['count'];

        //now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select')
            ->select('l.*');
        $results = $dq->execute()->fetchAll();

        //loop over results to put fields in something that can be assigned to the entities
        $fieldValues = array();
        $groups      = array('core', 'social', 'personal', 'professional');

        foreach ($results as $result) {
            $leadId = $result['id'];
            //unset all the columns that are not fields
            $this->removeNonFieldColumns($result);

            foreach ($result as $k => $r) {
                if (isset($fields[$k])) {
                    $fieldValues[$leadId][$fields[$k]['group']][$fields[$k]['alias']] = $fields[$k];
                    $fieldValues[$leadId][$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                }
            }

            //make sure each group key is present
            foreach ($groups as $g) {
                if (!isset($fieldValues[$leadId][$g])) {
                    $fieldValues[$leadId][$g] = array();
                }
            }
        }

        unset($results, $fields);

        //get an array of IDs for ORM query
        $ids = array_keys($fieldValues);

        if (count($ids)) {
            //ORM

            //build the order by id since the order was applied above
            //unfortunately, doctrine does not have a way to natively support this and can't use MySQL's FIELD function
            //since we have to be cross-platform; it's way ugly

            //We should probably totally ditch orm for leads
            $order = '(CASE';
            foreach ($ids as $count => $id) {
                $order .= ' WHEN l.id = ' . $id . ' THEN ' . $count;
                $count++;
            }
            $order .= ' ELSE ' . $count . ' END) AS HIDDEN ORD';

            //ORM - generates lead entities
            $q = $this->_em->createQueryBuilder();
            $q->select('l, u, i,' . $order)
                ->from('MauticLeadBundle:Lead', 'l', 'l.id')
                ->leftJoin('l.ipAddresses', 'i')
                ->leftJoin('l.owner', 'u');

            //only pull the leads as filtered via DBAL
            $q->where(
                $q->expr()->in('l.id', ':leadIds')
            )->setParameter('leadIds', $ids);

            $q->orderBy('ORD', 'ASC');

            $results = $q->getQuery()
                ->useQueryCache(false)
                ->useResultCache(false)
                ->getResult();

            //assign fields
            foreach ($results as $r) {
                if (!empty($this->triggerModel)) {
                    $r->setColor($this->triggerModel->getColorForLeadPoints($r->getPoints()));
                }

                $leadId = $r->getId();
                $r->setFields($fieldValues[$leadId]);
                $r->setAvailableSocialFields($this->availableSocialFields);
            }
        } else {
            $results = array();
        }

        return (!empty($args['withTotalCount'])) ?
            array(
                'count' => $total,
                'results' => $results
            ) : $results;
    }

    /**
     * Get contats for a specific channel entity
     *
     * @param $args - same as getEntity/getEntities
     * @param        $joinTable
     * @param        $entityId
     * @param string $contactColumnName
     *
     * @return array
     */
    public function getEntityContacts($args, $joinTable, $entityId, $filters = [], $contactColumnName = 'id')
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.$joinTable, 'entity')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.id', 'entity.lead_id'),
                    $qb->expr()->eq("entity.{$contactColumnName}", (int) $entityId)
                )
            );

        $parameters = [];
        if ($filters) {
            foreach ($filters as $column => $value) {
                $parameterName = $this->generateRandomParameterName();
                $qb->andWhere(
                    $qb->expr()->eq("entity.{$column}", ":{$parameterName}")
                );
                $parameters[$parameterName] = $value;
            }
        }

        $args['entity_query']      = $qb;
        $args['entity_parameters'] = $parameters;

        return $this->getEntities($args);
    }

    /**
     * Function to remove non custom field columns from an arrayed lead row
     *
     * @param array $r
     */
    protected function removeNonFieldColumns(&$r)
    {
        $baseCols = $this->getBaseColumns('Mautic\\LeadBundle\\Entity\\Lead', true);
        foreach ($baseCols as $c) {
            unset($r[$c]);
        }
        unset($r['owner_id']);
    }

    /**
     * Adds the "catch all" where clause to the QueryBuilder
     * @param QueryBuilder $q
     * @param              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        if ($filter->not) {
            $xFunc    = 'andX';
            $exprFunc = 'notLike';
        } else {
            $xFunc    = 'orX';
            $exprFunc = 'like';
        }

        $expr = $q->expr()->$xFunc(
            $q->expr()->$exprFunc('l.firstname', ":$unique"),
            $q->expr()->$exprFunc('l.lastname', ":$unique"),
            $q->expr()->$exprFunc('l.email', ":$unique"),
            $q->expr()->$exprFunc('l.company', ":$unique"),
            $q->expr()->$exprFunc('l.city', ":$unique"),
            $q->expr()->$exprFunc('l.state', ":$unique"),
            $q->expr()->$exprFunc('l.zipcode', ":$unique"),
            $q->expr()->$exprFunc('l.country', ":$unique")
        );

        if (!empty($this->availableSocialFields)) {
            foreach ($this->availableSocialFields as $field) {
                $expr->add(
                    $q->expr()->$exprFunc("l.$field", ":$unique")
                );
            }
        }

        return array(
            $expr,
            array("$unique" => $string)
        );
    }

    /**
     * Adds the command where clause to the QueryBuilder
     *
     * @param QueryBuilder $q
     * @param              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        $command         = $filter->command;
        $string          = $filter->string;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        $parameters      = array();

        //DBAL QueryBuilder does not have an expr()->not() function; boo!!
        if ($filter->not) {
            $xFunc = "orX";
            $existsFunc = "NOT EXISTS";
            $eqFunc   = "neq";
            $nullFunc = "isNotNull";
            $likeFunc = "notLike";
        } else {
            $xFunc = "andX";
            $existsFunc = "EXISTS";
            $eqFunc   = "eq";
            $nullFunc = "isNull";
            $likeFunc = "like";
        }

        switch ($command) {
            case $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous'):
                $expr = $q->expr()->$xFunc(
                    $q->expr()->$nullFunc('l.date_identified')
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
                $expr = $q->expr()->$eqFunc("l.owner_id", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned'):
                $expr = $q->expr()->orX(
                    $q->expr()->$eqFunc("l.owner_id", 0),
                    $q->expr()->$nullFunc("l.owner_id")
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.owner'):
                $expr = $q->expr()->orX(
                    $q->expr()->$likeFunc('LOWER(u.first_name)', ':'.$unique),
                    $q->expr()->$likeFunc('LOWER(u.last_name)', ':'.$unique)
                );
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
                $expr = $q->expr()->orX(
                    $q->expr()->$likeFunc('LOWER(l.firstname)', ":$unique"),
                    $q->expr()->$likeFunc('LOWER(l.lastname)', ":$unique")
                );
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.list'):
                //obtain the list details
                $list = $this->_em->getRepository("MauticLeadBundle:LeadList")->findOneByAlias($string);

                if (!empty($list)) {
                    $listId = (int) $list->getId();
                } else {
                    //force a bad expression as the list doesn't exist
                    $listId = 0;
                }

                $sq = $this->_em->getConnection()->createQueryBuilder()
                    ->select('null')
                    ->from(MAUTIC_TABLE_PREFIX . 'lead_lists_leads', 'll');

                $sq->where(
                    $sq->expr()->andX(
                        $sq->expr()->eq('l.id', 'll.lead_id'),
                        $sq->expr()->eq('ll.leadlist_id', $listId),
                        $sq->expr()->orX(
                            $sq->expr()->isNull('ll.manually_removed'),
                            $sq->expr()->eq('ll.manually_removed', ":$unique")
                        )
                    )
                );

                $filter->string = false;
                $filter->strict = false;

                $expr = $q->expr()->andX(sprintf('%s (%s)', $existsFunc, $sq->getSQL()));

                break;
            case $this->translator->trans('mautic.core.searchcommand.ip'):
                // search by IP
                $sq = $this->_em->getConnection()->createQueryBuilder();
                $sq->select('null')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_ips_xref', 'lip')
                    ->join('lip', MAUTIC_TABLE_PREFIX.'ip_addresses', 'ip', 'lip.ip_id = ip.id')
                    ->where(
                        $sq->expr()->andX(
                            $sq->expr()->eq('l.id', 'lip.lead_id'),
                            $sq->expr()->$likeFunc('ip.ip_address', ":$unique")
                        )
                    );

                $expr = $q->expr()->andX(sprintf('%s (%s)', $existsFunc, $sq->getSQL()));

                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.duplicate'):
                $prateek = explode("+", $string);

                $imploder = array();

                foreach ($prateek as $key => $value) {
                    $list = $this->_em->getRepository("MauticLeadBundle:LeadList")->findOneByAlias($value);
                    $imploder[] = ( (!empty($list)) ? (int) $list->getId() : 0 );
                }

                //logic. In query, Sum(manuall_removed should be less than the current)
                $pluck = sizeof($imploder) - 1;

                $imploder = (string)(implode(",", $imploder));

                $sq = $this->_em->getConnection()->createQueryBuilder();
                $sq -> select('lll.lead_id')
                    -> from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
                    -> where("lll.leadlist_id in ( ".$imploder." )")
                    //-> where("lll.leadlist_id in ( :imploder )")
                    -> groupBy('lll.lead_id')
                    -> having('COUNT(lll.lead_id) > :counting AND SUM(lll.manually_removed) < :counting')
                    -> setParameter('counting', $pluck)
                    //-> setParameter('imploder', $imploder)
                ;

                $results = $sq->execute()->fetchAll();

                $leadIds = array();
                foreach ($results as $row) {
                    $leadIds[] = $row['lead_id'];
                }
                if (!sizeof($leadIds)) $leadIds[0] = 0;
                $expr = $q->expr()->in('l.id', $leadIds);

                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.tag'):
                // search by tag
                $sq = $this->_em->getConnection()->createQueryBuilder();
                $sq->select('null')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x')
                    ->join('x', MAUTIC_TABLE_PREFIX.'lead_tags', 't', 'x.tag_id = t.id')
                    ->where(
                        $sq->expr()->andX(
                            $sq->expr()->eq('l.id', 'x.lead_id'),
                            $sq->expr()->$likeFunc('t.tag', ":$unique")
                        )
                    );

                $expr = $q->expr()->andX(sprintf('%s (%s)', $existsFunc, $sq->getSQL()));

                break;
            case $this->translator->trans('mautic.core.searchcommand.email'):
                $expr = $q->expr()->$likeFunc('LOWER(l.email)', ":$unique");
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.company'):
                $expr = $q->expr()->$likeFunc('LOWER(l.company)', ":$unique");
                break;
            default:
                if (in_array($command, $this->availableSearchFields)) {
                    $expr = $q->expr()->$likeFunc('LOWER(l.'.$command.')', ":$unique");
                }
                break;
        }

        $string = ($filter->strict) ? $filter->string : "%{$filter->string}%";
        $parameters[$unique] = $string;

        return array(
            $expr,
            ($returnParameter) ? $parameters : array()
        );

    }

    /**
     * Returns the array of search commands.
     *
     * @return array
     */
    public function getSearchCommands()
    {
        $commands = array(
            'mautic.lead.lead.searchcommand.isanonymous',
            'mautic.core.searchcommand.ismine',
            'mautic.lead.lead.searchcommand.isunowned',
            'mautic.lead.lead.searchcommand.list',
            'mautic.core.searchcommand.name',
            'mautic.lead.lead.searchcommand.company',
            'mautic.core.searchcommand.email',
            'mautic.lead.lead.searchcommand.owner',
            'mautic.core.searchcommand.ip',
            'mautic.lead.lead.searchcommand.tag',
            'mautic.lead.lead.searchcommand.stage',
            'mautic.lead.lead.searchcommand.duplicate'
        );

        if (!empty($this->availableSearchFields)) {
            $commands = array_merge($commands, $this->availableSearchFields);
        }

        return $commands;
    }

    /**
     * Returns the array of columns with the default order
     *
     * @return array
     */
    protected function getDefaultOrder()
    {
        return array(
            array('l.last_active', 'DESC')
        );
    }

    /**
     * Updates lead's lastActive with now date/time
     *
     * @param integer $leadId
     */
    public function updateLastActive($leadId)
    {
        $dt     = new DateTimeHelper();
        $fields = array('last_active' => $dt->toUtcString());

        $this->_em->getConnection()->update(MAUTIC_TABLE_PREFIX . 'leads', $fields, array('id' => $leadId));
    }

    /**
     * Gets the ID of the latest ID
     *
     * @return integer
     */
    public function getMaxLeadId()
    {
        $result = $this->_em->getConnection()->createQueryBuilder()
            ->select('max(id) as max_lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->execute()->fetchAll();

        return $result[0]['max_lead_id'];
    }

    /**
     * Gets names, signature and email of the user(lead owner)
     *
     * @param  integer $ownerId
     *
     * @return array|false
     */
    public function getLeadOwner($ownerId)
    {
        if (!$ownerId) return false;

        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('u.id, u.first_name, u.last_name, u.email, u.signature')
            ->from(MAUTIC_TABLE_PREFIX . 'users', 'u')
            ->where('u.id = :ownerId')
            ->setParameter('ownerId', (int) $ownerId);

        $result = $q->execute()->fetch();

        // Fix the HTML markup
        if (is_array($result)) {
            foreach ($result as &$field) {
                $field = html_entity_decode($field);
            }
        }

        return $result;
    }
}
