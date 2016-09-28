<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Query;

/**
 * LeadFieldRepository
 */
class LeadFieldRepository extends CommonRepository
{
    /**
     * Retrieves array of aliases used to ensure unique alias for new fields
     *
     * @param $exludingId
     * @param $publishedOnly
     * @param $includeEntityFields
     *
     * @return array
     */
    public function getAliases($exludingId, $publishedOnly = false, $includeEntityFields = true)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.alias')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'l');

        if (!empty($exludingId)) {
            $q->where('l.id != :id')
                ->setParameter('id', $exludingId);
        }

        if ($publishedOnly) {
            $q->andWhere(
                $q->expr()->eq('is_published', ':true')
            )
                ->setParameter(':true', true, 'boolean');
        }

        $results = $q->execute()->fetchAll();
        $aliases = array();
        foreach($results as $item) {
            $aliases[] = $item['alias'];
        }

        if ($includeEntityFields) {
            //add lead main column names to prevent attempt to create a field with the same name
            $leadRepo = $this->_em->getRepository('MauticLeadBundle:Lead')->getBaseColumns('Mautic\\LeadBundle\\Entity\\Lead', true);
            $aliases  = array_merge($aliases, $leadRepo);
        }

        return $aliases;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'f';
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
            $q->expr()->like('f.label',  ':'.$unique),
            $q->expr()->like('f.alias', ':'.$unique)
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
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array('f.order', 'ASC')
        );
    }

    /**
     * Get field aliases for lead table columns
     */
    public function getFieldAliases()
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        return $qb->select('f.alias, f.is_unique_identifer as is_unique, f.type')
                ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                ->orderBy('f.field_order', 'ASC')
                ->execute()->fetchAll();
    }

    /**
     * Compare a form result value with defined value for defined lead.
     *
     * @param  integer $lead ID
     * @param  integer $field alias
     * @param  string  $value to compare with
     * @param  string  $operatorExpr for WHERE clause
     *
     * @return boolean
     */
    public function compareValue($lead, $field, $value, $operatorExpr)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');

        if ($field === "tags") {
            // Special reserved tags field
            $q->join('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x', 'l.id = x.lead_id')
                ->join('x', MAUTIC_TABLE_PREFIX.'lead_tags', 't', 'x.tag_id = t.id') 
                ->where(
                    $q->expr()->andX(
                        $q->expr()->eq('l.id', ':lead'),
                        $q->expr()->eq('t.tag', ':value')
                    )
                )
                ->setParameter('lead', (int) $lead)
                ->setParameter('value', $value);

            $result = $q->execute()->fetch();

            if (($operatorExpr === "eq") || ($operatorExpr === "like")) {
                return !empty($result['id']);
            } elseif (($operatorExpr === "neq") || ($operatorExpr === "notLike")) {
                return empty($result['id']);
            } else {
                return false;
            }            
        } else {
            // Standard field
            $q->where(
                $q->expr()->andX(
                    $q->expr()->eq('l.id', ':lead'),
                    $q->expr()->$operatorExpr('l.' . $field, ':value')
                )
            )
            ->setParameter('lead', (int) $lead)
            ->setParameter('value', $value);

            $result = $q->execute()->fetch();

            return !empty($result['id']);
        }
    }

}
