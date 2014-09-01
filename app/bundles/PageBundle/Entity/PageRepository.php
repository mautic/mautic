<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class PageRepository
 *
 * @package Mautic\PageBundle\Entity
 */
class PageRepository extends CommonRepository
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
            ->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.category', 'c');

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
        $q = $this->createQueryBuilder('e')
            ->select('count(e.id) as aliasCount')
            ->where('e.alias = :alias');
        $q->setParameter('alias', $alias);

        if (!empty($entity)) {
            $parent   = $entity->getTranslationParent();
            $children = $entity->getTranslationChildren();
            if ($parent || count($children)) {
                //allow same alias among language group
                $ids = array();

                if (!empty($parent)) {
                    $children = $parent->getTranslationChildren();
                    $ids[] = $parent->getId();
                }

                foreach ($children as $child) {
                    if ($child->getId() != $entity->getId()) {
                        $ids[] = $child->getId();
                    }
                }
                $q->andWhere($q->expr()->notIn('e.id', $ids));
            }
            $parent   = $entity->getVariantParent();
            $children = $entity->getVariantChildren();
            if ($parent || count($children)) {
                //allow same alias among language group
                $ids = array();

                if (!empty($parent)) {
                    $children = $parent->getVariantChildren();
                    $ids[] = $parent->getId();
                }

                foreach ($children as $child) {
                    if ($child->getId() != $entity->getId()) {
                        $ids[] = $child->getId();
                    }
                }
                $q->andWhere($q->expr()->notIn('e.id', $ids));
            }
            if ($entity->getId()) {
                $q->andWhere('e.id != :id');
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
    public function getPageList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevel = false)
    {
        $q = $this->createQueryBuilder('p');
        $q->select('partial p.{id, title, language, alias}');

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('p.title', ':search'))
                ->setParameter('search', "{$search}%");
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('IDENTITY(p.createdBy)', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ($topLevel == 'translation') {
            //only get top level pages
            $q->andWhere($q->expr()->isNull('p.translationParent'));
        } elseif ($topLevel == 'variant') {
            $q->andWhere($q->expr()->isNull('p.variantParent'));
        }

        $q->orderBy('p.title');

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
            $q->expr()->like('p.title',  ":$unique"),
            $q->expr()->like('p.alias',  ":$unique")
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
                    case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                        $expr = $q->expr()->eq("p.isPublished", 1);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                        $expr = $q->expr()->eq("p.isPublished", 0);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
                        $expr = $q->expr()->orX(
                            $q->expr()->isNull('p.category'),
                            $q->expr()->eq('p.category', $q->expr()->literal(''))
                        );
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.ismine'):
                        $expr = $q->expr()->eq("IDENTITY(p.createdBy)", $this->currentUser->getId());
                        break;

                }
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
                $expr = $q->expr()->like('c.alias', ":$unique");
                $filter->strict = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.lang'):
                $langUnique       = $this->generateRandomParameterName();
                $langValue        = $filter->string . "_%";
                $forceParameters = array(
                    $langUnique => $langValue,
                    $unique     => $filter->string
                );
                $expr = $q->expr()->orX(
                    $q->expr()->eq('p.language', ":$unique"),
                    $q->expr()->like('p.language', ":$langUnique")
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
            'mautic.core.searchcommand.is' => array(
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isunpublished',
                'mautic.core.searchcommand.isuncategorized',
                'mautic.core.searchcommand.ismine',
            ),
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.lang'
        );
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array('p.title', 'ASC')
        );
    }

    public function getTableAlias()
    {
        return 'p';
    }
}
