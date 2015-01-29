<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CategoryRepository
 *
 * @package Mautic\CategoryBundle\Entity
 */
class CategoryRepository extends CommonRepository
{

    /**
     * Get a list of entities
     *
     * @param array      $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('c')
            ->select('c');

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
     * @paran string $bundle
     * @param string $search
     * @param int    $limit
     * @param int    $start
     */
    public function getCategoryList($bundle, $search = '', $limit = 10, $start = 0)
    {
        $q = $this->createQueryBuilder('c');
        $q->select('partial c.{id, title, alias, color}');

        $q->where('c.isPublished = true');
        $q->andWhere('c.bundle = :bundle')
            ->setParameter('bundle', $bundle);

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('c.title', ':search'))
                ->setParameter('search', "{$search}%");
        }

        $q->orderBy('c.title');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->getQuery()->getArrayResult();
        return $results;
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
            $q->expr()->like('c.title',  ':'.$unique),
            $q->expr()->like('c.description',  ':'.$unique)
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
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                $expr = $q->expr()->eq("c.isPublished", 1);
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                $expr = $q->expr()->eq("c.isPublished", 0);
                $returnParameter = false;
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
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished'
        );
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array('c.title', 'ASC')
        );
    }

    /**
     * @param string $bundle
     * @param string $alias
     * @param object $entity
     *
     * @return mixed
     */
    public function checkUniqueCategoryAlias($bundle, $alias, $entity = null)
    {
        $q = $this->createQueryBuilder('e')
            ->select('count(e.id) as aliasCount')
            ->where('e.alias = :alias')
            ->andWhere('e.bundle = :bundle')
            ->setParameter('alias', $alias)
            ->setParameter('bundle', $bundle);

        if (!empty($entity) && $entity->getId()) {
            $q->andWhere('e.id != :id');
            $q->setParameter('id', $entity->getId());
        }

        $results = $q->getQuery()->getSingleResult();

        return $results['aliasCount'];
    }

}
