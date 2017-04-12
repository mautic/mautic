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

use Joomla\Http\Response;
use Mautic\NotificationBundle\Exception\MissingApiKeyException;
use Mautic\NotificationBundle\Exception\MissingAppIDException;

class OneSignalApi extends AbstractNotificationApi
{
    /**
     * @var string
     */
    protected $apiUrlBase = 'https://onesignal.com/api/v1';

    /**
     * @param string $endpoint One of "apps", "players", or "notifications"
     * @param string $data     JSON encoded array of data to send
     *
     * @return Response
     *
     * @throws MissingAppIDException
     * @throws MissingApiKeyException
     */
    public function send($endpoint, $data)
    {
        $apiKeys    = $this->integrationHelper->getIntegrationObject('OneSignal')->getKeys();
        $appId      = $apiKeys['app_id'];
        $restApiKey = $apiKeys['rest_api_key'];

        if (!$restApiKey) {
            throw new MissingApiKeyException();
        }

        if (!array_key_exists('app_id', $data)) {
            if (!$appId) {
                throw new MissingAppIDException();
            }

            $data['app_id'] = $appId;
        }

        return $this->http->post(
            $this->apiUrlBase.$endpoint,
            json_encode($data),
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic '.$restApiKey,
            ]
        );
    }

    /**
     * @param string|array $playerId Player ID as string, or an array of player ID's
     * @param string|array $message  Message as string, or lang => message array
     *                               ['en' => 'English Message', 'es' => 'Spanish Message']
     * @param string|array $title    Title as string, or lang => title array
     *                               ['en' => 'English Title', 'es' => 'Spanish Title']
     * @param string       $url      The URL where the user should be sent when clicking the notification
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function sendNotification($playerId, $message, $title = null, $url = null, $button = null)
    {
        $data = [];

        $buttonId = $title;

        if (!is_array($playerId)) {
            $playerId = [$playerId];
        }

        $data['include_player_ids'] = $playerId;

        if (!is_array($message)) {
            $message = ['en' => $message];
        }

        $data['contents'] = $message;

        if (!empty($title)) {
            if (!is_array($title)) {
                $title = ['en' => $title];
            }

            $data['headings'] = $title;
        }

        if ($url) {
            $data['url'] = $url;
        }

        if ($button && $url) {
            $data['web_buttons'][] = ['id' => $buttonId, 'text' => $button, 'url' => $url];
        }

        return $this->send('/notifications', $data);
    }
}
