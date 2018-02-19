<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Api;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Helper\NotificationUploader;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;

abstract class AbstractNotificationApi
{
    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var Http
     */
    protected $http;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var NotificationUploader
     */
    protected $notificationUploader;

    /**
     * AbstractNotificationApi constructor.
     *
     * @param MauticFactory        $factory
     * @param Http                 $http
     * @param TrackableModel       $trackableModel
     * @param IntegrationHelper    $integrationHelper
     * @param NotificationUploader $notificationUploader
     */
    public function __construct(MauticFactory $factory, Http $http, TrackableModel $trackableModel, IntegrationHelper $integrationHelper, NotificationUploader $notificationUploader)
    {
        $this->factory              = $factory;
        $this->http                 = $http;
        $this->trackableModel       = $trackableModel;
        $this->integrationHelper    = $integrationHelper;
        $this->notificationUploader = $notificationUploader;
    }

    /**
     * @param string $endpoint One of "apps", "players", or "notifications"
     * @param string $data     JSON encoded array of data to send
     *
     * @return Response
     */
    abstract public function send($endpoint, $data);

    /**
     * @param              $id
     * @param Notification $sendNotification
     * @param Notification $notification
     *
     * @return mixed
     */
    abstract public function sendNotification($id, Notification $sendNotification, Notification $notification);

    /**
     * Convert a non-tracked url to a tracked url.
     *
     * @param string $url
     * @param array  $clickthrough
     *
     * @return string
     */
    public function convertToTrackedUrl($url, array $clickthrough, Notification $notification)
    {
        /* @var \Mautic\PageBundle\Entity\Redirect $redirect */
        $trackable = $this->trackableModel->getTrackableByUrl($url, 'notification', $clickthrough['notification']);

        return $this->trackableModel->generateTrackableUrl($trackable, $clickthrough, [], $notification->getUtmTags());
    }
}
