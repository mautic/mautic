<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticSocialBundle\Integration;

use Mautic\CoreBundle\Helper\EmojiHelper;

/**
 * Class TwitterIntegration
 */
class TwitterIntegration extends SocialIntegration
{

    /**
     * Used in getUserData to prevent a double user search call with getUserId
     *
     * @var bool
     */
    private $preventDoubleCall = false;

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'Twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority ()
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields ()
    {
        return 'twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures ()
    {
        return array(
            'public_profile',
            'public_activity',
            'share_button'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl ()
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    public function getAuthLoginUrl()
    {
        $url = 'https://api.twitter.com/oauth/authorize';

        // Get request token
        $requestToken = $this->getRequestToken();

        if (isset($requestToken['oauth_token'])) {
            $url .= '?oauth_token='.$requestToken['oauth_token'];
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getRequestTokenUrl()
    {
        return 'https://api.twitter.com/oauth/request_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType ()
    {
        return 'oauth1a';
    }

    /**
     * {@inheritdoc}
     *
     * @param $url
     * @param $parameters
     * @param $method
     * @param $settings
     * @param $authType
     */
    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        // Prevent SSL issues
        $settings['ssl_verifypeer'] = false;

        if (empty($settings['authorize_session'])) {
            // Twitter requires oauth_token_secret to be part of composite key
            $settings['token_secret'] = $this->keys['oauth_token_secret'];

            //Twitter also requires double encoding of parameters in building base string
            $settings['double_encode_basestring_parameters'] = true;
        }

        return parent::prepareRequest($url, $parameters, $method, $settings, $authType);
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl ($endpoint)
    {
        return "https://api.twitter.com/1.1/$endpoint.json";
    }

    /**
     * {@inheritdoc}
     */
    public function getUserData ($identifier, &$socialCache)
    {
        //tell getUserId to return a user array if it obtains it
        $this->preventDoubleCall = true;

        if ($id = $this->getUserId($identifier, $socialCache)) {
            if (is_array($id)) {
                //getUserId has alread obtained the data
                $data = $id;
            } else {
                $data = $this->makeRequest($this->getApiUrl("users/lookup"), array(
                    'user_id'          => $id,
                    'include_entities' => 'false'
                ));
            }

            if (isset($data[0])) {
                $info                  = $this->matchUpData($data[0]);
                $info['profileHandle'] = $data[0]['screen_name'];
                //remove the size variant
                $image                = $data[0]['profile_image_url_https'];
                $image                = str_replace(array('_normal', '_bigger', '_mini'), '', $image);
                $info['profileImage'] = $image;

                $socialCache['profile'] = $info;
            }
            $this->preventDoubleCall = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicActivity ($identifier, &$socialCache)
    {
        if ($id = $this->getUserId($identifier, $socialCache)) {
            //due to the way Twitter filters, get more than 10 tweets
            $data = $this->makeRequest($this->getApiUrl("/statuses/user_timeline"), array(
                'user_id'         => $id,
                'exclude_replies' => 'true',
                'count'           => 25,
                'trim_user'       => 'true'
            ));

            if (!empty($data) && count($data)) {
                $socialCache['has']['activity'] = true;
                $socialCache['activity']        = array(
                    'tweets' => array(),
                    'photos' => array(),
                    'tags'   => array()
                );

                foreach ($data as $k => $d) {
                    if ($k == 10) {
                        break;
                    }

                    $tweet = array(
                        'tweet'       => EmojiHelper::toHtml($d['text']),
                        'url'         => "https://twitter.com/{$id}/status/{$d['id']}",
                        'coordinates' => $d['coordinates'],
                        'published'   => $d['created_at'],
                    );

                    $socialCache['activity']['tweets'][] = $tweet;

                    //images
                    if (isset($d['entities']['media'])) {
                        foreach ($d['entities']['media'] as $m) {
                            if ($m['type'] == 'photo') {
                                $photo = array(
                                    'url' => (isset($m['media_url_https']) ? $m['media_url_https'] : $m['media_url'])
                                );

                                $socialCache['activity']['photos'][] = $photo;
                            }
                        }
                    }

                    //hastags
                    if (isset($d['entities']['hashtags'])) {
                        foreach ($d['entities']['hashtags'] as $h) {
                            if (isset($socialCache['activity']['tags'][$h['text']])) {
                                $socialCache['activity']['tags'][$h['text']]['count']++;
                            } else {
                                $socialCache['activity']['tags'][$h['text']] = array(
                                    'count' => 1,
                                    'url'   => 'https://twitter.com/search?q=%23' . $h['text']
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = array())
    {
        return array(
            "profileHandle" => array("type" => "string"),
            "name"          => array("type" => "string"),
            "location"      => array("type" => "string"),
            "description"   => array("type" => "string"),
            "url"           => array("type" => "string"),
            "time_zone"     => array("type" => "string"),
            "lang"          => array("type" => "string")
        );
    }

    /**
     * Gets the ID of the user for the network
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return mixed|null
     */
    public function getUserId ($identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        // note twitter requires params to be passed as strings
        $data = $this->makeRequest($this->getApiUrl("users/lookup"), array(
            'screen_name'      => $identifier,
            'include_entities' => 'false',

        ));

        if (isset($data[0])) {
            $socialCache['id'] = $data[0]['id'];

            //return the entire data set if the function has been called from getUserData()
            return ($this->preventDoubleCall) ? $data : $socialCache['id'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanIdentifier ($identifier)
    {
        if (strpos($identifier, 'http') !== false) {
            //extract the handle
            $identifier = substr(strrchr(rtrim($identifier, '/'), '/'), 1);
        }

        return urlencode($identifier);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse ($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            parse_str($data, $parsed);

            return $parsed;
        }
        return json_decode($data, true);
    }
}