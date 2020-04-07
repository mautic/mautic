<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticSocialBundle\Entity\TweetStat;
use MauticPlugin\MauticSocialBundle\Entity\TweetStatRepository;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);

        /** @var TweetStatRepository $repo */
        $repo                      = $entityManager->getRepository(TweetStat::class);
        $table                     = $repo->getTableName();
        $this->repositories[]      = $repo;
        $this->permissions[$table] = ['tweet' => 'mauticSocial:tweets'];
    }
}
