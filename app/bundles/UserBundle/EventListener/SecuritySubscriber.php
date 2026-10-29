<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Event\LoginEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SecuritySubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    #[AsEventListener(
        event: LoginEvent::class,
        priority: 0
    )]
    public function onSecurityInteractiveLogin(LoginEvent $event): void
    {
        $userId   = (int) $event->getUser()->getId();
        $useName  = $event->getUser()->getUserIdentifier();

        $log     = [
            'bundle'    => 'user',
            'object'    => 'security',
            'objectId'  => $userId,
            'action'    => 'login',
            'details'   => ['username' => $useName],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];

        $this->auditLogModel->writeToLog($log);
    }
}
