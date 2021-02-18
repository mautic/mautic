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
 * Class FoursquareIntegration.
 */
class FoursquareIntegration extends SocialIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Foursquare';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFields()
    {
        return [
            'email',
            'twitter', //foursquare allows searching directly by twitter handle
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl()
    {
        return 'https://foursquare.com/oauth2/authenticate';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl()
    {
        return 'https://foursquare.com/oauth2/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * @param string $endpoint
     * @param string $m
     *
     * @return string
     */
    public function getApiUrl($endpoint, $m = 'foursquare')
    {
        return "https://api.foursquare.com/v2/$endpoint?v=20140806&m={$m}";
    }

    /**
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed|string
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [])
    {
        $settings[$this->getAuthTokenKey()] = 'oauth_token';

        return parent::makeRequest($url, $parameters, $method, $settings);
    }

    /**
     * Get public data.
     *
     * @param $identifier
     * @param $socialCache
     *
     * @return array
     */
    public function getUserData($identifier, &$socialCache)
    {
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            $url  = $this->getApiUrl("users/{$id}");
            $data = $this->makeRequest($url);
            if (!empty($data) && isset($data->response->user)) {
                $result                 = $data->response->user;
                $socialCache['profile'] = $this->matchUpData($result);
                if (isset($result->photo)) {
                    $socialCache['profile']['profileImage'] = $result->photo->prefix.'300x300'.$result->photo->suffix;
                }
                $socialCache['profile']['profileHandle'] = $id;
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
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            $activity = [
                //'mayorships' => array(),
                'tips' => [],
                //'lists'      => array()
            ];

            /*
            //mayorships
            $url  = $this->getApiUrl("users/{$id}/mayorships");
            $data = $this->makeRequest($url);

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
            */

            //tips
            $url  = $this->getApiUrl("users/{$id}/tips").'&limit=5&sort=recent';
            $data = $this->makeRequest($url);

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
                    $contact            = (!empty($t->contact->formattedPhone)) ? $t->contact->formattedPhone : '';
                    $activity['tips'][] = [
                        'createdAt'     => $t->createdAt,
                        'tipText'       => $t->text,
                        'tipUrl'        => $t->canonicalUrl,
                        'venueName'     => $t->venue->name,
                        'venueLocation' => $t->venue->location->formattedAddress,
                        'venueContact'  => $contact,
                        'venueCategory' => $category,
                    ];
                }
            }

            /*
            //lists
            $url  = $this->getApiUrl("users/{$id}/lists") . "&limit=5&group=created";
            $data = $this->makeRequest($url);

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
                    $listData = $this->makeRequest($url);

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
            */

            if (!empty($activity)) {
                $socialCache['activity'] = $activity;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorsFromResponse($response)
    {
        if (is_object($response) && isset($response->meta->errorDetail)) {
            return $response->meta->errorDetail.' ('.$response->meta->code.')';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function matchFieldName($field, $subfield = '')
    {
        if ('contact' == $field && in_array($subfield, ['facebook', 'twitter'])) {
            return $subfield.'ProfileHandle';
        }

        return parent::matchFieldName($field, $subfield);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'profileHandle' => ['type' => 'string'],
            'firstName'     => ['type' => 'string'],
            'lastName'      => ['type' => 'string'],
            'gender'        => ['type' => 'string'],
            'homeCity'      => ['type' => 'string'],
            'bio'           => ['type' => 'string'],
            'contact'       => [
                'type'   => 'object',
                'fields' => [
                    'twitter',
                    'facebook',
                    'phone',
                ],
            ],
        ];
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
     * @param $identifier
     * @param $socialCache
     *
     * @return bool
     */
    private function getContactUserId(&$identifier, &$socialCache)
    {
        if (!empty($socialCache['id'])) {
            return $socialCache['id'];
        } elseif (empty($identifier)) {
            return false;
        }

        $cleaned = $this->cleanIdentifier($identifier);

        if (!is_array($cleaned)) {
            $cleaned = [$cleaned];
        }

        foreach ($cleaned as $type => $c) {
            $url  = $this->getApiUrl('users/search')."&{$type}={$c}";
            $data = $this->makeRequest($url);

            if (!empty($data) && isset($data->response->results) && count($data->response->results)) {
                $socialCache['id'] = $data->response->results[0]->id;

                return $socialCache['id'];
            }
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
