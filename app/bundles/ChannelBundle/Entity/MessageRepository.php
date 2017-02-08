<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class MessageRepository extends CommonRepository
{
    /**
     * @param array $args
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities($args = [])
    {
        $args['qb'] = $this->createQueryBuilder($this->getTableAlias());
        $args['qb']->join('MauticChannelBundle:Channel', 'channel', 'WITH', 'channel.message = '.$this->getTableAlias().'.id');

        return parent::getEntities($args);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'm';
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getMessageList($search = '', $limit = 10, $start = 0)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($this->getTableAlias());
        $q->select('partial '.$alias.'.{id, name, description}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map('intval', $search);
                $q->andWhere($q->expr()->in($alias.'.id', ':search'))
                    ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like($alias.'.name', ':search'))
                    ->setParameter('search', "%{$search}%");
            }
        }
        $q->andWhere($q->expr()->eq($alias.'.isPublished', true));

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * @param $messageId
     *
     * @return array
     */
    public function getMessageChannels($messageId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'message_channels', 'mc')
            ->select('id, channel, channel_id, properties')
            ->where($q->expr()->eq('message_id', ':messageId'))
            ->setParameter('messageId', $messageId)
            ->andWhere($q->expr()->eq('is_enabled', true, 'boolean'));

        $results = $q->execute()->fetchAll();

        $channels = [];
        foreach ($results as $result) {
            $result['properties']         = json_decode($result['properties'], true);
            $channels[$result['channel']] = $result;
        }

        return $channels;
    }

    /**
     * @param $channelId
     *
     * @return array
     */
    public function getChannelMessageByChannelId($channelId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'message_channels', 'mc')
            ->select('id, channel, channel_id, properties, message_id')
            ->where($q->expr()->eq('id', ':channelId'))
            ->setParameter('channelId', $channelId)
            ->andWhere($q->expr()->eq('is_enabled', true, 'boolean'));

        $result = $q->execute()->fetch();

        return $result;
    }
}
