<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Helper;


use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationHelper
{
    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventRepository;

    /**
     * @var OwnerHelper
     */
    private $ownerHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var array
     */
    private $userNotifications = [];

    /**
     * NotificationHelper constructor.
     *
     * @param NotificationModel      $notificationModel
     * @param LeadEventLogRepository $leadEventRepository
     * @param OwnerHelper            $ownerHelper
     * @param TranslatorInterface    $translator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(NotificationModel $notificationModel, LeadEventLogRepository $leadEventRepository, OwnerHelper $ownerHelper, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->notificationModel   = $notificationModel;
        $this->leadEventRepository = $leadEventRepository;
        $this->ownerHelper         = $ownerHelper;
        $this->translator          = $translator;
        $this->em                  = $entityManager;
    }

    /**
     * @param RemappedObjectDAO[] $remappedObjects
     */
    public function noteRemappedObjects(array $remappedObjects)
    {

    }

    /**
     * @param NotificationDAO[] $notifications
     */
    public function noteSyncIssue(array $notifications)
    {

    }

    /**
     * Push only one notification to the user's notification tray
     */
    public function pushUserNotifications()
    {
        if (empty($this->userNotifications)) {
            return;
        }


        $this->userNotifications = [];
    }

    /**
     * @param string $integration
     * @param int    $userId
     * @param int    $contactId
     * @param string $message
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function writeContactSyncEvent(string $integration, int $userId, int $contactId, string $message)
    {
        $eventLog = new LeadEventLog();
        $eventLog->setUserId($userId)
            ->setLead($this->em->getReference(Lead::class, $contactId))
            ->setBundle('integrations')
            ->setObject($integration)
            ->setAction('sync')
            ->setProperties(
                [
                    'object_description' => $this->translator->trans(
                        'mautic.integration.sync.event',
                        [
                            '%integration%' => $integration,
                            '%message%'     => $message
                        ]
                    )
                ]
            );

        $this->leadEventRepository->saveEntity($eventLog);
        $this->leadEventRepository->detachEntity($eventLog);
    }

    /**
     * @param string $header
     * @param string $message
     * @param int    $userId
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function writeUserNotification(string $header, string $message, int $userId)
    {
        $this->notificationModel->addNotification(
            $message,
            null,
            false,
            $header,
            'fa-refresh',
            $this->em->getReference(User::class, $userId)
        );
    }
}