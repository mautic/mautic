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
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;

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
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('distinct(l.email)')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = dnc.lead_id')
            ->where('dnc.channel = "email"')
            ->andWhere($q->expr()->neq('l.email', $q->expr()->literal('')));


        $results = $q->execute()->fetchAll();

        $dnc = array();

        foreach ($results as $r) {
            $dnc[] = strtolower($r['email']);
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
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('dnc.*')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.id = dnc.lead_id')
            ->where('dnc.channel = "email"')
            ->andWhere('l.email = :email')
            ->setParameter('email', $email);

        $results = $q->execute()->fetchAll();
        $dnc = count($results) ? $results[0] : null;

        if ($dnc === null) {
            return false;
        }

        $dnc['reason'] = (int) $dnc['reason'];

        return array(
            'id' => $dnc['id'],
            'unsubscribed' => ($dnc['reason'] === DoNotContact::UNSUBSCRIBED),
            'bounced' => ($dnc['reason'] === DoNotContact::BOUNCED),
            'manual' => ($dnc['reason'] === DoNotContact::MANUAL),
            'comments' => $dnc['comments']
        );
    }

    /**
     * Remove email from DNE list
     *
     * @param $email
     */
    public function removeFromDoNotEmailList($email)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepo */
        $leadRepo = $this->getEntityManager()->getRepository('MauticLeadBundle:Lead');
        $leadId = (array) $leadRepo->getLeadByEmail($email, true);

        /** @var \Mautic\LeadBundle\Entity\Lead[] $leads */
        $leads = array();

        foreach ($leadId as $lead) {
            $leads[] = $leadRepo->getEntity($lead['id']);
        }

        foreach ($leads as $lead) {
            $leadModel->removeDncForLead($lead, 'email');
        }
    }

    /**
     * Delete DNC row
     *
     * @param $id
     */
    public function deleteDoNotEmailEntry($id)
    {
        $this->getEntityManager()->getConnection()->delete(MAUTIC_TABLE_PREFIX.'lead_donotcontact', array('id' => (int) $id));
    }

    /**
     * Get a list of entities
     *
     * @param array      $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('e')
            ->from('MauticEmailBundle:Email', 'e', 'e.id');
        if (empty($args['iterator_mode'])) {
            $q->leftJoin('e.category', 'c');

            if (!isset($args['email_type']) || $args['email_type'] == 'list') {
                $q->leftJoin('e.lists', 'l');
            }
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get amounts of sent and read emails
     *
     * @return array
     */
    public function getSentReadCount()
    {
        $q = $this->getEntityManager()->createQueryBuilder();
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
     * @param      $emailId
     * @param null $variantIds
     * @param null $listIds
     * @param bool $countOnly
     * @param null $limit
     *
     * @return array|int
     */
    public function getEmailPendingLeads($emailId, $variantIds = null, $listIds = null, $countOnly = false, $limit = null)
    {
        // Do not include leads in the do not contact table
        $dncQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $dncQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_donotcontact', 'dnc')
            ->where(
                $dncQb->expr()->eq('dnc.lead_id', 'l.id')
            )
            ->andWhere('dnc.channel = "email"');

        // Do not include leads that have already been emailed
        $statQb    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('null')
            ->from(MAUTIC_TABLE_PREFIX . 'email_stats', 'stat');

        $statExpr = $statQb->expr()->andX(
            $statQb->expr()->eq('stat.lead_id', 'l.id')
        );

        if ($variantIds) {
            $variantIds[] = (int) $emailId;
            $statExpr->add(
                $statQb->expr()->in('stat.email_id', $variantIds)
            );
        } else {
            $statExpr->add(
                $statQb->expr()->eq('stat.email_id', (int) $emailId)
            );
        }
        $statQb->where($statExpr);

        // Only include those who belong to the associated lead lists
        if (null === $listIds) {
            // Get a list of lists associated with this email
            $lists = $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->select('el.leadlist_id')
                ->from(MAUTIC_TABLE_PREFIX . 'email_list_xref', 'el')
                ->where('el.email_id = ' . (int) $emailId)
                ->execute()
                ->fetchAll();

            $listIds = array();
            foreach ($lists as $list) {
                $listIds[] = $list['leadlist_id'];
            }

            if (empty($listIds)) {
                // Prevent fatal error
                return ($countOnly) ? 0 : array();
            }
        } elseif (!is_array($listIds)) {
            $listIds = array($listIds);
        }

        $listQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $listQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_lists_leads', 'll')
            ->where(
                $listQb->expr()->andX(
                    $listQb->expr()->in('ll.leadlist_id', $listIds),
                    $listQb->expr()->eq('ll.lead_id', 'l.id'),
                    $listQb->expr()->eq('ll.manually_removed', ':false')
                )
            );

        // Main query
        $q  = $this->getEntityManager()->getConnection()->createQueryBuilder();
        if ($countOnly) {
            $q->select('count(l.id) as count');
        } else {
            $q->select('l.*')
                ->orderBy('l.id');
        }
        $q->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->andWhere(sprintf('NOT EXISTS (%s)', $dncQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $statQb->getSQL()))
            ->andWhere(sprintf('EXISTS (%s)', $listQb->getSQL()))
            ->setParameter('false', false, 'boolean');

        // Has an email
        $q->andWhere(
            $q->expr()->andX(
                $q->expr()->isNotNull('l.email'),
                $q->expr()->neq('l.email', $q->expr()->literal(''))
            )
        );

        if (!empty($limit)) {
            $q->setFirstResult(0)
                ->setMaxResults($limit);
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
     * @param string $emailType
     *
     * @return array
     */
    public function getEmailList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevelOnly = false, $emailType = null)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, subject, name, language}');

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('e.name', ':search'))
                ->setParameter('search', "{$search}%");
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('e.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ($topLevelOnly) {
            $q->andWhere($q->expr()->isNull('e.variantParent'));
        }

        if (!empty($emailType)) {
            $q->andWhere(
                $q->expr()->eq('e.emailType', $q->expr()->literal($emailType))
            );
        }

        $q->orderBy('e.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX(
            $q->expr()->like('e.name',  ":$unique"),
            $q->expr()->like('e.subject', ":$unique")
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
     * @param \Doctrine\ORM\QueryBuilder $q
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
            array('e.name', 'ASC')
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

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX . 'emails')
            ->set('variant_parent_id', ':null')
            ->setParameter('null', null)
            ->where(
                $qb->expr()->in('variant_parent_id', $ids)
            )
            ->execute();
    }

    /**
     * Resets variant_start_date, variant_read_count, variant_sent_count
     *
     * @param $variantParentId
     * @param $date
     */
    public function resetVariants($variantParentId, $date)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX . 'emails')
            ->set('variant_read_count', 0)
            ->set('variant_sent_count', 0)
            ->set('variant_start_date', ':date')
            ->setParameter('date', $date)
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('id', (int) $variantParentId),
                    $qb->expr()->eq('variant_parent_id', (int) $variantParentId)
                )
            )
            ->execute();
    }

    /**
     * Up the read/sent counts
     *
     * @param            $id
     * @param string     $type
     * @param int        $increaseBy
     * @param bool|false $variant
     */
    public function upCount($id, $type = 'sent', $increaseBy = 1, $variant = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set($type . '_count', $type . '_count + ' . (int) $increaseBy)
            ->where('id = ' . (int) $id);

        if ($variant) {
            $q->set('variant_' . $type . '_count', 'variant_' . $type . '_count + ' . (int) $increaseBy);
        }

        $q->execute();
    }
}
