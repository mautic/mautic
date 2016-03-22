<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

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
        if ($userid = $this->getUserId($identifier, $socialCache)) {
            $url  = $this->getApiUrl("people/{$userid}");
            $data = $this->makeRequest($url);
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
            $socialCache['profile'] = $info;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        if ($id = $this->getUserId($identifier, $socialCache)) {
            $data = $this->makeRequest($this->getApiUrl("people/$id/activities/public"), array('maxResults' => 10));

            if (!empty($data) && isset($data->items) && count($data->items)) {
                $socialCache['activity'] = array(
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
                    $socialCache['activity']['posts'][] = $post;

                    //extract hashtags from content
                    if (isset($page->object->content)) {
                        preg_match_all(
                            '/\<a rel="nofollow" class="ot-hashtag" href="(.*?)">#(.*?)\<\/a>/',
                            $page->object->content,
                            $tags
                        );
                        if (!empty($tags[2])) {
                            foreach ($tags[2] as $k => $tag) {
                                if (isset($socialCache['activity']['tags'][$tag])) {
                                    $socialCache['activity']['tags'][$tag]['count']++;
                                } else {
                                    $socialCache['activity']['tags'][$tag] = array(
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
                                $socialCache['activity']['photos'][] = $photo;
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
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {

        return 'key';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'key';
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
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        if (!is_array($identifier)) {
            $identifier = array($identifier);
        }

        foreach ($identifier as $type => $id) {
            if (empty($id)) {
                continue;
            }
            if ($type == 'googleplus' && is_numeric($id)) {
                //this is a google user ID
                $socialCache['id'] = $id;

                return $id;
            }

            $data = $this->makeRequest($this->getApiUrl('people'), array('query' => $id));

            if (!empty($data) && isset($data->items) && count($data->items)) {
                $socialCache['id'] = $data->items[0]->id;

                return $socialCache['id'];
            }
        }

        return false;
    }
}
