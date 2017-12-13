<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CampaignNameRepository
 * @package MauticPlugin\UnsubscribeBundle\Entity
 */
class CampaignNameRepository extends CommonRepository
{

    /**
     * @param array $args
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('ucf');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }
}