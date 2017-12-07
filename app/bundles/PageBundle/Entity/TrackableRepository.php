<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;

/**
 * Class TrackableRepository.
 */
class TrackableRepository extends CommonRepository
{
    /**
     * Find redirects that are trackable.
     *
     * @param $channel
     * @param $channelId
     *
     * @return mixed
     */
    public function findByChannel($channel, $channelId)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        return $q->select('r.redirect_id, r.url, r.hits, r.unique_hits')
            ->from(MAUTIC_TABLE_PREFIX.'page_redirects', 'r')
            ->innerJoin('r', MAUTIC_TABLE_PREFIX.'channel_url_trackables', $this->getTableAlias(),
                $q->expr()->andX(
                    $q->expr()->eq('r.id', 't.redirect_id'),
                    $q->expr()->eq('t.channel', ':channel'),
                    $q->expr()->eq('t.channel_id', (int) $channelId)
                )
            )
            ->setParameter('channel', $channel)
            ->orderBy('r.url')
            ->execute()
            ->fetchAll();
    }

    /**
     * Get a Trackable by Redirect URL.
     *
     * @param $url
     * @param $channel
     * @param $channelId
     *
     * @return array
     */
    public function findByUrl($url, $channel, $channelId)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($alias)
            ->innerJoin("$alias.redirect", 'r');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq("$alias.channel", ':channel'),
                $q->expr()->eq("$alias.channelId", (int) $channelId),
                $q->expr()->eq('r.url', ':url')
            )
        )
            ->setParameter('url', $url)
            ->setParameter('channel', $channel);

        $result = $q->getQuery()->getResult();

        return ($result) ? $result[0] : null;
    }

    /**
     * Get an array of Trackable entities by Redirect URLs.
     *
     * @param array $urls
     * @param       $channel
     * @param       $channelId
     *
     * @return array
     */
    public function findByUrls(array $urls, $channel, $channelId)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($alias)
            ->innerJoin("$alias.redirect", 'r');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq("$alias.channel", ':channel'),
                $q->expr()->eq("$alias.channelId", (int) $channelId),
                $q->expr()->in('r.url', ':urls')
            )
        )
            ->setParameter('urls', $urls)
            ->setParameter('channel', $channel);

        return $q->getQuery()->getResult();
    }

    /**
     * Up the hit count.
     *
     * @param      $redirectId
     * @param      $channel
     * @param      $channelId
     * @param int  $increaseBy
     * @param bool $unique
     */
    public function upHitCount($redirectId, $channel, $channelId, $increaseBy = 1, $unique = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'channel_url_trackables')
            ->set('hits', 'hits + '.(int) $increaseBy)
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('redirect_id', (int) $redirectId),
                    $q->expr()->eq('channel', ':channel'),
                    $q->expr()->eq('channel_id', (int) $channelId)
                )
            )
            ->setParameter('channel', $channel);

        if ($unique) {
            $q->set('unique_hits', 'unique_hits + '.(int) $increaseBy);
        }

        $q->execute();
    }

    /**
     * Get hit count.
     *
     * @param                 $channel
     * @param                 $channelIds
     * @param                 $listId
     * @param ChartQuery|null $chartQuery
     *
     * @return array|int
     */
    public function getCount($channel, $channelIds, $listId, ChartQuery $chartQuery = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('count(ph.id) as click_count')
            ->from(MAUTIC_TABLE_PREFIX.'channel_url_trackables', 'cut')
            ->leftJoin('cut', MAUTIC_TABLE_PREFIX.'page_hits', 'ph', 'ph.redirect_id = cut.redirect_id');

        $q->where(
            'cut.channel = :channel'
        )->setParameter('channel', $channel);

        if ($channelIds) {
            if (!is_array($channelIds)) {
                $channelIds = [(int) $channelIds];
            }
            $q->andWhere(
                $q->expr()->in('cut.channel_id', $channelIds)
            );
        }

        if ($listId) {
            $q->leftJoin('ph', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'cs', 'cs.lead_id = ph.lead_id');

            if (true === $listId) {
                $q->addSelect('cs.leadlist_id')
                    ->groupBy('cs.leadlist_id');
            } elseif (is_array($listId)) {
                $q->andWhere(
                    $q->expr()->in('cs.leadlist_id', array_map('intval', $listId))
                )
                    ->addSelect('cs.leadlist_id')
                    ->groupBy('cs.leadlist_id');
            } else {
                $q->andWhere('cs.leadlist_id = :list_id')
                    ->setParameter('list_id', $listId);
            }
        }

        if ($chartQuery) {
            $chartQuery->applyDateFilters($q, 'date_hit', 'ph');
        }

        $results = $q->execute()->fetchAll();

        if (true === $listId || is_array($listId)) {
            // Return array of results
            $byList = [];
            foreach ($results as $result) {
                $byList[$result['leadlist_id']] = $result['click_count'];
            }

            return $byList;
        }

        return (isset($results[0])) ? $results[0]['click_count'] : 0;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 't';
    }
}
