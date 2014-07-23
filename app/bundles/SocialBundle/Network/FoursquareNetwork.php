<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

class FoursquareNetwork extends CommonNetwork
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Foursquare';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getIdentifierField()
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://foursquare.com/oauth2/authenticate';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|string
     */
    public function getAccessTokenUrl()
    {
        return 'https://foursquare.com/oauth2/access_token';
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
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * Get public data
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        $keys = $this->settings->getApiKeys();
        if (!empty($keys['access_token']) && $id = $this->getUserId($identifier, $socialCache)) {
            $url  = "https://api.foursquare.com/v2/users/{$id}?v=20140719&oauth_token={$keys['access_token']}";
            $data = $this->makeCall($url);
            if (!empty($data) && isset($data->response->user)) {
                $result = $data->response->user;
                $socialCache['profile'] = $this->matchUpData($result);
                if (isset($result->photo)) {
                    $socialCache['profile']['profileImage'] = $result->photo->prefix . '300x300' . $result->photo->suffix;
                }
                $socialCache['profile']['profileHandle'] = 'https://foursquare.com/user/' . $id;

                //mark as updated
                $socialCache['updated'] = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array|void
     */
    public function getPublicActivity($identifier, &$socialCache)
    {
        $keys = $this->settings->getApiKeys();
        if (!empty($keys['access_token']) && $id = $this->getUserId($identifier, $socialCache)) {
            $activity = array();

            //mayorships
            $url  = "https://api.foursquare.com/v2/users/{$id}/mayorships?v=20140719&oauth_token={$keys['access_token']}";
            $data = $this->makeCall($url);
            if (isset($data->response->mayorships) && count($data->response->mayorships->items)) {
                $limit = 5;
                foreach ($data->response->mayorships->items as $m) {
                    if (empty($limit)) {
                        break;
                    }
                    //find main category of venue
                    $category = '';
                    foreach ($m->venue->categories as $c) {
                        if ($c->primary) {
                            $category = $c->name;
                            break;
                        }
                    }
                    $contact = (!empty($m->contact->formattedPhone)) ? $m->contact->formattedPhone : '';
                    $activity['mayorships'][] = array(
                        'venueName'     => $m->venue->name,
                        'venueLocation' => $m->venue->location->formattedAddress,
                        'venueContact'  => $contact,
                        'venueCategory' => $category
                    );
                    $limit--;
                }
            }

            //tips
            $url  = "https://api.foursquare.com/v2/users/{$id}/tips?limit=5&sort=recent&v=20140719&oauth_token={$keys['access_token']}";
            $data = $this->makeCall($url);

            if (isset($data->response->tips) && count($data->response->tips->items)) {
                foreach ($data->response->tips->items as $t) {
                    //find main category of venue
                    $category = '';
                    foreach ($t->venue->categories as $c) {
                        if ($c->primary) {
                            $category = $c->name;
                            break;
                        }
                    }
                    $contact = (!empty($t->contact->formattedPhone)) ? $t->contact->formattedPhone : '';
                    $activity['tips'][] = array(
                        'createdAt'     => $t->createdAt,
                        'tipText'       => $t->text,
                        'tipUrl'        => $t->canonicalUrl,
                        'venueName'     => $t->venue->name,
                        'venueLocation' => $t->venue->location->formattedAddress,
                        'venueContact'  => $contact,
                        'venueCategory'  => $category
                    );
                }
            }

            //lists
            $url  = "https://api.foursquare.com/v2/users/{$id}/lists?limit=5&group=created&v=20140719&oauth_token={$keys['access_token']}";
            $data = $this->makeCall($url);

            if (isset($data->response->lists) && count($data->response->lists->items)) {
                foreach ($data->response->lists->items as $l) {
                    if (!$l->listItems->count) {
                        continue;
                    }

                    $item = array(
                        'listName'        => $l->name,
                        'listDescription' => $l->description,
                        'listUrl'         => $l->canonicalUrl,
                        'listCreatedAt'   => (isset($l->createdAt)) ? $l->createdAt : '',
                        'listUpdatedAt'   => (isset($l->updatedAt)) ? $l->updatedAt : '',
                        'listItems'       => array()
                    );

                    //get a sample of the list items
                    $url      = "https://api.foursquare.com/v2/lists/{$l->id}?limit=5&sort=recent&v=20140719&oauth_token={$keys['access_token']}";
                    $listData = $this->makeCall($url);

                    if (isset($listData->response->list->listItems) && count($listData->response->list->listItems->items)) {
                        foreach ($listData->response->list->listItems->items as $li) {
                            //find main category of venue
                            $category = '';
                            foreach ($li->venue->categories as $c) {
                                if ($c->primary) {
                                    $category = $c->name;
                                    break;
                                }
                            }
                            $contact = (!empty($li->contact->formattedPhone)) ? $li->contact->formattedPhone : '';

                            $item['listItems'][] = array(
                                'createdAt'     => $li->createdAt,
                                'venueName'     => $li->venue->name,
                                'venueLocation' => $li->venue->location->formattedAddress,
                                'venueContact'  => $contact,
                                'venueCategory'  => $category
                            );
                        }
                    }

                    $activity['lists'][] = $item;
                }
            }

            if (!empty($activity)) {
                $socialCache['activity'] = $activity;
                $socialCache['updated']  = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $response
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if (is_object($response) && isset($response->meta->errorDetail)) {
            return $response->meta->errorDetail . ' (' . $response->meta->code . ')';
        }
        return '';
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
                            $name = ($f == 'twitter' || $f == 'facebook') ? $f . 'ProfileHandle' : $f . ucfirst($field);
                            $info[$name] = $values->$f;
                        }
                    }
                    break;
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
            "profileHandle" => array("type" => "string"),
            "firstName"     => array("type" => "string"),
            "lastName"      => array("type" => "string"),
            "gender"        => array("type" => "string"),
            "homeCity"      => array("type" => "string"),
            "bio"           => array("type" => "string"),
            "contact"       => array(
                "type"   => "object",
                "fields" => array(
                    "twitter",
                    "facebook",
                    "phone"
                )
            )
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
            'public_profile',
            'public_activity'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param $identifier
     * @param $socialCache
     * @return bool
     */
    public function getUserId($identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        $email = $this->cleanIdentifier($identifier);
        $keys  = $this->settings->getApiKeys();

        if (!empty($keys['access_token'])) {
            $url  = "https://api.foursquare.com/v2/users/search?v=20140719&email={$email}&oauth_token={$keys['access_token']}";
            $data = $this->makeCall($url);
            if (!empty($data) && isset($data->response->results) && count($data->response->results)) {
                $socialCache['id'] = $data->response->results[0]->id;

                //mark the cache as needing to be updated
                $socialCache['updated'] = true;

                return $socialCache['id'];
            }
        }

        return false;
    }
}