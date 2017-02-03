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

/**
 * MessageQueueRepository.
 */
class MessageQueueRepository extends CommonRepository
{
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
}
