<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class SmsRepository.
 */
class SmsRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select($this->getTableAlias())
            ->from('MauticSmsBundle:Sms', $this->getTableAlias(), $this->getTableAlias().'.id');

        if (empty($args['iterator_mode'])) {
            $q->leftJoin($this->getTableAlias().'.category', 'c');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
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
            $qb->expr()->eq($this->getTableAlias().'.smsType', $qb->expr()->literal('list'))
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
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getSegmentsContactsQuery(int $smsId)
    {
        // Main query
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->from('sms_message_list_xref', 'sml')
            ->join('sml', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = sml.leadlist_id and ll.is_published = 1')
            ->join('ll', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll', 'lll.leadlist_id = sml.leadlist_id and lll.manually_removed = 0')
            ->join('lll', MAUTIC_TABLE_PREFIX.'leads', 'l', 'lll.lead_id = l.id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('sml.sms_id', ':smsId')
                )
            )
            ->setParameter('smsId', $smsId)
            // Order by ID so we can query by greater than X contact ID when batching
            ->orderBy('lll.lead_id');

        return $q;
    }

    /**
     * Get amounts of sent and read emails.
     *
     * @return array
     */
    public function getSentCount()
    {
        $q = $this->_em->createQueryBuilder();
        $q->select('SUM(e.sentCount) as sent_count')
            ->from('MauticSmsBundle:Sms', 'e');
        $results = $q->getQuery()->getSingleResult(Query::HYDRATE_ARRAY);

        if (!isset($results['sent_count'])) {
            $results['sent_count'] = 0;
        }

        return $results;
    }

    /**
     * Get amounts of pending text messages.
     * @return QueryBuilder|int|array
     */
    public function getSmsPendingQuery($smsId)
    {
        // Do not include leads in the do not contact table
        $dncQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $dncQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where(
                $dncQb->expr()->andX(
                    $dncQb->expr()->eq('dnc.lead_id', 'l.id'),
                    $dncQb->expr()->eq('dnc.channel', $dncQb->expr()->literal('sms'))
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
                    $mqQb->expr()->eq('mq.channel', $mqQb->expr()->literal('sms'))
                )
            );

        $lists = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('el.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'sms_message_list_xref', 'el')
            ->where('el.sms_id = '.(int) $smsId);
        $listIds = [];
        if ($lists) {
            foreach ($lists as $list) {
                $listIds[] = $list['leadlist_id'];
            }
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

        // Do not include leads that have already been sent the text message
        $statQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $statQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'sms_message_stats', 'stat')
            ->where(
                $statQb->expr()->eq('stat.lead_id', 'l.id')
            );

        $statQb->andWhere($statQb->expr()->eq('stat.sms_id', (int) $smsId));
        $mqQb->andWhere($mqQb->expr()->eq('mq.channel_id', (int) $smsId));

        // Main query
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('l.*');

        $q->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->andWhere(sprintf('EXISTS (%s)', $segmentQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $dncQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $statQb->getSQL()))
            ->andWhere(sprintf('NOT EXISTS (%s)', $mqQb->getSQL()))
            ->setParameter('false', false, 'boolean');

        // Has an email
        $q->andWhere(
            $q->expr()->andX(
                $q->expr()->isNotNull('l.mobile'),
                $q->expr()->neq('l.mobile', $q->expr()->literal(''))
            )
        );

        return $q;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
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
     * Up the click/sent counts.
     *
     * @param        $id
     * @param string $type
     * @param int    $increaseBy
     */
    public function upCount($id, $type = 'sent', $increaseBy = 1)
    {
        try {
            $q = $this->_em->getConnection()->createQueryBuilder();

            $q->update(MAUTIC_TABLE_PREFIX.'sms_messages')
                ->set($type.'_count', $type.'_count + '.(int) $increaseBy)
                ->where('id = '.(int) $id);

            $q->execute();
        } catch (\Exception $exception) {
            // not important
        }
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     * @param string $smsType
     *
     * @return array
     */
    public function getSmsList($search = '', $limit = 10, $start = 0, $viewOther = false, $smsType = null)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, name, language}');

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

        if (!empty($smsType)) {
            $q->andWhere(
                $q->expr()->eq('e.smsType', $q->expr()->literal($smsType))
            );
        }

        $q->orderBy('e.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }
}
