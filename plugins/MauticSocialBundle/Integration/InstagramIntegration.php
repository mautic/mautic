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

/**
 * Class InstagramIntegration.
 */
class InstagramIntegration extends SocialIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Instagram';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return [
            'public_profile',
            'public_activity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return 'instagram';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl()
    {
        return 'https://api.instagram.com/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint)
    {
        return "https://api.instagram.com/v1/$endpoint";
    }

    /**
     * {@inheritdoc}
     */
    public function getUserData($identifier, &$socialCache)
    {
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            $url  = $this->getApiUrl('users/'.$id);
            $data = $this->makeRequest($url);

            if (isset($data->data)) {
                $info = $this->matchUpData($data->data);

                $info['profileImage']   = $data->data->profile_picture;
                $info['profileHandle']  = $data->data->username;
                $socialCache['profile'] = $info;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        $socialCache['has']['activity'] = false;
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            //get more than 10 so we can weed out videos
            $data = $this->makeRequest($this->getApiUrl("users/$id/media/recent"), ['count' => 20]);

            $socialCache['activity'] = [
                'photos' => [],
                'tags'   => [],
            ];

            if (!empty($data->data)) {
                $socialCache['has']['activity'] = true;
                $count                          = 1;
                foreach ($data->data as $m) {
                    if ($count > 10) {
                        break;
                    }

                    if ('image' == $m->type) {
                        $socialCache['activity']['photos'][] = [
                            'url' => $m->images->standard_resolution->url,
                        ];

                        if (!empty($m->caption->text)) {
                            preg_match_all("/#(\w+)/", $m->caption->text, $tags);
                            if (!empty($tags[1])) {
                                foreach ($tags[1] as $tag) {
                                    if (isset($socialCache['activity']['tags'][$tag])) {
                                        ++$socialCache['activity']['tags'][$tag]['count'];
                                    } else {
                                        $socialCache['activity']['tags'][$tag] = [
                                            'count' => 1,
                                            'url'   => 'http://searchinstagram.com/'.$tag,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    ++$count;
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
            'full_name' => ['type' => 'string'],
            'bio'       => ['type' => 'string'],
            'website'   => ['type' => 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    private function getContactUserId(&$identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        $data = $this->makeRequest($this->getApiUrl('users/search'), ['q' => $identifier]);

        if (!empty($data->data)) {
            foreach ($data->data as $user) {
                //its possible that instagram may return multiple users if the username is a base of another
                //for example, search for alan may return alanh, alanhartless, etc
                if (strtolower($user->username) == strtolower($identifier)) {
                    $socialCache['id'] = $user->id;
                    break;
                }
            }

            return (!empty($socialCache['id'])) ? $socialCache['id'] : false;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return null;
    }
}
