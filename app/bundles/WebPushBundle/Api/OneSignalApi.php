<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Api;

use Joomla\Http\Response;
use Mautic\WebPushBundle\Exception\MissingApiKeyException;
use Mautic\WebPushBundle\Exception\MissingAppIDException;

class OneSignalApi extends AbstractWebPushApi
{
    /**
     * @var string
     */
    protected $apiUrlBase = 'https://onesignal.com/api/v1';

    /**
     * @param string $endpoint One of "apps", "players", or "notifications"
     * @param string $data JSON encoded array of data to send
     *
     * @return Response
     *
     * @throws MissingAppIDException
     * @throws MissingApiKeyException
     */
    public function send($endpoint, $data)
    {
        $appId = $this->factory->getParameter('webpush_app_id');
        $restApiKey = $this->factory->getParameter('webpush_rest_api_key');

        if (! $restApiKey) {
            throw new MissingApiKeyException;
        }

        if (! array_key_exists('app_id', $data)) {
            if (! $appId) {
                throw new MissingAppIDException;
            }

            $data['app_id'] = $appId;
        }

        return $this->http->post(
            $this->apiUrlBase . $endpoint,
            json_encode($data),
            array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $restApiKey
            )
        );
    }

    /**
     * @param string|array $playerId Player ID as string, or an array of player ID's
     * @param string|array $message Message as string, or lang => message array
     *                              ['en' => 'English Message', 'es' => 'Spanish Message']
     * @param string|array $title Title as string, or lang => title array
     *                            ['en' => 'English Title', 'es' => 'Spanish Title']
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function sendNotification($playerId, $message, $title = '')
    {
        $data = array();

        if (! is_array($playerId)) {
            $playerId = array($playerId);
        }

        $data['include_player_ids'] = $playerId;

        if (! is_array($message)) {
            $message = array("en" => $message);
        }

        $data['contents'] = $message;

        if (! empty($title)) {
            switch(gettype($title))
            {
                case 'string':
                    $title = array('en' => $title);
                    break;
                case 'array':
                default:
                    break;
            }

            $data['headings'] = $title;
        }

        return $this->send('/notifications', $data);
    }
}