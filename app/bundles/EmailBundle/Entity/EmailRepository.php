<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Class EmailRepository.
 */
class EmailRepository extends CommonRepository
{
    /**
     * Get an array of do not email emails.
     *
     * @param array $leadIds
     *
     * @return array
     */
    public function getDoNotEmailList($leadIds = [])
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('l.id, l.email')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dnc.lead_id')
            ->where('dnc.channel = "email"')
            ->andWhere($q->expr()->neq('l.email', $q->expr()->literal('')));

        if ($leadIds) {
            $q->andWhere(
                $q->expr()->in('l.id', $leadIds)
            );
        }

        $results = $q->execute()->fetchAll();

        $dnc = [];
        foreach ($results as $r) {
            $dnc[$r['id']] = strtolower($r['email']);
        }

        return $dnc;
    }

    /**
     * Check to see if an email is set as do not contact.
     *
     * @param $email
     *
     * @return bool
     */
    public function checkDoNotEmail($email)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('dnc.*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dnc.lead_id')
            ->where('dnc.channel = "email"')
            ->andWhere('l.email = :email')
            ->setParameter('email', $email);

        $results = $q->execute()->fetchAll();
        $dnc     = count($results) ? $results[0] : null;

        if ($dnc === null) {
            return false;
        }

        $dnc['reason'] = (int) $dnc['reason'];

        return [
            'id'           => $dnc['id'],
            'unsubscribed' => ($dnc['reason'] === DoNotContact::UNSUBSCRIBED),
            'bounced'      => ($dnc['reason'] === DoNotContact::BOUNCED),
            'manual'       => ($dnc['reason'] === DoNotContact::MANUAL),
            'comments'     => $dnc['comments'],
        ];
    }

    /**
     * Remove email from DNE list.
     *
     * @param $email
     */
    public function removeFromDoNotEmailList($email)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepo */
        $leadRepo = $this->getEntityManager()->getRepository('MauticLeadBundle:Lead');
        $leadId   = (array) $leadRepo->getLeadByEmail($email, true);

        /** @var \Mautic\LeadBundle\Entity\Lead[] $leads */
        $leads = [];

        foreach ($leadId as $lead) {
            $leads[] = $leadRepo->getEntity($lead['id']);
        }

        foreach ($leads as $lead) {
            $leadModel->removeDncForLead($lead, 'email');
        }
    }

    /**
     * Delete DNC row.
     *
     * @param $id
     */
    public function deleteDoNotEmailEntry($id)
    {
        $this->getEntityManager()->getConnection()->delete(MAUTIC_TABLE_PREFIX.'lead_donotcontact', ['id' => (int) $id]);
    }

    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('e')
            ->from('MauticEmailBundle:Email', 'e', 'e.id');
        if (empty($args['iterator_mode'])) {
            $q->leftJoin('e.category', 'c');

            if (empty($args['ignoreListJoin']) && (!isset($args['email_type']) || $args['email_type'] == 'list')) {
                $q->leftJoin('e.lists', 'l');
            }
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get amounts of sent and read emails.
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
     * @param int  $minContactId
     * @param int  $maxContactId
     * @param bool $countWithMaxMin
     *
     * @return QueryBuilder|int|array
     */
    public function getEmailPendingQuery(
        $emailId,
        $variantIds = null,
        $listIds = null,
        $countOnly = false,
        $limit = null,
        $minContactId = null,
        $maxContactId = null,
        $countWithMaxMin = false
    ) {
        // Do not include leads in the do not contact table
        $dncQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $dncQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where(
                $dncQb->expr()->andX(
                    $dncQb->expr()->eq('dnc.lead_id', 'l.id'),
                    $dncQb->expr()->eq('dnc.channel', $dncQb->expr()->literal('email'))
                )
            );

        // Do not include contacts where the message is pending in the message queue
        $mqQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $mqQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', 'mq')
            ->where(
                $mqQb->expr()->andX(
                    $mqQb->expr()->eq('mq.lead_id', 'l.id'),
                    $mqQb->expr()->neq('mq.status', $mqQb->expr()->literal(MessageQueue::STATUS_SENT)),
                    $mqQb->expr()->eq('mq.channel', $mqQb->expr()->literal('email'))
                )
            );

        // Do not include leads that have already been emailed
        $statQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $statQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'stat')
            ->where(
                $statQb->expr()->eq('stat.lead_id', 'l.id')
            );

        if ($variantIds) {
            if (!in_array($emailId, $variantIds)) {
                $variantIds[] = (int) $emailId;
            }
            $statQb->andWhere($statQb->expr()->in('stat.email_id', $variantIds));
            $mqQb->andWhere($mqQb->expr()->in('mq.channel_id', $variantIds));
        } else {
            $statQb->andWhere($statQb->expr()->eq('stat.email_id', (int) $emailId));
            $mqQb->andWhere($mqQb->expr()->eq('mq.channel_id', (int) $emailId));
        }

        // Only include those who belong to the associated lead lists
        if (is_null($listIds)) {
            // Get a list of lists associated with this email
            $lists = $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->select('el.leadlist_id')
                ->from(MAUTIC_TABLE_PREFIX.'email_list_xref', 'el')
                ->where('el.email_id = '.(int) $emailId)
                ->execute()
                ->fetchAll();

            $listIds = [];
            foreach ($lists as $list) {
                $listIds[] = $list['leadlist_id'];
            }

            if (empty($listIds)) {
                // Prevent fatal error
                return ($countOnly) ? 0 : [];
            }
        } elseif (!is_array($listIds)) {
            $listIds = [$listIds];
        }

        // Only include those in associated segments
        $segmentQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $segmentQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where(
                $segmentQb->expr()->andX(
                    $segmentQb->expr()->eq('ll.lead_id', 'l.id'),
                    $segmentQb->expr()->in('ll.leadlist_id', $listIds),
                    $segmentQb->expr()->eq('ll.manually_removed', ':false')
                )
            );

        // Main query
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        if ($countOnly) {
            $q->select('count(*) as count');
            if ($countWithMaxMin) {
                $q->addSelect('MIN(l.id) as min_id, MAX(l.id) as max_id');
            }
        } else {
            $q->select('l.*');
        }

        $q->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->andWhere(sprintf('EXISTS (%s)', $segmentQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $dncQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $statQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $mqQb->getSQL()))
            ->setParameter('false', false, 'boolean');

        $q = $this->setMinMaxIds($q, 'l.id', $minContactId, $maxContactId);

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

        return $q;
    }

    /**
     * @param      $emailId
     * @param null $variantIds
     * @param null $listIds
     * @param bool $countOnly
     * @param null $limit
     * @param int  $minContactId
     * @param int  $maxContactId
     * @param bool $countWithMaxMin
     *
     * @return array|int
     */
    public function getEmailPendingLeads(
        $emailId,
        $variantIds = null,
        $listIds = null,
        $countOnly = false,
        $limit = null,
        $minContactId = null,
        $maxContactId = null,
        $countWithMaxMin = false
    ) {
        $q = $this->getEmailPendingQuery(
            $emailId,
            $variantIds,
            $listIds,
            $countOnly,
            $limit,
            $minContactId,
            $maxContactId,
            $countWithMaxMin
        );

        if (!($q instanceof QueryBuilder)) {
            return $q;
        }

        $results = $q->execute()->fetchAll();

        if ($countOnly && $countWithMaxMin) {
            // returns array in format ['count' => #, ['min_id' => #, 'max_id' => #]]
            return $results[0];
        } elseif ($countOnly) {
            return (isset($results[0])) ? $results[0]['count'] : 0;
        } else {
            $leads = [];
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
     * @param bool   $topLevel
     * @param null   $emailType
     * @param array  $ignoreIds
     * @param null   $variantParentId
     *
     * @return array
     */
    public function getEmailList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevel = false, $emailType = null, array $ignoreIds = [], $variantParentId = null)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, subject, name, language}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map('intval', $search);
                $q->andWhere($q->expr()->in('e.id', ':search'))
                    ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like('e.name', ':search'))
                    ->setParameter('search', "%{$search}%");
            }
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('e.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ($topLevel) {
            if (true === $topLevel || $topLevel == 'variant') {
                $q->andWhere($q->expr()->isNull('e.variantParent'));
            } elseif ($topLevel == 'translation') {
                $q->andWhere($q->expr()->isNull('e.translationParent'));
            }
        }

        if ($variantParentId) {
            $q->andWhere(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(e.variantParent)', (int) $variantParentId),
                    $q->expr()->eq('e.id', (int) $variantParentId)
                )
            );
        }

        if (!empty($ignoreIds)) {
            $q->andWhere($q->expr()->notIn('e.id', ':emailIds'))
                ->setParameter('emailIds', $ignoreIds);
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
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     * @param                                         $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'e.name',
            'e.subject',
        ]);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     * @param                                         $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        list($expr, $parameters) = $this->addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; //returning a parameter that is not used will lead to a Doctrine error

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.lang'):
                $langUnique      = $this->generateRandomParameterName();
                $langValue       = $filter->string.'_%';
                $forceParameters = [
                    $langUnique => $langValue,
                    $unique     => $filter->string,
                ];
                $expr = $q->expr()->orX(
                    $q->expr()->eq('e.language', ":$unique"),
                    $q->expr()->like('e.language', ":$langUnique")
                );
                $returnParameter = true;
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.lang',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            ['e.name', 'ASC'],
        ];
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * Resets variant_start_date, variant_read_count, variant_sent_count.
     *
     * @param $relatedIds
     * @param $date
     */
    public function resetVariants($relatedIds, $date)
    {
        if (!is_array($relatedIds)) {
            $relatedIds = [(int) $relatedIds];
        }

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set('variant_read_count', 0)
            ->set('variant_sent_count', 0)
            ->set('variant_start_date', ':date')
            ->setParameter('date', $date)
            ->where(
                $qb->expr()->in('id', $relatedIds)
            )
            ->execute();
    }

    /**
     * Up the read/sent counts.
     *
     * @param int        $id
     * @param string     $type
     * @param int        $increaseBy
     * @param bool|false $variant
     */
    public function upCount($id, $type = 'sent', $increaseBy = 1, $variant = false)
    {
        if (!$increaseBy) {
            return;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'emails');
        $q->set($type.'_count', $type.'_count + '.(int) $increaseBy);
        $q->where('id = '.(int) $id);

        if ($variant) {
            $q->set('variant_'.$type.'_count', 'variant_'.$type.'_count + '.(int) $increaseBy);
        }

        // Try to execute 3 times before throwing the exception
        // to increase the chance the update will do its stuff.
        $retrialLimit = 3;
        while ($retrialLimit >= 0) {
            try {
                $q->execute();

                return;
            } catch (DBALException $e) {
                --$retrialLimit;
                if (0 === $retrialLimit) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param null $id
     *
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function getPublishedBroadcasts($id = null)
    {
        $qb   = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($qb, null, true, true, false);

        $expr->add(
            $qb->expr()->eq($this->getTableAlias().'.emailType', $qb->expr()->literal('list'))
        );

        if (!empty($id)) {
            $expr->add(
                $qb->expr()->eq($this->getTableAlias().'.id', (int) $id)
            );
        }
        $qb->where($expr);

        return $qb->getQuery()->iterate();
    }

    /**
     * Set Max and/or Min ID where conditions to the query builder.
     *
     * @param QueryBuilder $q
     * @param string       $column
     * @param int          $minContactId
     * @param int          $maxContactId
     *
     * @return QueryBuilder
     */
    private function setMinMaxIds(QueryBuilder $q, $column, $minContactId, $maxContactId)
    {
        if ($minContactId && is_numeric($minContactId)) {
            $q->andWhere($column.' >= :minContactId');
            $q->setParameter('minContactId', $minContactId);
        }

        if ($maxContactId && is_numeric($maxContactId)) {
            $q->andWhere($column.' <= :maxContactId');
            $q->setParameter('maxContactId', $maxContactId);
        }

        return $q;
    }
}
