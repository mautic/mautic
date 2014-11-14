<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
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
     * @return array
     */
    public function getLeadsByFieldValue($field, $value)
    {
        $col = 'l.'.$field;
        $q = $this->_em->getConnection()->createQueryBuilder()
        ->select('l.id')
        ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
        ->where("$col = :search")
        ->setParameter("search", $value);
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
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as `group`')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->where('f.is_published = 1');
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
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as `group`')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->where('f.is_published = 1');
        $results = $fq->execute()->fetchAll();

        $fields = array();
        foreach ($results as $r) {
            $fields[$r['alias']] = $r;
        }

        //Fix arguments if necessary
        $args = $this->convertOrmProperties('Mautic\\LeadBundle\\Entity\\Lead', $args);

        //DBAL
        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('count(*) as count')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX . 'lead_lists_included_leads', 'll', 'l.id = ll.lead_id');
        $this->buildWhereClause($dq, $args);

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
        }

        //get an array of IDs for ORM query
        $ids = array_keys($fieldValues);

        if (count($ids)) {
            //ORM

            //build the order by id since the order was applied above
            //unfortunately, doctrine does not have a way to natively support this and can't use MySQL's FIELD function
            //since we have to be cross-platform; it's way ugly
            $order = '(CASE';
            foreach ($ids as $count => $id) {
                $order .= ' WHEN l.id = ' . $id . ' THEN ' . $count;
                $count++;
            }
            $order .= ' ELSE ' . $count . ' END) AS HIDDEN ORD';

            //ORM - generates lead entities
            $q = $this
                ->createQueryBuilder('l');
            $q->select('l, u, i,' . $order)
                ->leftJoin('l.ipAddresses', 'i')
                ->leftJoin('l.owner', 'u');

            //only pull the leads as filtered via DBAL
            $q->where(
                $q->expr()->in('l.id', ':leadIds')
            )->setParameter('leadIds', $ids);

            $q->orderBy('ORD', 'ASC');
            $results   = $q->getQuery()->getResult();

            //assign fields
            foreach ($results as $r) {
                if (!empty($this->triggerModel)) {
                    $r->setColor($this->triggerModel->getColorForLeadPoints($r->getPoints()));
                }

                $leadId = $r->getId();
                $r->setFields($fieldValues[$leadId]);
            }
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

        $expr = $q->expr()->orX(
            $q->expr()->like('l.firstname', ":$unique"),
            $q->expr()->like('l.lastname', ":$unique"),
            $q->expr()->like('l.email', ":$unique"),
            $q->expr()->like('l.company', ":$unique"),
            $q->expr()->like('l.city', ":$unique"),
            $q->expr()->like('l.state', ":$unique"),
            $q->expr()->like('l.zipcode', ":$unique"),
            $q->expr()->like('l.country', ":$unique")
        );

        if ($filter->not) {
            $q->expr()->not($expr);
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
            case $this->translator->trans('mautic.core.searchcommand.is'):
                switch($string) {
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
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.ismine'):
                        $expr = $q->expr()->$eqFunc("l.owner_id", $this->currentUser->getId());
                        break;
                    case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned'):
                        $expr = $q->expr()->$xFunc(
                            $q->expr()->$eqFunc("l.owner_id", 0),
                            $q->expr()->$nullFunc("l.owner_id")
                        );
                        break;
                }
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.email'):
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
            'mautic.core.searchcommand.is' => array(
                'mautic.lead.lead.searchcommand.isanonymous',
                'mautic.core.searchcommand.ismine',
                'mautic.lead.lead.searchcommand.isunowned',
            ),
            'mautic.lead.lead.searchcommand.list',
            'mautic.core.searchcommand.name',
            'mautic.lead.lead.searchcommand.company',
            'mautic.lead.lead.searchcommand.email',
            'mautic.lead.lead.searchcommand.owner'
        );
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
       return array(
           array('l.date_added', 'ASC')
       );
    }
}
