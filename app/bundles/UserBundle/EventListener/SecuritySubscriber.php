<?php

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Event\LoginEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_LOGIN => ['onSecurityInteractiveLogin', 0],
        ];
    }

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
