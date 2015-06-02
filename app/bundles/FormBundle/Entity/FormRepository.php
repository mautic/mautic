<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * FormRepository
 */
class FormRepository extends CommonRepository
{
    /**
     * {@inheritdoc}
     */
    public function getEntities($args = array())
    {
        //use a subquery to get a count of submissions otherwise doctrine will not pull all of the results
        $sq = $this->_em->createQueryBuilder()
            ->select('count(fs.id)')
            ->from('MauticFormBundle:Submission', 'fs')
            ->where('fs.form = f');

        $q = $this->createQueryBuilder('f');
        $q->select('f, ('.$sq->getDql().') as submission_count');
        $q->leftJoin('f.category', 'c');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     * @param null   $formType
     *
     * @return array
     */
    public function getFormList($search = '', $limit = 10, $start = 0, $viewOther = false, $formType = null)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('partial f.{id, name, alias}');

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('f.name', ':search'))
                ->setParameter('search', "{$search}%");
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('IDENTITY(f.createdBy)', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if (!empty($formType)) {
            $q->andWhere(
                $q->expr()->eq('f.formType', ':type')
            )->setParameter('type', $formType);
        }

        $q->orderBy('f.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX(
            $q->expr()->like('f.name',  ':'.$unique),
            $q->expr()->like('f.description',  ':'.$unique)
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
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                $expr = $q->expr()->eq("f.isPublished", ":$unique");
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                $expr = $q->expr()->eq("f.isPublished", ":$unique");
                $forceParameters = array($unique => false);
                break;
            case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
                $expr = $q->expr()->orX(
                    $q->expr()->isNull('f.category'),
                    $q->expr()->eq('f.category', $q->expr()->literal(''))
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
                $expr = $q->expr()->eq("f.createdBy", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.form.form.searchcommand.isexpired'):
                $expr = $q->expr()->andX(
                    $q->expr()->eq('f.isPublished', ":$unique"),
                    $q->expr()->isNotNull('f.publishDown'),
                    $q->expr()->neq('f.publishDown', $q->expr()->literal('')),
                    $q->expr()->lt('f.publishDown', 'CURRENT_TIMESTAMP()')
                );
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.form.form.searchcommand.ispending'):
                $expr = $q->expr()->andX(
                    $q->expr()->eq('f.isPublished', ":$unique"),
                    $q->expr()->isNotNull('f.publishUp'),
                    $q->expr()->neq('f.publishUp', $q->expr()->literal('')),
                    $q->expr()->gt('f.publishUp', 'CURRENT_TIMESTAMP()')
                );
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.form.form.searchcommand.hasresults'):
                $sq = $this->getEntityManager()->createQueryBuilder();
                $subquery = $sq->select("count(s.id)")
                    ->from('MauticFormBundle:Submission', 's')
                    ->leftJoin('MauticFormBundle:Form', 'f2',
                        Join::WITH,
                        $sq->expr()->eq('s.form', "f2")
                    )
                    ->where(
                        $q->expr()->eq('s.form', 'f')
                    )
                    ->getDql();
                $expr = $q->expr()->gt(sprintf("(%s)",$subquery), 1);
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
                $expr = $q->expr()->like('c.alias', ":$unique");
                $filter->strict = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
                $q->expr()->like('f.name', ':'.$unique);
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        $parameters = array();
        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = array("$unique" => $string);
        }

        return array(
            $expr,
            $parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return array(
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.form.form.searchcommand.isexpired',
            'mautic.form.form.searchcommand.ispending',
            'mautic.form.form.searchcommand.hasresults',
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.name',
        );

    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return array(
            array('f.name', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'f';
    }
}
