<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
     * Get a list of entities
     *
     * @param array      $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this->createQueryBuilder($this->getTableAlias());

        $this->buildClauses($q, $args);

        $query = $q->getQuery();

        if (isset($args['hydration_mode'])) {
            $mode = strtoupper($args['hydration_mode']);
            $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::$mode"));
        }

        $results = new Paginator($query);
        return $results;
    }

    /**
     * Retrieves array of aliases used to ensure unique alias for new fields
     *
     * @param $exludingId
     * @return array
     */
    public function getAliases($exludingId)
    {
        $q = $this->createQueryBuilder('l')
            ->select('l.alias');
        if (!empty($exludingId)) {
        $q->where('l.id != :id')
            ->setParameter('id', $exludingId);
        }

        $results = $q->getQuery()->getArrayResult();
        $aliases = array();
        foreach($results as $item) {
            $aliases[] = $item['alias'];
        }

        //add lead main column names to prevent attempt to create a field with the same name
        $leadRepo = $this->_em->getRepository('MauticLeadBundle:Lead')->getBaseColumns('Mautic\\LeadBundle\\Entity\\Lead', true);
        $aliases = array_merge($aliases, $leadRepo);

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
}
