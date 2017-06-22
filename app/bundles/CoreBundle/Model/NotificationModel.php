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

use Debril\RssAtomBundle\Protocol\FeedReader;
use Debril\RssAtomBundle\Protocol\Parser\FeedContent;
use Debril\RssAtomBundle\Protocol\Parser\Item;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class NotificationModel.
 */
class NotificationModel extends FormModel
{
    /**
     * @var bool
     */
    protected $disableUpdates;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var UpdateHelper
     */
    protected $updateHelper;

    /**
     * @var FeedReader
     */
    protected $rssReader;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * NotificationModel constructor.
     *
     * @param PathsHelper          $pathsHelper
     * @param UpdateHelper         $updateHelper
     * @param FeedReader           $rssReader
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(
        PathsHelper $pathsHelper,
        UpdateHelper $updateHelper,
        FeedReader $rssReader,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->pathsHelper          = $pathsHelper;
        $this->updateHelper         = $updateHelper;
        $this->rssReader            = $rssReader;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param $disableUpdates
     */
    public function setDisableUpdates($disableUpdates)
    {
        $this->disableUpdates = $disableUpdates;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CoreBundle\Entity\NotificationRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCoreBundle:Notification');
    }

    /**
     * Write a notification.
     *
     * @param string    $message   Message of the notification
     * @param string    $type      Optional $type to ID the source of the notification
     * @param bool|true $isRead    Add unread indicator
     * @param string    $header    Header for message
     * @param string    $iconClass Font Awesome CSS class for the icon (e.g. fa-eye)
     * @param \DateTime $datetime  Date the item was created
     * @param User|null $user      User object; defaults to current user
     */
    public function addNotification(
        $message,
        $type = null,
        $isRead = false,
        $header = null,
        $iconClass = null,
        \DateTime $datetime = null,
        User $user = null
    ) {
        if ($user === null) {
            $user = $this->userHelper->getUser();
        }

        if ($user === null || !$user->getId()) {
            //ensure notifications aren't written for non users
            return;
        }

        $notification = new Notification();
        $notification->setType($type);
        $notification->setIsRead($isRead);
        $notification->setHeader(EmojiHelper::toHtml(InputHelper::strict_html($header)));
        $notification->setMessage(EmojiHelper::toHtml(InputHelper::strict_html($message)));
        $notification->setIconClass($iconClass);
        $notification->setUser($user);
        if ($datetime == null) {
            $datetime = new \DateTime();
        }
        $notification->setDateAdded($datetime);
        $this->saveAndDetachEntity($notification);
    }

    /**
     * Mark notifications read for a user.
     */
    public function markAllRead()
    {
        $this->getRepository()->markAllReadForUser($this->userHelper->getUser()->getId());
    }

    /**
     * Clears a notification for a user.
     *
     * @param $id       Notification to clear; will clear all if empty
     * @param $limit    Maximum number of notifications to clear if $id is empty
     */
    public function clearNotification($id, $limit = null)
    {
        $this->getRepository()->clearNotificationsForUser($this->userHelper->getUser()->getId(), $id, $limit);
    }

    /**
     * Get content for notifications.
     *
     * @param null $afterId
     * @param bool $includeRead
     * @param int  $limit
     *
     * @return array
     */
    public function getNotificationContent($afterId = null, $includeRead = false, $limit = null)
    {
        if ($this->userHelper->getUser()->isGuest) {
            return [[], false, ''];
        }

        $this->updateUpstreamNotifications();

        $showNewIndicator = false;
        $userId           = ($this->userHelper->getUser()) ? $this->userHelper->getUser()->getId() : 0;

        $notifications = $this->getRepository()->getNotifications($userId, $afterId, $includeRead, null, $limit);

        //determine if the new message indicator should be shown
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

            //check to see when we last checked for an update
            $lastChecked = $this->session->get('mautic.update.checked', 0);

            if (time() - $lastChecked > 3600) {
                $this->session->set('mautic.update.checked', time());

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

                $alreadyNotified = $this->session->get('mautic.update.notified');

                if (empty($alreadyNotified) || $alreadyNotified != $updateData['version']) {
                    $newUpdate = true;
                    $this->session->set('mautic.update.notified', $updateData['version']);
                }
            }
        }

        return [$notifications, $showNewIndicator, ['isNew' => $newUpdate, 'message' => $updateMessage]];
    }

    /**
     * Fetch upstream notifications via RSS.
     */
    public function updateUpstreamNotifications()
    {
        $url = $this->coreParametersHelper->getParameter('rss_notification_url');

        if (empty($url)) {
            return;
        }

        //check to see when we last checked for an update
        $lastChecked = $this->session->get('mautic.upstream.checked', 0);

        if (time() - $lastChecked > 3600) {
            $this->session->set('mautic.upstream.checked', time());
            $lastDate = $this->getRepository()->getUpstreamLastDate();

            try {
                /** @var FeedContent $feed */
                $feed = $this->rssReader->getFeedContent($url, $lastDate);

                /** @var Item $item */
                foreach ($feed->getItems() as $item) {
                    $description = $item->getDescription();
                    if (mb_strlen(strip_tags($description)) > 300) {
                        $description = mb_substr(strip_tags($description), 0, 300);
                        $description .= '... <a href="'.$item->getLink().'" target="_blank">'.$this->translator->trans(
                                'mautic.core.notification.read_more'
                            ).'</a>';
                    }
                    $header = $item->getTitle();

                    $this->addNotification($description, 'upstream', false, ($header) ? $header : null, 'fa-bullhorn');
                }
            } catch (\Exception $exception) {
                $this->logger->addWarning($exception->getMessage());
            }
        }
    }
}
