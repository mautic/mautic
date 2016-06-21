<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\FeedBundle\Helper\FeedHelper;
use Mautic\CoreBundle\Factory\MauticFactory;


/**
 * Class FeedRepository
 *
 * @package Mautic\FeedBundle\Entity
 */
class FeedRepository extends CommonRepository
{
    /**
     *
     * @param MauticFactory $factory
     * @param Feed $feed
     * @param null | \DateTime $maxDate max date of validity expected
     *
     * @return Snapshot
     */
    public function latestSnapshot(MauticFactory $factory, Feed $feed, $maxDate=null)
    {

        for ($i = sizeof($feed->getSnapshots())-1; $i > 0; $i --) { //TODO faire une requette DQL pour eviter de charger tous les snapshot en memoire
            /** @var \Mautic\FeedBundle\Entity\Snapshot $s */
            $s = $feed->getSnapshots()->get($i);
            if ($s->isExpired()===false && (is_null($maxDate) || $s->getDate()>$maxDate)){
                return $s;
            }
        }
        unset($s);
        // there is no valid feed... need to parse a new one

        /** @var FeedHelper $feedHelper */
        $feedHelper= $factory->getHelper('feed');

        if (!is_null($xmlString=$feedHelper->getStringFromFeed($feed->getFeedUrl()))){
            $ns= new Snapshot();
            $ns->setDate(new \DateTime());
            $ns->setXmlString($xmlString);
            $ns->setFeed($feed);

            $this->_em->persist($ns);
            $this->_em->persist($feed);
            $this->_em->flush();
            return $ns;
        }

        return null;
    }
    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'f';
    }
}
