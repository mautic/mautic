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


/**
 * Class FeedRepository
 *
 * @package Mautic\FeedBundle\Entity
 */
class FeedRepository extends CommonRepository
{
    public function latestSnapshot(Feed $feed)
    {
        /** @var FeedHelper $fh */
        $fh= $this->factory->getHelper('feed');
        for ($i = sizeof($feed->getSnapshots())-1; $i > 0; $i --) { //TODO faire une requette DQL pour eviter de charger tous les snapshot en memoire
            /** @var \Mautic\FeedBundle\Entity\Snapshot $s */
            $s = $feed->getSnapshots()->get($i);
            if ($s->isExpired()===false){
                return $s;
            }
        }
        unset($s);
        // there is no valid feed... need to pase a new one

        if (!is_null($xmlString=$fh->getStringFromFeed($feed->getFeedUrl()))){
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
