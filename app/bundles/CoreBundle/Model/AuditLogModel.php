<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\UserBundle\Entity\User;

/**
 * Class AuditLogModel.
 */
class AuditLogModel extends AbstractCommonModel
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AuditLogRepository
     */
    protected $repository;

    public function __construct(EntityManager $em)
    {
        $this->em         = $em;
        $this->repository = $this->em->getRepository('MauticCoreBundle:AuditLog');
    }

    /**
     * Writes an entry to the audit log.
     *
     * @param array $args [bundle, object, objectId, action, details, ipAddress]
     */
    public function writeToLog(array $args)
    {
        $bundle    = (isset($args['bundle'])) ? $args['bundle'] : '';
        $object    = (isset($args['object'])) ? $args['object'] : '';
        $objectId  = (isset($args['objectId'])) ? $args['objectId'] : '';
        $action    = (isset($args['action'])) ? $args['action'] : '';
        $details   = (isset($args['details'])) ? $args['details'] : '';
        $ipAddress = (isset($args['ipAddress'])) ? $args['ipAddress'] : '';

        $log = new AuditLog();
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

        $this->repository->saveEntity($log);

        $this->em->detach($log);
    }

    /**
     * Get the audit log for specific object.
     *
     * @param string                  $object
     * @param string|int              $id
     * @param \DateTimeInterface|null $afterDate
     * @param int                     $limit
     * @param string|null             $bundle
     *
     * @return mixed
     */
    public function getLogForObject($object, $id, $afterDate = null, $limit = 10, $bundle = null)
    {
        return $this->repository->getLogForObject($object, $id, $limit, $afterDate, $bundle);
    }
}
