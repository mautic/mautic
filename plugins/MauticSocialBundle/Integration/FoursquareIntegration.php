<?php

namespace MauticPlugin\MauticSocialBundle\Integration;

class FoursquareIntegration extends SocialIntegration
{
    public function getName(): string
    {
        return 'Foursquare';
    }

    public function getPriority(): int
    {
        return 2;
    }

    /**
     * @return string[]
     */
    public function getIdentifierFields(): array
    {
        return [
            'email',
            'twitter', // foursquare allows searching directly by twitter handle
        ];
    }

    public function getAuthenticationUrl(): string
    {
        return 'https://foursquare.com/oauth2/authenticate';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://foursquare.com/oauth2/access_token';
    }

    public function getAuthenticationType(): string
    {
        return 'oauth2';
    }

    /**
     * @param string $endpoint
     * @param string $m
     */
    public function getApiUrl($endpoint, $m = 'foursquare'): string
    {
        return "https://api.foursquare.com/v2/$endpoint?v=20140806&m={$m}";
    }

    /**
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
     */
    public function getUserData($identifier, &$socialCache): void
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

    public function getPublicActivity($identifier, &$socialCache): void
    {
        if ($id = $this->getContactUserId($identifier, $socialCache)) {
            $activity = [
                // 'mayorships' => array(),
                'tips' => [],
                // 'lists'      => array()
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

            // tips
            $url  = $this->getApiUrl("users/{$id}/tips").'&limit=5&sort=recent';
            $data = $this->makeRequest($url);

            if (isset($data->response->tips) && count($data->response->tips->items)) {
                foreach ($data->response->tips->items as $t) {
                    // find main category of venue
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

    public function getErrorsFromResponse($response): string
    {
        if (is_object($response) && isset($response->meta->errorDetail)) {
            return $response->meta->errorDetail.' ('.$response->meta->code.')';
        }

        return '';
    }

    public function matchFieldName($field, $subfield = '')
    {
        if ('contact' == $field && in_array($subfield, ['facebook', 'twitter'])) {
            return $subfield.'ProfileHandle';
        }

        return parent::matchFieldName($field, $subfield);
    }

    public function getAvailableLeadFields($settings = []): array
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

    public function getSupportedFeatures(): array
    {
        return [
            'public_profile',
            'public_activity',
        ];
    }

    /**
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

    public function getFormType()
    {
        return null;
    }
}
