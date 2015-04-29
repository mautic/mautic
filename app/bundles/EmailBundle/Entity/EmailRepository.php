<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Query;
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
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('lower(e.address) as email')
            ->from(MAUTIC_TABLE_PREFIX.'email_donotemail', 'e');
        $results = $q->execute()->fetchAll();

        $dnc = array();
        foreach ($results as $r) {
            $dnc[] = $r['email'];
        }

        return $dnc;
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
        $q->select('SUM(e.sentCount) as sent_count, SUM(e.readCount) as read_count')
            ->from('MauticEmailBundle:Email', 'e');
        $results = $q->getQuery()->getSingleResult(Query::HYDRATE_ARRAY);

        if (!isset($results['sent_count'])) {
            $results['sent_count'] = 0;
        }
        if (!isset($results['read_count'])) {
            $results['read_count'] = 0;
        }

        return $results;
    }

    /**
     * @param $emailId
     */
    public function getEmailPendingLeads($emailId, $listIds = null, $countOnly = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('dne.lead_id')->from(MAUTIC_TABLE_PREFIX.'email_donotemail', 'dne');

        $sq2 = $this->_em->getConnection()->createQueryBuilder();
        $sq2->select('stat.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'stat')
            ->where('stat.email_id = el.email_id');

        if ($countOnly) {
            $q->select('count(l.id) as count');
        } else {
            $q->select('l.*');
        }
        $q->from(MAUTIC_TABLE_PREFIX . 'leads', 'l')
            ->join('l', MAUTIC_TABLE_PREFIX . 'lead_lists_leads', 'll', 'l.id = ll.lead_id')
            ->join('ll', MAUTIC_TABLE_PREFIX . 'email_list_xref', 'el', 'el.leadlist_id = ll.leadlist_id');

        $q->where('el.email_id = ' . (int) $emailId);
        $q->andWhere('l.id NOT IN ' . sprintf("(%s)",$sq->getSQL()));
        $q->andWhere('l.id NOT IN ' . sprintf("(%s)",$sq2->getSQL()));

        if ($listIds != null) {
            if (!is_array($listIds)) {
                $listIds = array($listIds);
            }
            $q->andWhere(
                $q->expr()->in('ll.leadlist_id', $listIds)
            );
        }

        $results = $q->execute()->fetchAll();

        if ($countOnly) {

            return (isset($results[0])) ? $results[0]['count'] : 0;
        } else {
            $leads = array();
            foreach ($results as $r) {
                $leads[$r['id']] = $r;
            }

            return $leads;
        }
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
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                $expr = $q->expr()->eq("e.isPublished", ":$unique");
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                $expr = $q->expr()->eq("e.isPublished", ":$unique");
                $forceParameters = array($unique => true);
                break;
            case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
                $expr = $q->expr()->orX(
                    $q->expr()->isNull('e.category'),
                    $q->expr()->eq('e.category', $q->expr()->literal(''))
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
                $expr = $q->expr()->eq("IDENTITY(e.createdBy)", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
                $expr = $q->expr()->like('e.alias', ":$unique");
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
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
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

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * Null variant parent
     *
     * @param $ids
     */
    public function nullVariantParent($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $qb = $this->_em->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX . 'emails')
            ->set('variant_parent_id', ':null')
            ->setParameter('null', null)
            ->where(
                $qb->expr()->in('variant_parent_id', $ids)
            )
            ->execute();
    }
}
