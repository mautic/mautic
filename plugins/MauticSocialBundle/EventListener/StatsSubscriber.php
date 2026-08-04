<?php

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticSocialBundle\Entity\TweetStatRepository;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(
        CorePermissions $security,
        EntityManagerInterface $entityManager,
        TweetStatRepository $tweetStatRepository,
    ) {
        parent::__construct($security, $entityManager);

        $table                     = $tweetStatRepository->getTableName();
        $this->repositories[]      = $tweetStatRepository;
        $this->permissions[$table] = ['tweet' => 'mauticSocial:tweets'];
    }
}
