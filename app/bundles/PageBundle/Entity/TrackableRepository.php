<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class TrackableRepository
 */
class TrackableRepository extends CommonRepository
{
    /**
     * Find redirects that are trackable
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
            ->innerJoin('channel_url_trackables', 't',
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

    public function findByUrl($url, $channel, $channelId)
    {

    }
}
