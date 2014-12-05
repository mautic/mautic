<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class EmailRepository
 *
 * @package Mautic\EmailBundle\Entity
 */
class EmailRepository extends CommonRepository
{

    /**
     * Get an array of do not email emails
     *
     * @return array
     */
    public function getDoNotEmailList()
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('partial e.{id, emailAddress}')
            ->from('MauticEmailBundle:DoNotEmail', 'e', 'e.emailAddress');
        $results = $q->getQuery()->getArrayResult();

        return array_keys($results);
    }

    /**
     * Check to see if an email is set as do not contact
     *
     * @param $email
     *
     * @return bool
     */
    public function checkDoNotEmail($email)
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('partial e.{id}')
            ->from('MauticEmailBundle:DoNotEmail', 'e')
            ->where('e.emailAddress = :email')
            ->setParameter('email', $email);
        $results = $q->getQuery()->getArrayResult();

        return (!empty($results)) ? true : false;
    }

    /**
     * Remove email from DNE list
     *
     * @param $email
     */
    public function removeFromDoNotEmailList($email)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('MauticEmailBundle:DoNotEmail', 'd')
            ->andWhere($qb->expr()->eq('d.emailAddress', ':email'))
            ->setParameter(':email', $email);

        $qb->getQuery()->execute();
    }

    /**
     * Get a list of entities
     *
     * @param array      $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select('e')
            ->from('MauticEmailBundle:Email', 'e', 'e.id')
            ->leftJoin('e.category', 'c')
            ->leftJoin('e.lists', 'l');

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
     * Get amounts of sent and read emails
     *
     * @return array
     */
    public function getSentReadCount()
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('SUM(e.sentCount) as sentCount, SUM(e.readCount) as readCount')
            ->from('MauticEmailBundle:Email', 'e');
        $results = $q->getQuery()->getSingleResult();

        if (!isset($results['sentCount'])) {
            $results['sentCount'] = 0;
        }
        if (!isset($results['readCount'])) {
            $results['readCount'] = 0;
        }

        return $results;
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     * @param bool   $topLevelOnly
     *
     * @return array
     */
    public function getEmailList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevelOnly = false)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, subject, language}');

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('e.subject', ':search'))
                ->setParameter('search', "{$search}%");
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('IDENTITY(e.createdBy)', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ($topLevelOnly) {
            $q->andWhere($q->expr()->isNull('e.variantParent'));
        }

        $q->orderBy('e.subject');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
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

        $expr = $q->expr()->like('e.subject',  ":$unique");
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
        $command         = $filter->command;
        $string          = $filter->string;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.is'):
                switch($string) {
                    case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                        $expr = $q->expr()->eq("e.isPublished", 1);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                        $expr = $q->expr()->eq("e.isPublished", 0);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
                        $expr = $q->expr()->orX(
                            $q->expr()->isNull('e.category'),
                            $q->expr()->eq('e.category', $q->expr()->literal(''))
                        );
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.ismine'):
                        $expr = $q->expr()->eq("IDENTITY(e.createdBy)", $this->currentUser->getId());
                        break;

                }
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
                $expr = $q->expr()->like('e.alias', ":$unique");
                $filter->strict = true;
                break;
            case $this->translator->trans('mautic.email.searchcommand.lang'):
                $langUnique       = $this->generateRandomParameterName();
                $langValue        = $filter->string . "_%";
                $forceParameters = array(
                    $langUnique => $langValue,
                    $unique     => $filter->string
                );
                $expr = $q->expr()->orX(
                    $q->expr()->eq('e.language', ":$unique"),
                    $q->expr()->like('e.language', ":$langUnique")
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
            array('e.subject', 'ASC')
        );
    }

    public function getTableAlias()
    {
        return 'e';
    }
}
