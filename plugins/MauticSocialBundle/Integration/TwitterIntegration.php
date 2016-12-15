<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

use Mautic\CoreBundle\Helper\EmojiHelper;

/**
 * Class TwitterIntegration.
 */
class TwitterIntegration extends SocialIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 5000;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return 'twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return [
            'public_profile',
            'public_activity',
            'share_button',
            'login_button',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    /**
     * @return string
     */
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
    public function getAuthenticationType()
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

        if (empty($settings['authorize_session']) && $authType != 'access_token') {
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
    public function getApiUrl($endpoint)
    {
        return "https://api.twitter.com/1.1/$endpoint.json";
    }

    /**
     * {@inheritdoc}
     */
    public function getUserData($identifier, &$socialCache)
    {
        $accessToken = $this->getContactAccessToken($socialCache);

        // Contact SSO
        if (isset($accessToken['oauth_token'])) {
            // note twitter requires params to be passed as strings
            $data = $this->makeRequest(
                $this->getApiUrl('account/verify_credentials'),
                [
                    'include_email'    => 'true',
                    'include_entities' => 'false',
                    'oauth_token'      => $accessToken['oauth_token'],
                ],
                'GET',
                ['auth_type' => 'oauth1a']
            );
        }

        if (empty($data)) {
            // Try via user lookup
            $data = $this->makeRequest(
                $this->getApiUrl('users/lookup'),
                [
                    'screen_name'      => $this->cleanIdentifier($identifier),
                    'include_entities' => 'false',
                ]
            );

            if (isset($data[0])) {
                $data = $data[0];
            }
        }

        if (isset($data['id'])) {
            $socialCache['id'] = $data['id'];

            $info                  = $this->matchUpData($data);
            $info['profileHandle'] = $data['screen_name'];
            //remove the size variant
            $image                = $data['profile_image_url_https'];
            $image                = str_replace(['_normal', '_bigger', '_mini'], '', $image);
            $info['profileImage'] = $image;

            $socialCache['profile']     = $info;
            $socialCache['lastRefresh'] = new \DateTime();

            $this->getMauticLead($info, $this->persistNewLead, $socialCache, $identifier);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        if (!isset($socialCache['id'])) {
            $this->getUserData($identifier, $socialCache);

            if (!isset($socialCache['id'])) {
                return;
            }
        }

        $id = $socialCache['id'];

        //due to the way Twitter filters, get more than 10 tweets
        $data = $this->makeRequest($this->getApiUrl('/statuses/user_timeline'), [
            'user_id'         => $id,
            'exclude_replies' => 'true',
            'count'           => 25,
            'trim_user'       => 'true',
        ]);

        if (!empty($data) && count($data)) {
            $socialCache['has']['activity'] = true;
            $socialCache['activity']        = [
                'tweets' => [],
                'photos' => [],
                'tags'   => [],
            ];

            foreach ($data as $k => $d) {
                if ($k == 10) {
                    break;
                }

                $tweet = [
                    'tweet'       => EmojiHelper::toHtml($d['text']),
                    'url'         => "https://twitter.com/{$id}/status/{$d['id']}",
                    'coordinates' => $d['coordinates'],
                    'published'   => $d['created_at'],
                ];

                $socialCache['activity']['tweets'][] = $tweet;

                //images
                if (isset($d['entities']['media'])) {
                    foreach ($d['entities']['media'] as $m) {
                        if ($m['type'] == 'photo') {
                            $photo = [
                                'url' => (isset($m['media_url_https']) ? $m['media_url_https'] : $m['media_url']),
                            ];

                            $socialCache['activity']['photos'][] = $photo;
                        }
                    }
                }

                //hastags
                if (isset($d['entities']['hashtags'])) {
                    foreach ($d['entities']['hashtags'] as $h) {
                        if (isset($socialCache['activity']['tags'][$h['text']])) {
                            ++$socialCache['activity']['tags'][$h['text']]['count'];
                        } else {
                            $socialCache['activity']['tags'][$h['text']] = [
                                'count' => 1,
                                'url'   => 'https://twitter.com/search?q=%23'.$h['text'],
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'profileHandle' => ['type' => 'string'],
            'name'          => ['type' => 'string'],
            'location'      => ['type' => 'string'],
            'description'   => ['type' => 'string'],
            'url'           => ['type' => 'string'],
            'time_zone'     => ['type' => 'string'],
            'lang'          => ['type' => 'string'],
            'email'         => ['type' => 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanIdentifier($identifier)
    {
        if (preg_match('#https?://twitter.com/(.*?)(/.*?|$)#i', $identifier, $match)) {
            //extract the handle
            $identifier = $match[1];
        } elseif (substr($identifier, 0, 1) == '@') {
            $identifier = substr($identifier, 1);
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
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            parse_str($data, $parsed);

            return $parsed;
        }

        return json_decode($data, true);
    }
}
