<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;

class GooglePlusNetwork extends AbstractNetwork
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'GooglePlus';
    }

    /**
     * {@inheritdoc}
     *
     * @return int|mixed
     */
    public function getPriority()
    {
        return 1;
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getIdentifierField()
    {
        return array(
            'googleplus',
            'email'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return array
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
     *
     * @param $identifier
     * @param $socialCache
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        $keys = $this->settings->getApiKeys();

        if (!empty($keys['key']) && $userid = $this->getUserId($identifier, $socialCache)) {
            $url                = "https://www.googleapis.com/plus/v1/people/{$userid}?key={$keys['key']}";
            $data               = $this->makeCall($url);
            $info               = $this->matchUpData($data);
            $info['profileHandle'] = $data->url;
            if (isset($data->image->url)) {
                //remove the size from the end
                $image = $data->image->url;
                $image                   = preg_replace('/\?.*/', '', $image);
                $info["profileImage"] = $image;
            }
            $socialCache['profile'] = $info;
            $socialCache['updated'] = true;
        } elseif (empty($socialCache['profile'])) {
            //populate empty data
            $empty = new \stdClass();
            $socialCache['profile'] = $this->matchUpData($empty);
            $socialCache['profile']['profileHandle'] = "";
            $socialCache['profile']['profileImage']  = $this->factory->getAssetsHelper()->getUrl('assets/images/avatar.png');
            $socialCache['updated'] = true;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     * @return array
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        $keys = $this->settings->getApiKeys();
        if (!empty($keys['key']) && $id = $this->getUserId($identifier, $socialCache)) {
            $url  = "https://www.googleapis.com/plus/v1/people/$id/activities/public?key={$keys['key']}&maxResults=10";
            $data = $this->makeCall($url);
            if (!empty($data) && isset($data->items) && count($data->items)) {
                $socialCache['activity'] = array(
                    'posts'  => array(),
                    'photos' => array()
                );
                foreach ($data->items as $page) {
                    $post = array(
                        'title'     => $page->title,
                        'url'       => $page->url,
                        'published' => $page->published,
                        'updated'   => $page->updated
                    );
                    $socialCache['activity']['posts'][] = $post;

                    if (isset($page->object->attachments)) {
                        foreach ($page->object->attachments as $a) {
                            if (isset($a->fullImage)) {
                                $photo = array(
                                    'url'  => $a->fullImage->url,
                                    'type' => $a->fullImage->type
                                );
                                $socialCache['activity']['photos'][] = $photo;
                            }
                        }
                    }
                }
                $socialCache['updated'] = true;
            }
        }

        if (empty($socialCache['activity'])) {
            //ensure keys are present
            $socialCache['activity'] = array(
                'posts' => array(),
                'photos' => array()
            );
            $socialCache['updated'] = true;
        }
    }

    /**
     * Convert and assign the data to assignable fields
     *
     * @param $data
     */
    private function matchUpData($data)
    {
        $info       = array();
        $available  = $this->getAvailableFields();
        $translator = $this->factory->getTranslator();

        foreach ($available as $field => $fieldDetails) {
            if (!isset($data->$field)) {
                $info[$field] = '';
            } else {
                $values = $data->$field;

                switch ($fieldDetails['type']) {
                    case 'string':
                    case 'boolean':
                        $info[$field] = $values;
                        break;
                    case 'object':
                        foreach ($fieldDetails['fields'] as $f) {
                            if (isset($values->$f)) {
                                $name        = $f . ucfirst($field);
                                $info[$name] = $values->$f;
                            }
                        }
                        break;
                    case 'array_object':
                        if ($field == "urls") {
                            $socialProfileUrls = NetworkIntegrationHelper::getSocialProfileUrlRegex();
                            foreach ($values as $k => $v) {
                                $socialMatch = false;
                                foreach ($socialProfileUrls as $service => $regex) {
                                    if (is_array($regex)) {
                                        foreach ($regex as $r) {
                                            preg_match($r, $v->value, $match);
                                            if (!empty($match[1])) {
                                                $info[$service . 'ProfileHandle'] = $match[1];
                                                $socialMatch                      = true;
                                                break;
                                            }
                                        }
                                        if ($socialMatch)
                                            break;
                                    } else {
                                        preg_match($regex, $v->value, $match);
                                        if (!empty($match[1])) {
                                            $info[$service . 'ProfileHandle'] = $match[1];
                                            $socialMatch                      = true;
                                            break;
                                        }
                                    }
                                }

                                if (!$socialMatch) {
                                    $name = $v->type . 'Urls';
                                    if (isset($info[$name])) {
                                        $info[$name] .= ", {$v->label} ({$v->value})";
                                    } else {
                                        $info[$name] = "{$v->label} ({$v->value})";
                                    }
                                }
                            }
                        } elseif ($field == "organizations") {
                            $organizations = array();

                            foreach ($values as $k => $v) {
                                if (!empty($v->name) && !empty($v->title))
                                    $organization = $v->name . ', ' . $v->title;
                                elseif (!empty($v->name)) {
                                    $organization = $v->name;
                                } elseif (!empty($v->title)) {
                                    $organization = $v->title;
                                }

                                if (!empty($v->startDate) && !empty($v->endDate)) {
                                    $organization .= " " . $v->startDate . ' - ' . $v->endDate;
                                } elseif (!empty($v->startDate)) {
                                    $organization .= ' ' . $v->startDate;
                                } elseif (!empty($v->endDate)) {
                                    $organization .= ' ' . $v->endDate;
                                }

                                if (!empty($v->primary)) {
                                    $organization .= " (" . $translator->trans('mautic.lead.lead.primary') . ")";
                                }
                                $organizations[$v->type][] = $organization;
                            }
                            foreach ($organizations as $type => $orgs) {
                                $info[$type . "Organizations"] = implode("; ", $orgs);
                            }
                        } elseif ($field == "placesLived") {
                            $places = array();
                            foreach ($values as $k => $v) {
                                $primary  = (!empty($v->primary)) ? ' (' . $translator->trans('mautic.lead.lead.primary') . ')' : '';
                                $places[] = $v->value . $primary;
                            }
                            $info[$field] = implode('; ', $places);
                        }
                        break;
                }
            }
        }
        return $info;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getAvailableFields()
    {
        return array(
            "profileHandle"      => array("type" => "string"),
            "nickname"           => array("type" => "string"),
            "occupation"         => array("type" => "string"),
            "skills"             => array("type" => "string"),
            "birthday"           => array("type" => "string"),
            "gender"             => array("type" => "string"),
            "urls"               => array(
                "type"   => "array_object",
                "fields" => array(
                    "otherProfile",
                    "contributor",
                    "website",
                    "other"
                )
            ),
            "name"               => array(
                "type"   => "object",
                "fields" => array(
                    "formatted",
                    "familyName",
                    "givenName",
                    "middleName",
                    "honorificPrefix",
                    "honorificSuffix"
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
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'key' => 'mautic.social.keyfield.api'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     * @return mixed|null
     */
    public function getUserId($identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        $cleaned = $this->cleanIdentifier($identifier);
        if (!empty($cleaned['googleplus'])) {
            $query = $cleaned['googleplus'];
        } elseif (!empty($cleaned['email'])) {
            $query = $cleaned['email'];
        }
        $keys  = $this->settings->getApiKeys();
        if (!empty($query) && !empty($keys['key'])) {
            $url  = "https://www.googleapis.com/plus/v1/people?query={$query}&key={$keys['key']}";
            $data = $this->makeCall($url);

            if (!empty($data) && isset($data->items) && count($data->items)) {
                $socialCache['id'] = $data->items[0]->id;

                //mark the cache as needing to be updated
                $socialCache['updated'] = true;

                return $socialCache['id'];
            }
        }

        return false;
    }
}