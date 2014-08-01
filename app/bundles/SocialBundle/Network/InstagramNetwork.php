<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

class InstagramNetwork extends AbstractNetwork
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Instagram';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array(
            'public_profile',
            'public_activity'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getIdentifierField()
    {
        return 'instagram';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://api.instagram.com/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|string
     */
    public function getAccessTokenUrl()
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'clientId'      => 'mautic.social.keyfield.clientid',
            'clientSecret'  => 'mautic.social.keyfield.clientsecret'
        );
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint)
    {
        $keys  = $this->settings->getApiKeys();
        $token = (isset($keys['access_token'])) ? $keys['access_token'] : '';
        return "https://api.instagram.com/v1/$endpoint?access_token=$token";
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array|void
     */
    public function getUserData($identifier, &$socialCache)
    {
        if ($id = $this->getUserId($identifier, $socialCache)) {
            $url  = $this->getApiUrl('users/'.$id) . "&q=$identifier";
            $data = $this->makeCall($url);

            if (isset($data->data)) {
                $info = $this->matchUpData($data->data);

                $info['profileImage']   = $data->data->profile_picture;
                $info['profileHandle']  = $data->data->username;
                $socialCache['profile'] = $info;
            }
        }

        if (empty($socialCache['profile'])) {
            //populate empty data
            $empty = new \stdClass();
            $socialCache['profile'] = $this->matchUpData($empty);
            $socialCache['profile']['profileHandle'] = "";
            $socialCache['profile']['profileImage']  = $this->factory->getAssetsHelper()->getUrl('assets/images/avatar.png');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        if ($id = $this->getUserId($identifier, $socialCache)) {
            //get more than 10 so we can weed out videos
            $url  = $this->getApiUrl("users/$id/media/recent"). "&count=20";
            $data = $this->makeCall($url);

            $socialCache['activity'] = array(
                "photos" => array(),
                "tags"   => array()
            );

            if (!empty($data->data)) {
                $count = 1;
                foreach ($data->data as $m) {
                    if ($count > 10) break;

                    if ($m->type == 'image') {
                        $socialCache['activity']['photos'][] = array(
                            'url' => $m->images->standard_resolution->url
                        );

                        if (!empty($m->caption->text)) {
                            preg_match_all("/#(\w+)/", $m->caption->text, $tags);
                            if (!empty($tags[1])) {
                                foreach ($tags[1] as $tag) {
                                    if (isset($socialCache['activity']['tags'][$tag])) {
                                        $socialCache['activity']['tags'][$tag]['count']++;
                                    } else {
                                        $socialCache['activity']['tags'][$tag] = array(
                                            'count' => 1,
                                            'url'   => 'http://searchinstagram.com/' . $tag
                                        );
                                    }
                                }
                            }
                        }
                    }
                    $count++;
                }
            }
        }

        if (empty($socialCache['activity'])) {
            $socialCache['activity'] = array(
                "photos" => array(),
                "tags"   => array()
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return mixed|null
     */
    public function getUserId($identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        $identifier = $this->cleanIdentifier($identifier);

        $url  = $this->getApiUrl('users/search') . "&q=$identifier";
        $data = $this->makeCall($url);

        if (!empty($data->data)) {
            foreach ($data->data as $user) {
                //its possible that instagram may return multiple users if the username is a base of another
                //for example, search for alan may return alanh, alanhartless, etc
                if ($user->username == $identifier) {
                    $socialCache['id'] = $user->id;
                    break;
                }
            }

            return (!empty($socialCache['id'])) ? $socialCache['id'] : false;
        }
    }

    public function getAvailableFields()
    {
        return array(
            "full_name" => array("type" => "string"),
            "bio"       => array("type" => "string"),
            "webiste"   => array("type" => "string")
        );
    }
}