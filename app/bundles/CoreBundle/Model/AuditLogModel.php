<?php

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\UserBundle\Entity\User;

/**
 * @extends AbstractCommonModel<AuditLog>
 */
class AuditLogModel extends AbstractCommonModel
{
    /**
     * @return \Mautic\CoreBundle\Entity\AuditLogRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(AuditLog::class);
    }

    /**
     * Writes an entry to the audit log.
     *
     * @param array $args [bundle, object, objectId, action, details, ipAddress]
     */
    public function writeToLog(array $args): void
    {
        $bundle    = $args['bundle'] ?? '';
        $object    = $args['object'] ?? '';
        $objectId  = $args['objectId'] ?? '';
        $action    = $args['action'] ?? '';
        $details   = $args['details'] ?? '';
        $ipAddress = isset($args['ipAddress']) ? ($this->coreParametersHelper->get('anonymize_ip') ? '*.*.*.*' : $args['ipAddress']) : '';
        $log       = new AuditLog();
        $log->setBundle($bundle);
        $log->setObject($object);
        $log->setObjectId($objectId);
        $log->setAction($action);
        $log->setDetails($details);
        $log->setIpAddress($ipAddress);
        $log->setDateAdded(new \DateTime());

        $user     = (!defined('MAUTIC_IGNORE_AUDITLOG_USER') && !defined('MAUTIC_AUDITLOG_USER')) ? $this->userHelper->getUser() : null;
        $userId   = 0;
        $userName = defined('MAUTIC_AUDITLOG_USER') ? MAUTIC_AUDITLOG_USER : $this->translator->trans('mautic.core.system');
        if ($user instanceof User && $user->getId()) {
            $userId   = $user->getId();
            $userName = $user->getName();
        }
        $log->setUserId($userId);
        $log->setUserName($userName);

        $this->em->getRepository(AuditLog::class)->saveEntity($log);

        $this->em->detach($log);
    }

    /**
     * Get the audit log for specific object.
     *
     * @param string|null             $object
     * @param string|int              $id
     * @param \DateTimeInterface|null $afterDate
     * @param int                     $limit
     * @param string|null             $bundle
     *
     * @return mixed
     */
    public function getLogForObject($object, $id, $afterDate = null, $limit = 10, $bundle = null)
    {
        return $this->getRepository()->getLogForObject($object, $id, $limit, $afterDate, $bundle);
    }
}
