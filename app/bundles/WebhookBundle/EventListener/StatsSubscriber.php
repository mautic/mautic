<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\WebhookBundle\Entity\Log;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManagerInterface $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addRestrictedRepostories([Log::class], ['webhook' => 'webhook:webhooks']);
    }
}
