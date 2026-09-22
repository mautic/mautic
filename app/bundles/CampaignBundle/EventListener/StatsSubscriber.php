<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManagerInterface $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories(
            [
                Lead::class,
                LeadEventLog::class,
            ]
        );
    }
}
