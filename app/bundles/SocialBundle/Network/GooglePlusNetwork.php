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

class GooglePlusNetwork extends CommonNetwork
{
    private $userEmail = '';
    private $userId    = false;

    public function getName()
    {
        return 'GooglePlus';
    }

    /**
     * @param $email
     * @return mixed|null
     */
    private function findUserByEmail($email)
    {
        if ($email != $this->userEmail || empty($this->userId)) {
            $this->userEmail = $email;
            $email = urlencode($email);
            $keys  = $this->settings->getApiKeys();
            if (!empty($keys['key'])) {
                $url  = "https://www.googleapis.com/plus/v1/people?query={$email}&key={$keys['key']}";
                $data = $this->makeCall($url);

                if (!empty($data) && isset($data->items) && count($data->items)) {
                    $result = $data->items[0];
                    $this->userId = $result->id;
                    return $this->userId;
                }
            }
        } elseif (!empty($this->userId)) {
            return $this->userId;
        }
        return false;
    }

    /**
     * Get public data
     *
     * @param $email
     * @return array
     */
    public function getUserData($email)
    {
        $userid = $this->findUserByEmail($email);
        if ($userid) {
            $key                          = $this->factory->getParameter('googleplus_apikey');
            $url                          = "https://www.googleapis.com/plus/v1/people/{$userid}?key={$key}";
            $data                         = $this->makeCall($url);
            $info                         = $this->matchUpData($data);
            $info['profileUrl'] = $data->url;
            if (isset($data->image->url)) {
                //remove the size from the end
                $image = $data->image->url;
                $image                   = preg_replace('/\?.*/', '', $image);
                $info["profileImage"] = $image;
            }
            return $info;
        }
    }

    /**
     * Retrieve public posts
     *
     * @param $email
     * @return array
     */
    public function getPublicActivity($email)
    {
        $id  = $this->findUserByEmail($email);
        $posts = array();
        if ($id) {
            $key  = $this->factory->getParameter('googleplus_apikey');
            $url  = "https://www.googleapis.com/plus/v1/people/$id/activities/public?key={$key}&maxResults=5";
            $data = $this->makeCall($url);

            if (!empty($data) && isset($data->items) && count($data->items)) {
                foreach ($data->items as $page) {
                    $post = array(
                        'title'     => $page->title,
                        'url'       => $page->url,
                        'published' => $page->published,
                        'updated'   => $page->updated
                    );
                    $posts[] = $post;
                }
            }
        }
        return $posts;
    }

    /**
     * Convert and assign the data to assignable fields
     *
     * @param $data
     */
    protected function matchUpData($data)
    {
        $info       = array();
        $available  = $this->getAvailableFields();
        $translator = $this->factory->getTranslator();

        foreach ($data as $field => $values) {
            if (!isset($available[$field]))
                continue;

            $fieldDetails = $available[$field];

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
                        $socialProfileUrls = NetworkIntegrationHelper::getSocialProfileUrls();
                        foreach ($values as $k => $v) {
                            $socialMatch       = false;
                            foreach ($socialProfileUrls as $service => $url) {
                                if (is_array($url)) {
                                    foreach ($url as $u) {
                                        if (strpos($v->value, $u) !== false) {
                                            $info[$service . 'ProfileUrl'] = $v->value;
                                            $socialMatch                   = true;
                                            break;
                                        }
                                    }
                                    if ($socialMatch)
                                        break;
                                } elseif (strpos($v->value, $url) !== false) {
                                    $info[$service . 'ProfileUrl'] = $v->value;
                                    $socialMatch                   = true;
                                    break;
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
        return $info;
    }

    public function getAvailableFields()
    {
        return array(
            "profileUrl"         => array("type" => "string"),
            "profileImage"       => array("type" => "string"),
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

    public function getRequiredKeyFields()
    {
        return array(
            'key' => 'mautic.social.keyfield.api'
        );
    }

    public function getSupportedFeatures()
    {
        return array(
            'lead_fields',
            'public_activity',
            'share_button'
        );
    }

    public function getAuthenticationType()
    {
        return 'key';
    }
}