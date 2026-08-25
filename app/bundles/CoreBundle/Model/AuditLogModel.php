<?php

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends AbstractCommonModel<AuditLog>
 */
class AuditLogModel extends AbstractCommonModel
{
    public static function getName(): string
    {
        return 'core.auditlog';
    }

    private AuditLogRepository $auditLogRepository;

    #[Required]
    public function autowireAuditLogModel(
        AuditLogRepository $auditLogRepository,
    ): void {
        $this->auditLogRepository = $auditLogRepository;
    }

    public function getRepository(): AuditLogRepository
    {
        return $this->auditLogRepository;
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

        $this->auditLogRepository->saveEntity($log);

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
        return $this->auditLogRepository->getLogForObject($object, $id, $limit, $afterDate, $bundle);
    }
}
