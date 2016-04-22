<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

/**
 * Class GooglePlusIntegration
 */
class GooglePlusIntegration extends SocialIntegration
{

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'GooglePlus';
    }

    public function getDisplayName()
    {
        return 'Google+';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return array(
            'googleplus',
            'email'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return array(
            'public_activity',
            'public_profile',
            'share_button'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUserData($identifier, &$socialCache)
    {

        if(!isset($identifier['googleplus']) || $identifier['googleplus']===null){
            $identifier['googleplus'] = "people/me";
        }
        $access_token = $this->factory->getSession()->get($this->getName().'_tokenResponse');

        if(isset($access_token['access_token'])) {
            $identifier['access_token'] = $access_token['access_token'];

            $this->preventDoubleCall = true;

            if ($userid = $this->getUserId($identifier, $socialCache)) {
                $url  = $this->getApiUrl("people/{$userid}");
                $data = $this->makeRequest(
                    $url,
                    array('access_token' => $identifier['access_token']),
                    'GET',
                    array('auth_type' => 'access_token')
                );

                if (is_object($data) && !isset($data->error)) {
                    $info = $this->matchUpData($data);

                    if (isset($data->url)) {
                        preg_match("/plus.google.com\/(.*?)($|\/)/", $data->url, $matches);
                        $info['profileHandle'] = $matches[1];
                    }

                    if (isset($data->image->url)) {
                        //remove the size from the end
                        $image                = $data->image->url;
                        $image                = preg_replace('/\?.*/', '', $image);
                        $info["profileImage"] = $image;
                    }
                    if (!empty($info)) {
                        $socialCache[$this->getName()]['profile']     = $info;
                        $socialCache[$this->getName()]['lastRefresh'] = new \DateTime();
                        $socialCache[$this->getName()]['accessToken'] = $this->encryptApiKeys($access_token);

                        $this->getMauticLead($info, true, $socialCache);
                    }

                    return $data;

                    $this->preventDoubleCall = false;
                }
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        if ($id = $this->getUserId($identifier, $socialCache)) {
            $data = $this->makeRequest($this->getApiUrl("people/$id/activities/public"), array('maxResults' => 10));

            if (!empty($data) && isset($data->items) && count($data->items)) {
                $socialCache[$this->getName()]['activity'] = array(
                    'posts'  => array(),
                    'photos' => array(),
                    'tags'   => array()
                );
                foreach ($data->items as $page) {
                    $post                               = array(
                        'title'     => $page->title,
                        'url'       => $page->url,
                        'published' => $page->published,
                        'updated'   => $page->updated
                    );
                    $socialCache[$this->getName()]['activity']['posts'][] = $post;

                    //extract hashtags from content
                    if (isset($page->object->content)) {
                        preg_match_all(
                            '/\<a rel="nofollow" class="ot-hashtag" href="(.*?)">#(.*?)\<\/a>/',
                            $page->object->content,
                            $tags
                        );
                        if (!empty($tags[2])) {
                            foreach ($tags[2] as $k => $tag) {
                                if (isset($socialCache[$this->getName()]['activity']['tags'][$tag])) {
                                    $socialCache[$this->getName()]['activity']['tags'][$tag]['count']++;
                                } else {
                                    $socialCache[$this->getName()]['activity']['tags'][$tag] = array(
                                        'count' => 1,
                                        'url'   => $tags[1][$k]
                                    );
                                }
                            }
                        }
                    }

                    //images
                    if (isset($page->object->attachments)) {
                        foreach ($page->object->attachments as $a) {
                            //use proxy image so that its SSL
                            if (isset($a->image)) {
                                $url = $a->image->url;

                                //remove size limits
                                if (isset($a->image->width)) {
                                    $pos = strpos($url, '=w');
                                    $url = substr($url, 0, $pos);
                                }

                                $photo                               = array(
                                    'url' => $url
                                );
                                $socialCache[$this->getName()]['activity']['photos'][] = $photo;
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
            "profileHandle"      => array("type" => "string"),
            "nickname"           => array("type" => "string"),
            "occupation"         => array("type" => "string"),
            "skills"             => array("type" => "string"),
            "birthday"           => array("type" => "string"),
            "gender"             => array("type" => "string"),
            'url'                => array("type" => "string"),
            "urls"               => array(
                "type"   => "array_object",
                "fields" => array(
                    "otherProfile",
                    "contributor",
                    "website",
                    "other"
                )
            ),
            "displayName"        => array("type" => "string"),
            "name"               => array(
                "type"   => "object",
                "fields" => array(
                    "familyName",
                    "givenName",
                    "middleName",
                    "honorificPrefix",
                    "honorificSuffix"
                )
            ),
            "emails"             => array(
                "type"   => "array_object",
                "fields" => array(
                    "account"
                )
            ),
            "tagline"            => array("type" => "string"),
            "braggingRights"     => array("type" => "string"),
            "aboutMe"            => array("type" => "string"),
            "currentLocation"    => array("type" => "string"),
            "relationshipStatus" => array("type" => "string"),
            "organizations"      => array(
                "type"   => "array_object",
                "fields" => array(
                    "work",
                    "home"
                )
            ),
            "placesLived"        => array(
                "type" => "array_object"
            ),
            "language"           => array("type" => "string"),
            "ageRange"           => array(
                "type"   => "object",
                "fields" => array(
                    "min",
                    "max"
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return array(
            'key'           => 'mautic.integration.keyfield.api',
            'client_id'     => 'mautic.integration.keyfield.clientid',
            'client_secret' => 'mautic.integration.keyfield.clientsecret'
        );
    }

    /**
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'key';
    }
    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl()
    {
        return 'https://accounts.google.com/o/oauth2/auth';
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }
    /**
     * @param $endpoint
     *
     * @return string
     */
    public function getApiUrl($endpoint)
    {
        return "https://www.googleapis.com/plus/v1/$endpoint";
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId($identifier, &$socialCache)
    {
        if (!empty($socialCache[$this->getName()]['id'])) {
            return $socialCache[$this->getName()]['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        if (!is_array($identifier)) {
            $identifier = array($identifier);
        }
        if(!isset($identifier['access_token'])){
            return;
        }


        $data = $this->makeRequest($this->getApiUrl('people/me'), array('access_token'=>$identifier['access_token']),'GET',array('auth_type'=>'access_token'));

        if (!empty($data) && isset($data->id) && count($data->id)) {
            $socialCache[$this->getName()]['id'] = $data->id;

            return $socialCache[$this->getName()]['id'];
        }

        return false;
    }
}
