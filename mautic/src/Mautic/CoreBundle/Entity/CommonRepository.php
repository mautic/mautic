<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\SearchStringHelper;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * Class CommonRepository
 *
 * @package Mautic\CoreBundle\Entity
 */
class CommonRepository extends EntityRepository
{

    /**
     * @var Translator
     */
    public $translator;

    public function setTranslator($translator)
    {
        if (!$translator instanceof Translator && !$translator instanceof IdentityTranslator) {
            throw new FatalErrorException();
        }
        $this->translator = $translator;
    }

    /**
     * Save an entity through the repository
     *
     * @param $entity
     * @return int
     */
    public function saveEntity($entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
        return $entity;
    }

    /**
     * Delete an entity through the repository
     *
     * @param $entity
     * @return int
     */
    public function deleteEntity($entity)
    {
        //delete entity
        $this->_em->remove($entity);
        $this->_em->flush();
        return $entity;
    }

    protected function buildClauses(QueryBuilder &$q, array $args)
    {
        $this->buildWhereClause($q, $args);
        $this->buildOrderByClause($q, $args);
        $this->buildLimiterClauses($q, $args);
    }

    protected function buildWhereClause(QueryBuilder &$q, array $args)
    {

        $filter = array_key_exists('filter', $args) ? $args['filter'] : '';
        if (!empty($filter)) {

            //remove wildcards passed by user
            if (strpos($filter, '%') !== false) {
                $filter = str_replace('%', '', $filter);
            }

            //parse filter
            $filterHelper   = new SearchStringHelper();
            $filter         = $filterHelper->parseSearchString($filter);

            list($expressions, $parameters) = $this->addAdvancedSearchWhereClause($q, $filter);
            $q->where($expressions)
                ->setParameters($parameters);
        }
    }

    protected function addCatchAllWhereClause(QueryBuilder &$qb, $filter)
    {
        return array(
            false,
            array()
        );
    }

    protected function addAdvancedSearchWhereClause(QueryBuilder &$qb, $filters)
    {
        if (isset($filters->root)) {
            //function is determined by the second clause type
            $type         = (isset($filters->root[1])) ? $filters->root[1]->type : $filters->root[0]->type;
            $parseFilters =& $filters->root;
        } elseif (isset($filters->children)) {
            $type         = (isset($filters->children[1])) ? $filters->children[1]->type : $filters->children[0]->type;
            $parseFilters =& $filters->children;
        } else {
            $type         = (isset($filters[1])) ? $filters[1]->type : $filters[0]->type;
            $parseFilters =& $filters;
        }

        $parameters  = array();
        $expressions = $qb->expr()->{"{$type}X"}();
        foreach ($parseFilters as $f) {
            if (isset($f->children)) {
                list($expr, $params) = $this->addAdvancedSearchWhereClause($qb, $f);
            } else {
                if (!empty($f->command) && $this->isSupportedSearchCommand($f->command)) {
                    list($expr, $params) = $this->addSearchCommandWhereClause($qb, $f);
                } else {
                    list($expr, $params) = $this->addCatchAllWhereClause($qb, $f);
                }
            }
            $parameters = array_merge($parameters, $params);
            $expressions->add($expr);
        }
        return array($expressions, $parameters);
    }

    protected function getSupportedCommands()
    {
        return array();
    }

    protected function buildOrderByClause(QueryBuilder &$q, array $args)
    {
        $orderBy    = array_key_exists('orderBy', $args) ? $args['orderBy'] : $this->getDefaultOrderBy();
        $orderByDir = array_key_exists('orderByDir', $args) ? $args['orderByDir'] : "ASC";

        if (!empty($orderBy)) {
            $q->orderBy($orderBy, $orderByDir);
        }
    }

    protected function getDefaultOrderBy()
    {
        return '';
    }

    protected function buildLimiterClauses(QueryBuilder &$q, array $args)
    {
        $start      = array_key_exists('start', $args) ? $args['start'] : 0;
        $limit      = array_key_exists('limit', $args) ? $args['limit'] : 30;

        $q->setFirstResult($start)
            ->setMaxResults($limit);
    }

    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($alpha_numeric), 0, 8);
    }
}
