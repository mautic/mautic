<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\UserBundle\Event as Events;
use Mautic\UserBundle\UserEvents;

/**
 * Class UserSubscriber.
 */
class UserSubscriber extends CommonSubscriber
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * UserSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel  = $auditLogModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            UserEvents::USER_POST_SAVE   => ['onUserPostSave', 0],
            UserEvents::USER_POST_DELETE => ['onUserDelete', 0],
            UserEvents::ROLE_POST_SAVE   => ['onRolePostSave', 0],
            UserEvents::ROLE_POST_DELETE => ['onRoleDelete', 0],
        ];
    }

    /**
     * Add a user entry to the audit log.
     *
     * @param Events\UserEvent $event
     */
    public function onUserPostSave(Events\UserEvent $event)
    {
        $user = $event->getUser();

        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'user',
                'object'    => 'user',
                'objectId'  => $user->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a user delete entry to the audit log.
     *
     * @param Events\UserEvent $event
     */
    public function onUserDelete(Events\UserEvent $event)
    {
        $user = $event->getUser();
        $log  = [
            'bundle'    => 'user',
            'object'    => 'user',
            'objectId'  => $user->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $user->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a role entry to the audit log.
     *
     * @param Events\RoleEvent $event
     */
    public function onRolePostSave(Events\RoleEvent $event)
    {
        $role = $event->getRole();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'user',
                'object'    => 'role',
                'objectId'  => $role->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a role delete entry to the audit log.
     *
     * @param Events\RoleEvent $event
     */
    public function onRoleDelete(Events\RoleEvent $event)
    {
        $role = $event->getRole();
        $log  = [
            'bundle'    => 'user',
            'object'    => 'role',
            'objectId'  => $role->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $role->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
