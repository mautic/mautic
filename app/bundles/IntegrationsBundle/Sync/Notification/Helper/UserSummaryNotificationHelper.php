<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Notification\Writer;
use Symfony\Component\Translation\TranslatorInterface;

class UserSummaryNotificationHelper
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var OwnerProvider
     */
    private $ownerProvider;

    /**
     * @var RouteHelper
     */
    private $routeHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $userNotifications = [];

    /**
     * @var string
     */
    private $integrationDisplayName;

    /**
     * @var string
     */
    private $objectDisplayName;

    /**
     * @var string
     */
    private $mauticObject;

    /**
     * @var string
     */
    private $listTranslationKey;

    public function __construct(
        Writer $writer,
        UserHelper $userHelper,
        OwnerProvider $ownerProvider,
        RouteHelper $routeHelper,
        TranslatorInterface $translator
    ) {
        $this->writer        = $writer;
        $this->userHelper    = $userHelper;
        $this->ownerProvider = $ownerProvider;
        $this->routeHelper   = $routeHelper;
        $this->translator    = $translator;
    }

    /**
     * @throws ObjectNotSupportedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function writeNotifications(string $mauticObject, string $listTranslationKey): void
    {
        $this->mauticObject       = $mauticObject;
        $this->listTranslationKey = $listTranslationKey;

        if (empty($this->userNotifications)) {
            return;
        }

        foreach ($this->userNotifications as $integrationDisplayName => $integrationNotifications) {
            foreach ($integrationNotifications as $objectDisplayName => $objectNotifications) {
                $this->integrationDisplayName = $integrationDisplayName;
                $this->objectDisplayName      = $objectDisplayName;

                $this->findAndSendToUsers($objectNotifications);
            }
        }

        $this->userNotifications = [];
    }

    public function storeSummaryNotification(string $integrationDisplayName, string $objectDisplayName, int $id): void
    {
        if (!isset($this->userNotifications[$integrationDisplayName])) {
            $this->userNotifications[$integrationDisplayName] = [];
        }

        if (!isset($this->userNotifications[$integrationDisplayName][$objectDisplayName])) {
            $this->userNotifications[$integrationDisplayName][$objectDisplayName] = [];
        }

        $this->userNotifications[$integrationDisplayName][$objectDisplayName][$id] = $id;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws ObjectNotSupportedException
     */
    private function findAndSendToUsers(array $ids): void
    {
        $results = $this->ownerProvider->getOwnersForObjectIds($this->mauticObject, $ids);
        $owners  = [];

        // Group by owner ID.
        foreach ($results as $result) {
            $ownerId = $result['owner_id'];
            if (!isset($owners[$ownerId])) {
                $owners[$ownerId] = [];
            }

            $owners[$ownerId][] = (int) $result['id'];
        }

        foreach ($owners as $userId => $ownedObjectIds) {
            // Keep track of who is left over to send to admins instead
            $ids = array_diff($ids, $ownedObjectIds);

            $this->writeNotification($ownedObjectIds, $userId);
        }

        if (count($ids)) {
            // Send the rest to admins
            $adminUserIds = $this->userHelper->getAdminUsers();
            foreach ($adminUserIds as $userId) {
                $this->writeNotification($ids, $userId);
            }
        }
    }

    /**
     * @throws ObjectNotSupportedException
     * @throws \Doctrine\ORM\ORMException
     */
    private function writeNotification(array $ids, int $userId): void
    {
        $count = count($ids);

        if ($count > 25) {
            $this->writer->writeUserNotification(
                $this->translator->trans(
                    'mautic.integration.sync.user_notification.header',
                    [
                        '%integration%' => $this->integrationDisplayName,
                        '%object%'      => ucfirst($this->objectDisplayName),
                    ]
                ),
                $this->translator->trans(
                    'mautic.integration.sync.user_notification.count_message',
                    ['%count%' => $count]
                ),
                $userId
            );

            return;
        }

        $this->writer->writeUserNotification(
            $this->translator->trans(
                'mautic.integration.sync.user_notification.header',
                [
                    '%integration%' => $this->integrationDisplayName,
                    '%object%'      => ucfirst($this->objectDisplayName),
                ]
            ),
            $this->translator->trans(
                $this->listTranslationKey,
                [
                    '%contacts%' => $this->routeHelper->getLinkCsv($this->mauticObject, $ids),
                ]
            ),
            $userId
        );
    }
}
