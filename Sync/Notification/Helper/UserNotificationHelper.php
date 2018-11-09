<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Helper;


use MauticPlugin\IntegrationsBundle\Sync\Notification\Writer;
use Symfony\Component\Translation\TranslatorInterface;

class UserNotificationHelper
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
     * @var RouteHelper
     */
    private $routeHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $integrationDisplayName;

    /**
     * @var string
     */
    private $objectDisplayName;

    /**
     * UserSummaryNotificationHelper constructor.
     *
     * @param Writer              $writer
     * @param UserHelper          $userHelper
     * @param RouteHelper         $routeHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Writer $writer,
        UserHelper $userHelper,
        RouteHelper $routeHelper,
        TranslatorInterface $translator
    ) {
        $this->writer      = $writer;
        $this->userHelper  = $userHelper;
        $this->routeHelper = $routeHelper;
        $this->translator  = $translator;
    }

    /**
     * @param string $message
     * @param string $integrationDisplayName
     * @param string $objectDisplayName
     * @param string $mauticObject
     * @param string $id
     * @param string $linkText
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    public function writeNotification(
        string $message,
        string $integrationDisplayName,
        string $objectDisplayName,
        string $mauticObject,
        string $id,
        string $linkText
    ) {
        $this->integrationDisplayName = $integrationDisplayName;
        $this->objectDisplayName      = $objectDisplayName;
        $link                         = $this->routeHelper->getLink($mauticObject, $id, $linkText);

        if ($owner = $this->userHelper->getOwner($mauticObject, $id)) {
            $this->writeMessage($message, $link, $owner);

            return;
        }

        $adminUsers = $this->userHelper->getAdminUsers();
        foreach ($adminUsers as $userId) {
            $this->writeMessage($message, $link, $userId);
        }
    }

    /**
     * @param string $message
     * @param string $link
     * @param int    $userId
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function writeMessage(string $message, string $link, int $userId)
    {
        $this->writer->writeUserNotification(
            $this->translator->trans(
                'mautic.integration.sync.user_notification.header',
                [
                    '%integration%' => $this->integrationDisplayName,
                    '%object%'      => $this->objectDisplayName
                ]
            ),
            $this->translator->trans(
                'mautic.integration.sync.user_notification.sync_error',
                [
                    '%name%'   => $link,
                    '%message%' => $message
                ]
            ),
            $userId
        );
    }
}