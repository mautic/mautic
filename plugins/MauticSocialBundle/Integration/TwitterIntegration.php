<?php

namespace MauticPlugin\MauticSocialBundle\Integration;

use MauticPlugin\MauticSocialBundle\Form\Type\TwitterType;

class TwitterIntegration extends SocialIntegration
{
    public const NAME = 'Twitter';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPriority(): int
    {
        return 5000;
    }

    public function getIdentifierFields(): string
    {
        return 'twitter';
    }

    public function getSupportedFeatures(): array
    {
        return [
            'public_profile',
            'public_activity',
            'share_button',
            'login_button',
        ];
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    public function getAuthLoginUrl(): string
    {
        $url = 'https://api.twitter.com/oauth/authorize';

        // Get request token
        $requestToken = $this->getRequestToken();

        if (isset($requestToken['oauth_token'])) {
            $url .= '?oauth_token='.$requestToken['oauth_token'];
        }

        return $url;
    }

    public function getRequestTokenUrl(): string
    {
        return 'https://api.twitter.com/oauth/request_token';
    }

    public function getAuthenticationType(): string
    {
        return 'oauth1a';
    }

    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        // Prevent SSL issues
        $settings['ssl_verifypeer'] = false;

        if (empty($settings['authorize_session']) && 'access_token' != $authType) {
            // Twitter requires oauth_token_secret to be part of composite key
            if (isset($this->keys['oauth_token_secret'])) {
                $settings['token_secret'] = $this->keys['oauth_token_secret'];
            }

            // Twitter also requires double encoding of parameters in building base string
            $settings['double_encode_basestring_parameters'] = true;
        }

        return parent::prepareRequest($url, $parameters, $method, $settings, $authType);
    }

    public function getApiUrl($endpoint): string
    {
        return "https://api.twitter.com/1.1/$endpoint.json";
    }

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
            // remove the size variant
            $image                = $data['profile_image_url_https'];
            $image                = str_replace(['_normal', '_bigger', '_mini'], '', $image);
            $info['profileImage'] = $image;

            $socialCache['profile']     = $info;
            $socialCache['lastRefresh'] = new \DateTime();

            $this->getMauticLead($info, $this->persistNewLead, $socialCache, $identifier);
        }

        return $data;
    }

    public function getPublicActivity($identifier, &$socialCache): void
    {
        if (!isset($socialCache['id'])) {
            $this->getUserData($identifier, $socialCache);

            if (!isset($socialCache['id'])) {
                return;
            }
        }

        $id = $socialCache['id'];

        // due to the way Twitter filters, get more than 10 tweets
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
                if (10 == $k) {
                    break;
                }

                $tweet = [
                    'tweet'       => $d['text'],
                    'url'         => "https://twitter.com/{$id}/status/{$d['id']}",
                    'coordinates' => $d['coordinates'],
                    'published'   => $d['created_at'],
                ];

                $socialCache['activity']['tweets'][] = $tweet;

                // images
                if (isset($d['entities']['media'])) {
                    foreach ($d['entities']['media'] as $m) {
                        if ('photo' == $m['type']) {
                            $photo = [
                                'url' => ($m['media_url_https'] ?? $m['media_url']),
                            ];

                            $socialCache['activity']['photos'][] = $photo;
                        }
                    }
                }

                // hastags
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

    public function getAvailableLeadFields($settings = []): array
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

    public function cleanIdentifier($identifier): string
    {
        if (preg_match('#https?://twitter.com/(.*?)(/.*?|$)#i', $identifier, $match)) {
            // extract the handle
            $identifier = $match[1];
        } elseif (str_starts_with($identifier, '@')) {
            $identifier = substr($identifier, 1);
        }

        return urlencode($identifier);
    }

    /**
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

    public function getFormType(): string
    {
        return TwitterType::class;
    }
}
