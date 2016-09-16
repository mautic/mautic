<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
/**
 * MessageQueueRepository
 */
class MessageQueueRepository extends CommonRepository
{
    protected function findMessage($channel,$channelId,$leadId)
    {
        $results = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('mq.id, mq.channel ,mq.channel_id, mq.lead_id')
            ->from(MAUTIC_TABLE_PREFIX . 'message_queue', 'mq')
            ->where('mq.lead_id = :leadId')
            ->andWhere('mq.channel = :channel')
            ->andWhere('mq.channel_id = :channelId')
            ->setParameter('leadId',$leadId)
            ->setParameter('channel',$channel)
            ->setParameter('channelId',$channelId)
            ->execute()
            ->fetchAll();
       return $results;
    }

    public function getQueuedMessages($channel = null, $channelId =  null) {

        $scheduledDate = new \DateTime();

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('mq.id,mq.channel, mq.channel_id as channelId, mq.lead_id as lead, mq.options')
            ->from(MAUTIC_TABLE_PREFIX . 'message_queue', 'mq');

        $exp = $q->where( $q->expr()->eq('mq.success' ,':success'))
                   ->andWhere($q->expr()->lt('mq.attempts','mq.max_attempts'))
            ->andWhere($q->expr()->gt('mq.scheduled_date', ':scheduledDate'))
            ->andWhere($q->expr()->lt('mq.scheduled_date', ':scheduledDateEnd'))
            ->setParameter('success', false, 'boolean')
            ->setParameter('scheduledDate',$scheduledDate->format('Y-m-d 00:00:00'))
            ->setParameter('scheduledDateEnd',$scheduledDate->format('Y-m-d 23:59:59'));
        $q->orderBy('priority,scheduledDate', 'ASC');
        $results= $q->execute($exp)
            ->fetchAll();

        return $results;
    }
}