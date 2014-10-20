<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, Na. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class AssetRepository
 *
 * @package Mautic\AssetBundle\Entity
 */
class AssetRepository extends CommonRepository
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
            ->createQueryBuilder('a')
            ->select('a')
            ->leftJoin('a.category', 'c');

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
     * @param      $alias
     * @param null $entity
     * @return mixed
     */
    public function checkUniqueAlias($alias, $entity = null)
    {
        $q = $this->createQueryBuilder('a')
            ->select('count(a.id) as aliasCount')
            ->where('a.alias = :alias');
        $q->setParameter('alias', $alias);

        if (!empty($entity)) {
            if ($entity->getId()) {
                $q->andWhere('a.id != :id');
                $q->setParameter('id', $entity->getId());
            }
        }

        $results = $q->getQuery()->getSingleResult();
        return $results['aliasCount'];
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     */
    public function getAssetList($search = '', $limit = 10, $start = 0, $viewOther = false)
    {
        $q = $this->createQueryBuilder('a');
        $q->select('partial a.{id, title, path, alias, language}');

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('a.title', ':search'))
                ->setParameter('search', "{$search}%");
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('IDENTITY(a.createdBy)', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        $q->orderBy('a.title');

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
            $q->expr()->like('a.title',  ":$unique"),
            $q->expr()->like('a.alias',  ":$unique")
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
            case $this->translator->trans('mautic.cora.searchcommand.is'):
                switch($string) {
                    case $this->translator->trans('mautic.cora.searchcommand.ispublished'):
                        $expr = $q->expr()->eq("a.isPublished", 1);
                        break;
                    case $this->translator->trans('mautic.cora.searchcommand.isunpublished'):
                        $expr = $q->expr()->eq("a.isPublished", 0);
                        break;
                    case $this->translator->trans('mautic.cora.searchcommand.isuncategorized'):
                        $expr = $q->expr()->orX(
                            $q->expr()->isNull('a.category'),
                            $q->expr()->eq('a.category', $q->expr()->literal(''))
                        );
                        break;
                    case $this->translator->trans('mautic.cora.searchcommand.ismine'):
                        $expr = $q->expr()->eq("IDENTITY(a.createdBy)", $this->currentUser->getId());
                        break;

                }
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.cora.searchcommand.category'):
                $expr = $q->expr()->like('c.alias', ":$unique");
                $filter->strict = true;
                break;
            case $this->translator->trans('mautic.asset.asset.searchcommand.lang'):
                $langUnique       = $this->generateRandomParameterName();
                $langValue        = $filter->string . "_%";
                $forceParameters = array(
                    $langUnique => $langValue,
                    $unique     => $filter->string
                );
                $expr = $q->expr()->orX(
                    $q->expr()->eq('a.language', ":$unique"),
                    $q->expr()->like('a.language', ":$langUnique")
                );
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif (!$returnParameter) {
            $parameters = array();
        } else {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = array("$unique" => $string);
        }

        return array( $expr, $parameters );
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return array(
            'mautic.cora.searchcommand.is' => array(
                'mautic.cora.searchcommand.ispublished',
                'mautic.cora.searchcommand.isunpublished',
                'mautic.cora.searchcommand.isuncategorized',
                'mautic.cora.searchcommand.ismine',
            ),
            'mautic.cora.searchcommand.category',
            'mautic.asset.asset.searchcommand.lang'
        );
    }

    /**
     * Get a list of popular (by downloads) assets
     *
     * @param integer $limit
     * @return array
     */
    public function getPopularAssets($limit = 10)
    {
        $q = $this->createQueryBuilder('a');

        $q->select('a.id, a.title, a.downloadCount')
            ->orderBy('a.downloadCount', 'DESC')
            ->groupBy('a.id')
            ->setMaxResults($limit);

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array('a.title', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'a';
    }
}
