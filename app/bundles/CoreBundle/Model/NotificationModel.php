<?php

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends FormModel<Notification>
 */
class NotificationModel extends FormModel
{
    /**
     * @var bool
     */
    protected $disableUpdates;

    public function __construct(
        protected PathsHelper $pathsHelper,
        protected UpdateHelper $updateHelper,
        CoreParametersHelper $coreParametersHelper,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        private RequestStack $requestStack,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    private function getSession(): Session
    {
        $session = $this->requestStack->getSession();
        \assert($session instanceof Session);

        return $session;
    }

    /**
     * @param bool $disableUpdates
     */
    public function setDisableUpdates($disableUpdates): void
    {
        $this->disableUpdates = $disableUpdates;
    }

    /**
     * @return NotificationRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Notification::class);
    }

    /**
     * Write a notification.
     *
     * @param string         $message                 Message of the notification
     * @param string|null    $type                    Optional $type to ID the source of the notification
     * @param bool|true      $isRead                  Add unread indicator
     * @param string|null    $header                  Header for message
     * @param string|null    $iconClass               CSS class for the icon (e.g. ri-eye-line)
     * @param \DateTime|null $datetime                Date the item was created
     * @param User|null      $user                    User object; defaults to current user
     * @param string|null    $deduplicateValue        When supplied, notification will not be added if another notification with tha same $deduplicateValue exists within last 24 hours
     * @param \DateTime|null $deduplicateDateTimeFrom This argument is applied only when $deduplicateValue is supplied. If default deduplication time span (last 24 hours) does not fit your needs you can change it here.
     *                                                E.g. $deduplicateDateTimeFrom = new DateTime('-3 hours') means that the notification is considered duplicate only if there is a notification with the same $deduplicateValue and is not older than 3 hours.
     */
    public function addNotification(
        $message,
        $type = null,
        $isRead = false,
        $header = null,
        $iconClass = null,
        \DateTime $datetime = null,
        User $user = null,
        string $deduplicateValue = null,
        \DateTime $deduplicateDateTimeFrom = null,
    ): void {
        if (null === $user) {
            $user = $this->userHelper->getUser();
        }

        if (null === $user || !$user->getId()) {
            // ensure notifications aren't written for non users
            return;
        }

        if (null !== $deduplicateValue) {
            $deduplicateValue = md5($deduplicateValue);

            if ($this->isDuplicate($user->getId(), $deduplicateValue, $deduplicateDateTimeFrom)) {
                return;
            }
        }

        $notification = new Notification();
        $notification->setType($type);
        $notification->setIsRead($isRead);
        $notification->setHeader(EmojiHelper::toHtml(InputHelper::strict_html($header)));
        $notification->setMessage(EmojiHelper::toHtml(InputHelper::strict_html($message)));
        $notification->setIconClass($iconClass);
        $notification->setUser($user);
        if (null == $datetime) {
            $datetime = new \DateTime();
        }
        $notification->setDateAdded($datetime);
        $notification->setDeduplicate($deduplicateValue);
        $this->saveAndDetachEntity($notification);
    }

    /**
     * Mark notifications read for a user.
     */
    public function markAllRead(): void
    {
        $this->getRepository()->markAllReadForUser($this->userHelper->getUser()->getId());
    }

    /**
     * Clears a notification for a user.
     *
     * @param $id    Notification to clear; will clear all if empty
     * @param $limit Maximum number of notifications to clear if $id is empty
     */
    public function clearNotification($id, $limit = null): void
    {
        $this->getRepository()->clearNotificationsForUser($this->userHelper->getUser()->getId(), $id, $limit);
    }

    /**
     * Get content for notifications.
     *
     * @param bool $includeRead
     * @param int  $limit
     */
    public function getNotificationContent($afterId = null, $includeRead = false, $limit = null): array
    {
        if ($this->userHelper->getUser()->isGuest()) {
            return [[], false, ''];
        }

        $showNewIndicator = false;
        $userId           = ($this->userHelper->getUser()) ? $this->userHelper->getUser()->getId() : 0;

        $notifications = $this->getRepository()->getNotifications($userId, $afterId, $includeRead, null, $limit);

        // determine if the new message indicator should be shown
        foreach ($notifications as $n) {
            if (!$n['isRead']) {
                $showNewIndicator = true;
                break;
            }
        }

        // Check for updates
        $updateMessage = '';
        $newUpdate     = false;

        if (!$this->disableUpdates && $this->userHelper->getUser()->isAdmin()) {
            $updateData = [];
            $cacheFile  = $this->pathsHelper->getSystemPath('cache').'/lastUpdateCheck.txt';

            // check to see when we last checked for an update
            $lastChecked = $this->getSession()->get('mautic.update.checked', 0);

            if (time() - $lastChecked > 3600) {
                $this->getSession()->set('mautic.update.checked', time());

                $updateData = $this->updateHelper->fetchData();
            } elseif (file_exists($cacheFile)) {
                $updateData = json_decode(file_get_contents($cacheFile), true);
            }

            // If the version key is set, we have an update
            if (isset($updateData['version'])) {
                $announcement = $this->translator->trans(
                    'mautic.core.updater.update.announcement_link',
                    ['%announcement%' => $updateData['announcement']]
                );

                $updateMessage = $this->translator->trans(
                    $updateData['message'],
                    ['%version%' => $updateData['version'], '%announcement%' => $announcement]
                );

                $alreadyNotified = $this->getSession()->get('mautic.update.notified');

                if (empty($alreadyNotified) || $alreadyNotified != $updateData['version']) {
                    $newUpdate = true;
                    $this->getSession()->set('mautic.update.notified', $updateData['version']);
                }
            }
        }

        return [$notifications, $showNewIndicator, ['isNew' => $newUpdate, 'message' => $updateMessage]];
    }

    private function isDuplicate(int $userId, string $deduplicate, \DateTime $from = null): bool
    {
        return $this->getRepository()->isDuplicate($userId, $deduplicate, $from ?? new \DateTime('-1 day'));
    }
}
