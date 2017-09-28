<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * MessageQueueRepository.
 */
class MessageQueueRepository extends CommonRepository
{
    use TimelineTrait;
    /**
     * @param $channel
     * @param $channelId
     * @param $leadId
     */
    public function findMessage($channel, $channelId, $leadId)
    {
        $results = $this->createQueryBuilder('mq')
            ->where('IDENTITY(mq.lead) = :leadId')
            ->andWhere('mq.channel = :channel')
            ->andWhere('mq.channelId = :channelId')
            ->setParameter('leadId', $leadId)
            ->setParameter('channel', $channel)
            ->setParameter('channelId', $channelId)
            ->getQuery()
            ->getResult();

        return ($results) ? $results[0] : null;
    }

    /**
     * @param      $limit
     * @param      $processStarted
     * @param null $channel
     * @param null $channelId
     *
     * @return mixed
     */
    public function getQueuedMessages($limit, $processStarted, $channel = null, $channelId = null)
    {
        $q = $this->createQueryBuilder('mq');

        $q->where($q->expr()->eq('mq.success', ':success'))
            ->andWhere($q->expr()->lt('mq.attempts', 'mq.maxAttempts'))
            ->andWhere('mq.lastAttempt is null or mq.lastAttempt < :processStarted')
            ->andWhere('mq.scheduledDate <= :processStarted')
            ->setParameter('success', false, 'boolean')
            ->setParameter('processStarted', $processStarted)
            ->indexBy('mq', 'mq.id');

        $q->orderBy('mq.priority, mq.scheduledDate', 'ASC');

        if ($limit) {
            $q->setMaxResults((int) $limit);
        }

        if ($channel) {
            $q->andWhere($q->expr()->eq('mq.channel', ':channel'))
                ->setParameter('channel', $channel);

            if ($channelId) {
                $q->andWhere($q->expr()->eq('mq.channelId', (int) $channelId));
            }
        }

        $results = $q->getQuery()->getResult();

        return $results;
    }

    /**
     * @param            $channel
     * @param array|null $ids
     *
     * @return bool|string
     */
    public function getQueuedChannelCount($channel, array $ids = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $expr = $q->expr()->andX(
            $q->expr()->eq($this->getTableAlias().'.channel', ':channel'),
            $q->expr()->neq($this->getTableAlias().'.status', ':status')
        );

        if (!empty($ids)) {
            $expr->add(
                $q->expr()->in($this->getTableAlias().'.channel_id', $ids)
            );
        }

        return (int) $q->select('count(*)')
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', $this->getTableAlias())
            ->where($expr)
            ->setParameters(
                [
                    'channel' => $channel,
                    'status'  => MessageQueue::STATUS_SENT,
                ]
            )
            ->execute()
            ->fetchColumn();
    }

    /**
     * Get a lead's point log.
     *
     * @param int|null $leadId
     * @param array    $options
     *
     * @return array
     */
    public function getLeadTimelineEvents($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', 'mq')
            ->select('mq.lead_id, mq.channel as channelName, mq.channel_id as channelId, mq.priority as priority, mq.attempts, mq.success as status, mq.date_published as dateAdded, mq.scheduled_date as scheduledDate, mq.last_attempt as lastAttempt, mq.date_sent as dateSent');

        if ($leadId) {
            $query->where('mq.lead_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('mq.channel', $query->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        return $this->getTimelineResults($query, $options, 'mq.channel', 'mq.date_published', [], ['dateAdded']);
    }
}
