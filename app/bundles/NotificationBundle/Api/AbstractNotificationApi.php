<?php

namespace Mautic\NotificationBundle\Api;

use GuzzleHttp\Client;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Helper\NotificationUploader;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractNotificationApi
{
    protected Client $http;
    protected TrackableModel $trackableModel;
    protected IntegrationHelper $integrationHelper;

    /**
     * @var NotificationUploader
     */
    protected $notificationUploader;

    /**
     * AbstractNotificationApi constructor.
     */
    public function __construct(Client $http, TrackableModel $trackableModel, IntegrationHelper $integrationHelper, NotificationUploader $notificationUpload
er)
    {
        $this->http              = $http;
        $this->trackableModel    = $trackableModel;
        $this->integrationHelper = $integrationHelper;
        $this->notificationUploader = $notificationUploader;
    }

    /**
     * @param string $endpoint One of "apps", "players", or "notifications"
     * @param array  $data     Array of data to send
     */
    abstract public function send(string $endpoint, array $data): ResponseInterface;

    /**
     * @param $id
     *
     * @return mixed
     */
    abstract public function sendNotification($id, Notification $sendNotification, Notification $notification);

    /**
     * Convert a non-tracked url to a tracked url.
     *
     * @param string $url
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
