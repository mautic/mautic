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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\PointBundle\Model\TriggerModel;

/**
 * LeadRepository
 */
class LeadRepository extends CommonRepository
{
    /**
     * Required to get the color based on a lead's points
     * @var
     */
    private $triggerModel;

    public function setTriggerModel (TriggerModel $triggerModel)
    {
        $this->triggerModel = $triggerModel;
    }

    /**
     * Gets a list of unique values from fields for autocompletes
     * @param        $field
     * @param string $search
     * @param int    $limit
     * @param int    $start
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
        }

        return $results;
    }

    /**
     * Get a list of lead entities
     *
     * @param $fields
     * @param $values
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

        return $results;
    }

    /*
     * Get list of lead Ids by unique field data.
     *
     * @param $uniqueFieldsWithData is an array of columns & values to filter by
     * @param $leadId is the current lead id. Added to query to skip and find other leads.
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
     * @param $email
     */
    public function getLeadByEmail($email)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where("LOWER(email) = :search")
            ->setParameter('search', strtolower($email));

        $result = $q->execute()->fetchAll();

        if (count($result)) {
            return $result[0];
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

        return $results;
    }

    /**
     * Get leads count per country name.
     * Can't use entity, because country is custom field.
     *
     * @return array
     */
    public function getLeadsCountPerCountries()
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('COUNT(l.id) as quantity, l.country')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->groupBy('l.country')
            ->where($q->expr()->isNotNull('l.country'));
        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     * @param $flush
     * @return int
     */
    public function saveEntity($entity, $flush = true)
    {
        $this->_em->persist($entity);

        if ($flush)
            $this->_em->flush();

        $fields = $entity->getUpdatedFields();
        if (!empty($fields)) {
            $this->_em->getConnection()->update(MAUTIC_TABLE_PREFIX . 'leads', $fields, array('id' => $entity->getId()));
        }
    }


    /**
     * Persist an array of entities
     *
     * @param array $entities
     *
     * @return void
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
     * @param $id
     *
     * @return array
     */
    public function getFieldValues($id, $byGroup = true)
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

        //use DBAL to get entity fields
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('*')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where('l.id = :leadId')
            ->setParameter('leadId', $id);
        $leadValues = $q->execute()->fetchAll();
        $this->removeNonFieldColumns($leadValues[0]);

        $fieldValues = array();

        //loop over results to put fields in something that can be assigned to the entities
        foreach ($leadValues[0] as $k => $r) {
            if (isset($fields[$k])) {
                if ($byGroup) {
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
     * @param int $id
     * @return mixed|null
     */
    public function getEntity($id = 0)
    {
        try {
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
        }

        return $entity;
    }

    /**
     * Get a list of leads
     *
     * @param array      $args
     * @param Translator $translator
     * @return Paginator
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
        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('count(l.id) as count')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX . 'lead_lists_leads', 'll', 'l.id = ll.lead_id');
        $this->buildWhereClause($dq, $args);
        $dq->andWhere(
            $dq->expr()->orX(
                $dq->expr()->isNull('ll.manually_removed'),
                $dq->expr()->eq('ll.manually_removed', ':false')
            )
        )
            ->setParameter('false', false, 'boolean');

        //get a total count
        $result = $dq->execute()->fetchAll();
        $total  = $result[0]['count'];

        //now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select');
        $dq->select('*');
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
     * Function to remove non custom field columns from an arrayed lead row
     *
     * @param $r
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
     * @param QueryBuilder $q
     * @param              $filter
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
        $command         = $filter->command;
        $string          = $filter->string;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        $parameters      = array();

        //DBAL QueryBuilder does not have an expr()->not() function; boo!!
        if ($filter->not) {
            $xFunc = "orX";
            $xSubFunc = "andX";
            $eqFunc   = "neq";
            $nullFunc = "isNotNull";
            $likeFunc = "notLike";
        } else {
            $xFunc = "andX";
            $xSubFunc = "orX";
            $eqFunc   = "eq";
            $nullFunc = "isNull";
            $likeFunc = "like";
        }

        switch ($command) {
            case $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous'):
                $expr = $q->expr()->$xFunc(
                    $q->expr()->$xSubFunc(
                        $q->expr()->$eqFunc("l.firstname", $q->expr()->literal('')),
                        $q->expr()->$nullFunc("l.firstname")
                    ),
                    $q->expr()->$xSubFunc(
                        $q->expr()->$eqFunc("l.lastname", $q->expr()->literal('')),
                        $q->expr()->$nullFunc("l.lastname")
                    ),
                    $q->expr()->$xSubFunc(
                        $q->expr()->$eqFunc("l.company", $q->expr()->literal('')),
                        $q->expr()->$nullFunc("l.company")
                    ),
                    $q->expr()->$xSubFunc(
                        $q->expr()->$eqFunc("l.email", $q->expr()->literal('')),
                        $q->expr()->$nullFunc("l.email")
                    )
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
                $expr = $q->expr()->$eqFunc("l.owner_id", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned'):
                $expr = $q->expr()->$xFunc(
                    $q->expr()->$eqFunc("l.owner_id", 0),
                    $q->expr()->$nullFunc("l.owner_id")
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.email'):
                $expr = $q->expr()->$likeFunc('l.email', ":$unique");
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.company'):
                $expr = $q->expr()->$likeFunc('l.company', ":$unique");
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.owner'):
                $expr = $q->expr()->$xFunc(
                    $q->expr()->$likeFunc('u.firstName', ':'.$unique),
                    $q->expr()->$likeFunc('u.lastName', ':'.$unique)
                );
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
                $expr = $q->expr()->$xFunc(
                    $q->expr()->$likeFunc('l.firstname', ":$unique"),
                    $q->expr()->$likeFunc('l.lastname', ":$unique")
                );
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.list'):
                //obtain the list details
                $list = $this->_em->getRepository("MauticLeadBundle:LeadList")->findOneByAlias($string);
                if (!empty($list)) {
                    $expr = $q->expr()->eq('ll.leadlist_id', (int) $list->getId());
                } else {
                    //force a bad expression as the list doesn't exist
                    $expr = $q->expr()->eq('ll.leadlist_id', 0);
                }
                break;
        }

        $string = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        if ($command != $this->translator->trans('mautic.lead.lead.searchcommand.list')) {
            $parameters[$unique] = $string;
        }

        return array(
            $expr,
            ($returnParameter) ? $parameters : array()
        );

    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return array(
            'mautic.lead.lead.searchcommand.isanonymous',
            'mautic.core.searchcommand.ismine',
            'mautic.lead.lead.searchcommand.isunowned',
            'mautic.lead.lead.searchcommand.list',
            'mautic.core.searchcommand.name',
            'mautic.lead.lead.searchcommand.company',
            'mautic.core.searchcommand.email',
            'mautic.lead.lead.searchcommand.owner'
        );
    }

    /**
     * @return string
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
     * @param $leadId
     */
    public function updateLastActive($leadId)
    {
        $dt     = new DateTimeHelper();
        $fields = array('last_active' => $dt->toUtcString());

        $this->_em->getConnection()->update(MAUTIC_TABLE_PREFIX . 'leads', $fields, array('id' => $leadId));
    }

    /**
     * Grabs a random lead
     */
    public function getRandomLead()
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('*')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->where(
                $q->expr()->andX(
                    $q->expr()->neq('l.firstname', $q->expr()->literal('')),
                    $q->expr()->neq('l.lastname', $q->expr()->literal('')),
                    $q->expr()->neq('l.email', $q->expr()->literal(''))
                )
            )
            ->orderBy('l.last_active');
        $q->setMaxResults(1);

        $result = $q->execute()->fetchAll();

        return (count($result)) ? $result[0] : null;
    }
}