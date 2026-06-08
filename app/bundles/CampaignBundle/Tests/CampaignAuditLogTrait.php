<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\UserBundle\Entity\User;

trait CampaignAuditLogTrait
{
    /**
     * Helper method to save audit logs for campaign testing.
     *
     * @param array<int, array{dateAdded: string, details: array<string, mixed>}> $auditLogs
     */
    private function saveAuditLogs(EntityManagerInterface $em, array $auditLogs, Campaign $campaign): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        foreach ($auditLogs as $auditLog) {
            $log = new AuditLog();
            $log->setDateAdded(new \DateTime($auditLog['dateAdded']));
            $log->setDetails($auditLog['details']);
            $log->setObject('campaign');
            $log->setBundle('campaign');
            $log->setAction('edit');
            $log->setObjectId($campaign->getId());
            $log->setUserId($user->getId());
            $log->setUserName($user->getUsername());
            $log->setIpAddress('127.0.0.1');
            $em->persist($log);
        }
        $em->flush();
    }
}
